<?php
/**
 * Кабинет клиента в GraphQL: мои брони, отмена, избранное, отзыв после визита.
 *
 * Все операции требуют авторизации (JWT). Брони — приватный тип, поэтому
 * наружу отдаём собственный BookingView с явной проверкой владельца, а не
 * queryable-ноду.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Account;

use GraphQL\Error\UserError;
use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\BookingPostType;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ReviewPostType;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Запросы и мутации личного кабинета.
 */
final class AccountFields implements Module {

	private const FAVORITES_META = 'sh_favorites';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'graphql_register_types', array( $this, 'register_types' ) );
	}

	/**
	 * Зарегистрировать типы, запросы и мутации кабинета.
	 */
	public function register_types(): void {
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}

		register_graphql_object_type(
			'BookingView',
			array(
				'description' => __( 'Бронь пользователя (безопасная проекция).', 'servicehub' ),
				'fields'      => array(
					'databaseId'  => array( 'type' => 'Int' ),
					'date'        => array( 'type' => 'String' ),
					'time'        => array( 'type' => 'String' ),
					'status'      => array( 'type' => 'String' ),
					'serviceName' => array( 'type' => 'String' ),
					'companyName' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_field(
			'RootQuery',
			'myBookings',
			array(
				'type'        => array( 'list_of' => 'BookingView' ),
				'description' => __( 'Брони текущего пользователя.', 'servicehub' ),
				'resolve'     => array( $this, 'resolve_my_bookings' ),
			)
		);

		register_graphql_field(
			'RootQuery',
			'myFavorites',
			array(
				'type'        => array( 'list_of' => 'Company' ),
				'description' => __( 'Избранные компании пользователя.', 'servicehub' ),
				'resolve'     => array( $this, 'resolve_my_favorites' ),
			)
		);

		register_graphql_mutation(
			'cancelBooking',
			array(
				'inputFields'         => array(
					'bookingId' => array( 'type' => array( 'non_null' => 'ID' ) ),
				),
				'outputFields'        => array(
					'status' => array(
						'type'    => 'String',
						'resolve' => static fn ( $p ) => $p['status'] ?? null,
					),
				),
				'mutateAndGetPayload' => array( $this, 'cancel_booking' ),
			)
		);

		register_graphql_mutation(
			'toggleFavorite',
			array(
				'inputFields'         => array(
					'companyId' => array( 'type' => array( 'non_null' => 'ID' ) ),
				),
				'outputFields'        => array(
					'isFavorite' => array(
						'type'    => 'Boolean',
						'resolve' => static fn ( $p ) => $p['isFavorite'] ?? false,
					),
					'companyIds' => array(
						'type'    => array( 'list_of' => 'Int' ),
						'resolve' => static fn ( $p ) => $p['companyIds'] ?? array(),
					),
				),
				'mutateAndGetPayload' => array( $this, 'toggle_favorite' ),
			)
		);

		register_graphql_mutation(
			'createServiceReview',
			array(
				'inputFields'         => array(
					'companyId' => array( 'type' => array( 'non_null' => 'ID' ) ),
					'rating'    => array( 'type' => array( 'non_null' => 'Int' ) ),
					'text'      => array( 'type' => 'String' ),
				),
				'outputFields'        => array(
					'reviewDatabaseId' => array(
						'type'    => 'Int',
						'resolve' => static fn ( $p ) => $p['id'] ?? null,
					),
				),
				'mutateAndGetPayload' => array( $this, 'create_review' ),
			)
		);
	}

	/**
	 * Текущий пользователь или ошибка авторизации.
	 *
	 * @throws UserError Если не авторизован.
	 */
	private function require_user(): int {
		$uid = get_current_user_id();
		if ( $uid <= 0 ) {
			throw new UserError( __( 'Требуется авторизация.', 'servicehub' ) );
		}
		return $uid;
	}

	/**
	 * Брони текущего пользователя.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function resolve_my_bookings(): array {
		$uid = get_current_user_id();
		if ( $uid <= 0 ) {
			return array();
		}
		$query = new \WP_Query(
			array(
				'post_type'      => BookingPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => 'booking_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $uid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return array_map( array( $this, 'booking_view' ), $query->posts );
	}

	/**
	 * Проекция брони в BookingView.
	 *
	 * @param int $id ID брони.
	 * @return array<string, mixed>
	 */
	private function booking_view( int $id ): array {
		$service_id = (int) get_post_meta( $id, 'booking_service', true );
		$company_id = (int) get_post_meta( $id, 'booking_company', true );
		return array(
			'databaseId'  => $id,
			'date'        => (string) get_post_meta( $id, 'booking_date', true ),
			'time'        => (string) get_post_meta( $id, 'booking_time', true ),
			'status'      => (string) get_post_meta( $id, 'booking_status', true ),
			'serviceName' => get_the_title( $service_id ),
			'companyName' => get_the_title( $company_id ),
		);
	}

	/**
	 * Отменить свою бронь.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{status:string}
	 *
	 * @throws UserError Если не владелец/не авторизован.
	 */
	public function cancel_booking( array $input ): array {
		$uid        = $this->require_user();
		$booking_id = (int) ( $input['bookingId'] ?? 0 );

		if ( BookingPostType::POST_TYPE !== get_post_type( $booking_id )
			|| (int) get_post_meta( $booking_id, 'booking_user', true ) !== $uid ) {
			throw new UserError( __( 'Бронь не найдена.', 'servicehub' ) );
		}

		update_post_meta( $booking_id, 'booking_status', BookingPostType::STATUS_CANCELLED );
		return array( 'status' => BookingPostType::STATUS_CANCELLED );
	}

	/**
	 * Избранные компании пользователя.
	 *
	 * @return array<int, Post>
	 */
	public function resolve_my_favorites(): array {
		$uid = get_current_user_id();
		if ( $uid <= 0 ) {
			return array();
		}
		$ids = $this->favorite_ids( $uid );
		$out = array();
		foreach ( $ids as $company_id ) {
			$post = get_post( $company_id );
			if ( $post && CompanyPostType::POST_TYPE === $post->post_type && 'publish' === $post->post_status ) {
				$out[] = new Post( $post );
			}
		}
		return $out;
	}

	/**
	 * Переключить компанию в избранном.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{isFavorite:bool,companyIds:array<int,int>}
	 *
	 * @throws UserError Если не авторизован.
	 */
	public function toggle_favorite( array $input ): array {
		$uid        = $this->require_user();
		$company_id = (int) ( $input['companyId'] ?? 0 );
		$ids        = $this->favorite_ids( $uid );

		if ( in_array( $company_id, $ids, true ) ) {
			$ids         = array_values( array_diff( $ids, array( $company_id ) ) );
			$is_favorite = false;
		} else {
			$ids[]       = $company_id;
			$is_favorite = true;
		}

		update_user_meta( $uid, self::FAVORITES_META, $ids );
		return array(
			'isFavorite' => $is_favorite,
			'companyIds' => $ids,
		);
	}

	/**
	 * Оставить отзыв — только при наличии подтверждённой брони в этой компании.
	 *
	 * @param array<string, mixed> $input Вход.
	 * @return array{id:int}
	 *
	 * @throws UserError Если нет состоявшейся брони/не авторизован.
	 */
	public function create_review( array $input ): array {
		$uid        = $this->require_user();
		$company_id = (int) ( $input['companyId'] ?? 0 );
		$rating     = max( 1, min( 5, (int) ( $input['rating'] ?? 0 ) ) );

		if ( ! $this->has_confirmed_booking( $uid, $company_id ) ) {
			throw new UserError( __( 'Отзыв доступен только после подтверждённой записи.', 'servicehub' ) );
		}

		$user      = wp_get_current_user();
		$review_id = wp_insert_post(
			array(
				'post_type'   => ReviewPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sprintf( 'Отзыв: %s', $user->display_name ),
			),
			true
		);
		if ( is_wp_error( $review_id ) ) {
			throw new UserError( __( 'Не удалось сохранить отзыв.', 'servicehub' ) );
		}

		update_post_meta( $review_id, 'review_company', $company_id );
		update_post_meta( $review_id, 'review_author', $user->display_name );
		update_post_meta( $review_id, 'review_rating', $rating );
		update_post_meta( $review_id, 'review_text', sanitize_textarea_field( (string) ( $input['text'] ?? '' ) ) );
		update_post_meta( $review_id, 'review_verified', 1 );

		// Перезапуск save_post с заполненной meta → пересчёт кэш-рейтинга в ReviewFields.
		wp_update_post( array( 'ID' => $review_id ) );

		return array( 'id' => (int) $review_id );
	}

	/**
	 * Есть ли у пользователя подтверждённая бронь в компании.
	 *
	 * @param int $uid        ID пользователя.
	 * @param int $company_id ID компании.
	 */
	private function has_confirmed_booking( int $uid, int $company_id ): bool {
		$query = new \WP_Query(
			array(
				'post_type'      => BookingPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'   => 'booking_user',
						'value' => $uid,
					),
					array(
						'key'   => 'booking_company',
						'value' => $company_id,
					),
					array(
						'key'   => 'booking_status',
						'value' => BookingPostType::STATUS_CONFIRMED,
					),
				),
			)
		);
		return ! empty( $query->posts );
	}

	/**
	 * Массив ID избранных компаний пользователя.
	 *
	 * @param int $uid ID пользователя.
	 * @return array<int, int>
	 */
	private function favorite_ids( int $uid ): array {
		$ids = get_user_meta( $uid, self::FAVORITES_META, true );
		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}
}
