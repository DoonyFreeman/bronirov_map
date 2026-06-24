<?php
/**
 * GraphQL-мутация createBooking.
 *
 * Защиты: honeypot, rate-limit по IP, идемпотентность по ключу, проверка что
 * выбранный слот реально доступен (анти-гонка). Бронь создаётся в статусе
 * pending; уведомления — Спринт 4, авторизация владельца — Спринт 5.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Graphql;

use GraphQL\Error\UserError;
use ServiceHub\Booking\AvailabilityService;
use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\BookingPostType;
use ServiceHub\PostTypes\ServicePostType;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует мутацию создания брони.
 */
final class CreateBookingMutation implements Module {

	private const RATE_LIMIT       = 10;
	private const RATE_WINDOW_SECS = 60;

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'graphql_register_types', array( $this, 'register_mutation' ) );
	}

	/**
	 * Зарегистрировать мутацию createBooking.
	 */
	public function register_mutation(): void {
		if ( ! function_exists( 'register_graphql_mutation' ) ) {
			return;
		}

		register_graphql_mutation(
			'createServiceBooking',
			array(
				'inputFields'         => array(
					'serviceId'      => array( 'type' => array( 'non_null' => 'ID' ) ),
					'date'           => array( 'type' => array( 'non_null' => 'String' ) ),
					'time'           => array( 'type' => array( 'non_null' => 'String' ) ),
					'clientName'     => array( 'type' => array( 'non_null' => 'String' ) ),
					'clientPhone'    => array( 'type' => array( 'non_null' => 'String' ) ),
					'clientEmail'    => array( 'type' => 'String' ),
					'idempotencyKey' => array( 'type' => array( 'non_null' => 'String' ) ),
					'notes'          => array( 'type' => 'String' ),
					'website'        => array(
						'type'        => 'String',
						'description' => __( 'Honeypot — должно оставаться пустым.', 'servicehub' ),
					),
				),
				// Бронь — приватный тип, поэтому возвращаем скаляры подтверждения,
				// а не queryable-ноду (доступ к своим броням — Спринт 5 с auth).
				'outputFields'        => array(
					'bookingDatabaseId' => array(
						'type'    => 'Int',
						'resolve' => static fn ( $payload ) => $payload['id'] ?? null,
					),
					'status'            => array(
						'type'    => 'String',
						'resolve' => static fn ( $payload ) => $payload['status'] ?? null,
					),
					'date'              => array(
						'type'    => 'String',
						'resolve' => static fn ( $payload ) => $payload['date'] ?? null,
					),
					'time'              => array(
						'type'    => 'String',
						'resolve' => static fn ( $payload ) => $payload['time'] ?? null,
					),
				),
				'mutateAndGetPayload' => array( $this, 'mutate' ),
			)
		);
	}

	/**
	 * Создать бронь с валидацией.
	 *
	 * @param array<string, mixed> $input Входные данные мутации.
	 * @return array{id:int,status:string}
	 *
	 * @throws UserError При нарушении правил/недоступном слоте.
	 */
	public function mutate( array $input ): array {
		// 1. Honeypot.
		if ( ! empty( $input['website'] ) ) {
			throw new UserError( __( 'Заявка отклонена.', 'servicehub' ) );
		}

		// 2. Rate-limit по IP.
		$this->enforce_rate_limit();

		$service_id = (int) ( $input['serviceId'] ?? 0 );
		$date       = sanitize_text_field( (string) ( $input['date'] ?? '' ) );
		$time       = sanitize_text_field( (string) ( $input['time'] ?? '' ) );
		$key        = sanitize_text_field( (string) ( $input['idempotencyKey'] ?? '' ) );

		if ( ServicePostType::POST_TYPE !== get_post_type( $service_id ) ) {
			throw new UserError( __( 'Услуга не найдена.', 'servicehub' ) );
		}

		// 3. Идемпотентность: бронь с этим ключом уже создана?
		$existing = $this->find_by_idempotency_key( $key );
		if ( $existing ) {
			return array(
				'id'     => $existing,
				'status' => (string) get_post_meta( $existing, 'booking_status', true ),
				'date'   => (string) get_post_meta( $existing, 'booking_date', true ),
				'time'   => (string) get_post_meta( $existing, 'booking_time', true ),
			);
		}

		// 4. Проверка доступности слота (анти-гонка / невалидный ввод).
		$slots = ( new AvailabilityService() )->available_slots( $service_id, $date );
		if ( ! in_array( $time, $slots, true ) ) {
			throw new UserError( __( 'Выбранное время недоступно. Обновите слоты.', 'servicehub' ) );
		}

		$company_id = (int) get_post_meta( $service_id, 'service_company', true );
		$duration   = (int) get_post_meta( $service_id, 'service_duration', true );
		$name       = sanitize_text_field( (string) ( $input['clientName'] ?? '' ) );

		// 5. Создать бронь.
		$booking_id = wp_insert_post(
			array(
				'post_type'   => BookingPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sprintf( 'Бронь: %s — %s %s', $name, $date, $time ),
			),
			true
		);
		if ( is_wp_error( $booking_id ) ) {
			throw new UserError( __( 'Не удалось создать бронь.', 'servicehub' ) );
		}

		$meta = array(
			'booking_service'         => $service_id,
			'booking_company'         => $company_id,
			'booking_date'            => $date,
			'booking_time'            => $time,
			'booking_duration'        => $duration,
			'booking_status'          => BookingPostType::STATUS_PENDING,
			'booking_client_name'     => $name,
			'booking_client_phone'    => sanitize_text_field( (string) ( $input['clientPhone'] ?? '' ) ),
			'booking_client_email'    => sanitize_email( (string) ( $input['clientEmail'] ?? '' ) ),
			'booking_idempotency_key' => $key,
			'booking_notes'           => sanitize_textarea_field( (string) ( $input['notes'] ?? '' ) ),
		);
		foreach ( $meta as $meta_key => $value ) {
			update_post_meta( $booking_id, $meta_key, $value );
		}

		return array(
			'id'     => (int) $booking_id,
			'status' => BookingPostType::STATUS_PENDING,
			'date'   => $date,
			'time'   => $time,
		);
	}

	/**
	 * Ограничить число попыток с одного IP.
	 *
	 * @throws UserError При превышении лимита.
	 */
	private function enforce_rate_limit(): void {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$cache = 'sh_book_rl_' . md5( $ip );
		$count = (int) get_transient( $cache );
		if ( $count >= self::RATE_LIMIT ) {
			throw new UserError( __( 'Слишком много заявок. Попробуйте позже.', 'servicehub' ) );
		}
		set_transient( $cache, $count + 1, self::RATE_WINDOW_SECS );
	}

	/**
	 * Найти бронь по ключу идемпотентности.
	 *
	 * @param string $key Ключ.
	 * @return int|null ID существующей брони.
	 */
	private function find_by_idempotency_key( string $key ): ?int {
		if ( '' === $key ) {
			return null;
		}
		$query = new \WP_Query(
			array(
				'post_type'      => BookingPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'booking_idempotency_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return $query->posts[0] ?? null;
	}
}
