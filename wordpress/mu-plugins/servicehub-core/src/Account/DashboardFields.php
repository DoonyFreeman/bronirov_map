<?php
/**
 * Кабинет компании в GraphQL (owner-scoped).
 *
 * Владелец (мета company_owner = ID пользователя) управляет своей компанией:
 * видит брони с контактами клиентов, меняет их статус, ведёт услуги.
 * Все операции строго ограничены своей компанией.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Account;

use GraphQL\Error\UserError;
use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\BookingPostType;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ServicePostType;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Запросы и мутации бизнес-кабинета.
 */
final class DashboardFields implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'graphql_register_types', array( $this, 'register_types' ) );
	}

	/**
	 * Зарегистрировать типы, запросы и мутации кабинета компании.
	 */
	public function register_types(): void {
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}

		register_graphql_object_type(
			'CompanyBookingView',
			array(
				'description' => __( 'Бронь компании с контактами клиента (для владельца).', 'servicehub' ),
				'fields'      => array(
					'databaseId'  => array( 'type' => 'Int' ),
					'date'        => array( 'type' => 'String' ),
					'time'        => array( 'type' => 'String' ),
					'status'      => array( 'type' => 'String' ),
					'serviceName' => array( 'type' => 'String' ),
					'clientName'  => array( 'type' => 'String' ),
					'clientPhone' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_field(
			'RootQuery',
			'myCompany',
			array(
				'type'        => 'Company',
				'description' => __( 'Компания, которой владеет текущий пользователь.', 'servicehub' ),
				'resolve'     => function () {
					$company_id = $this->owned_company_id();
					$post       = $company_id ? get_post( $company_id ) : null;
					return $post ? new Post( $post ) : null;
				},
			)
		);

		register_graphql_field(
			'RootQuery',
			'companyBookings',
			array(
				'type'        => array( 'list_of' => 'CompanyBookingView' ),
				'description' => __( 'Брони компании текущего владельца.', 'servicehub' ),
				'resolve'     => array( $this, 'resolve_company_bookings' ),
			)
		);

		register_graphql_mutation(
			'setBookingStatus',
			array(
				'inputFields'         => array(
					'bookingId' => array( 'type' => array( 'non_null' => 'ID' ) ),
					'status'    => array( 'type' => array( 'non_null' => 'String' ) ),
				),
				'outputFields'        => array(
					'status' => array(
						'type'    => 'String',
						'resolve' => static fn ( $p ) => $p['status'] ?? null,
					),
				),
				'mutateAndGetPayload' => array( $this, 'set_booking_status' ),
			)
		);

		register_graphql_mutation(
			'saveCompanyService',
			array(
				'inputFields'         => array(
					'serviceId'   => array( 'type' => 'ID' ),
					'title'       => array( 'type' => array( 'non_null' => 'String' ) ),
					'price'       => array( 'type' => 'Float' ),
					'duration'    => array( 'type' => 'Int' ),
					'description' => array( 'type' => 'String' ),
				),
				'outputFields'        => array(
					'serviceDatabaseId' => array(
						'type'    => 'Int',
						'resolve' => static fn ( $p ) => $p['id'] ?? null,
					),
				),
				'mutateAndGetPayload' => array( $this, 'save_service' ),
			)
		);

		register_graphql_mutation(
			'deleteCompanyService',
			array(
				'inputFields'         => array(
					'serviceId' => array( 'type' => array( 'non_null' => 'ID' ) ),
				),
				'outputFields'        => array(
					'deleted' => array(
						'type'    => 'Boolean',
						'resolve' => static fn ( $p ) => $p['deleted'] ?? false,
					),
				),
				'mutateAndGetPayload' => array( $this, 'delete_service' ),
			)
		);
	}

	/**
	 * Брони компании владельца (с контактами клиентов).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function resolve_company_bookings(): array {
		$company_id = $this->owned_company_id();
		if ( ! $company_id ) {
			return array();
		}
		$query = new \WP_Query(
			array(
				'post_type'      => BookingPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => 'booking_company', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $company_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return array_map(
			static function ( $id ) {
				return array(
					'databaseId'  => $id,
					'date'        => (string) get_post_meta( $id, 'booking_date', true ),
					'time'        => (string) get_post_meta( $id, 'booking_time', true ),
					'status'      => (string) get_post_meta( $id, 'booking_status', true ),
					'serviceName' => get_the_title( (int) get_post_meta( $id, 'booking_service', true ) ),
					'clientName'  => (string) get_post_meta( $id, 'booking_client_name', true ),
					'clientPhone' => (string) get_post_meta( $id, 'booking_client_phone', true ),
				);
			},
			$query->posts
		);
	}

	/**
	 * Сменить статус брони своей компании.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{status:string}
	 *
	 * @throws UserError При чужой брони/неверном статусе.
	 */
	public function set_booking_status( array $input ): array {
		$company_id = $this->require_company();
		$booking_id = (int) ( $input['bookingId'] ?? 0 );
		$status     = (string) ( $input['status'] ?? '' );

		$allowed = array( BookingPostType::STATUS_CONFIRMED, BookingPostType::STATUS_CANCELLED );
		if ( ! in_array( $status, $allowed, true ) ) {
			throw new UserError( __( 'Недопустимый статус.', 'servicehub' ) );
		}
		if ( (int) get_post_meta( $booking_id, 'booking_company', true ) !== $company_id ) {
			throw new UserError( __( 'Бронь не найдена.', 'servicehub' ) );
		}

		update_post_meta( $booking_id, 'booking_status', $status );
		return array( 'status' => $status );
	}

	/**
	 * Создать/обновить услугу своей компании.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{id:int}
	 *
	 * @throws UserError При чужой услуге.
	 */
	public function save_service( array $input ): array {
		$company_id = $this->require_company();
		$service_id = (int) ( $input['serviceId'] ?? 0 );

		if ( $service_id > 0 ) {
			if ( (int) get_post_meta( $service_id, 'service_company', true ) !== $company_id ) {
				throw new UserError( __( 'Услуга не найдена.', 'servicehub' ) );
			}
			wp_update_post(
				array(
					'ID'           => $service_id,
					'post_title'   => sanitize_text_field( (string) $input['title'] ),
					'post_content' => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
				)
			);
		} else {
			$service_id = (int) wp_insert_post(
				array(
					'post_type'    => ServicePostType::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => sanitize_text_field( (string) $input['title'] ),
					'post_content' => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
				)
			);
			update_post_meta( $service_id, 'service_company', $company_id );
		}

		if ( isset( $input['price'] ) ) {
			update_post_meta( $service_id, 'service_price', (float) $input['price'] );
		}
		if ( isset( $input['duration'] ) ) {
			update_post_meta( $service_id, 'service_duration', (int) $input['duration'] );
		}

		return array( 'id' => $service_id );
	}

	/**
	 * Удалить услугу своей компании.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{deleted:bool}
	 *
	 * @throws UserError При чужой услуге.
	 */
	public function delete_service( array $input ): array {
		$company_id = $this->require_company();
		$service_id = (int) ( $input['serviceId'] ?? 0 );
		if ( (int) get_post_meta( $service_id, 'service_company', true ) !== $company_id ) {
			throw new UserError( __( 'Услуга не найдена.', 'servicehub' ) );
		}
		wp_trash_post( $service_id );
		return array( 'deleted' => true );
	}

	/**
	 * ID компании текущего владельца или 0.
	 */
	private function owned_company_id(): int {
		$uid = get_current_user_id();
		if ( $uid <= 0 ) {
			return 0;
		}
		$query = new \WP_Query(
			array(
				'post_type'      => CompanyPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'company_owner', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $uid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return (int) ( $query->posts[0] ?? 0 );
	}

	/**
	 * ID компании владельца или ошибка.
	 *
	 * @throws UserError Если нет своей компании.
	 */
	private function require_company(): int {
		$company_id = $this->owned_company_id();
		if ( ! $company_id ) {
			throw new UserError( __( 'У вас нет компании.', 'servicehub' ) );
		}
		return $company_id;
	}
}
