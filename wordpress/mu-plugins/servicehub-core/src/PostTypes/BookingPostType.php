<?php
/**
 * CPT «Бронирование».
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\PostTypes;

use ServiceHub\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует тип записи booking. Приватный: публичная схема не отдаёт
 * чужие брони (доступ к своим — в Спринте 5 с авторизацией).
 */
final class BookingPostType implements Module {

	public const POST_TYPE = 'booking';

	public const STATUS_PENDING   = 'pending';
	public const STATUS_CONFIRMED = 'confirmed';
	public const STATUS_CANCELLED = 'cancelled';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Зарегистрировать CPT booking.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Брони', 'servicehub' ),
				'labels'              => array(
					'name'          => __( 'Брони', 'servicehub' ),
					'singular_name' => __( 'Бронь', 'servicehub' ),
				),
				// Приватный тип: список бронирований недоступен без авторизации.
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'menu_icon'           => 'dashicons-calendar-alt',
				'supports'            => array( 'title' ),
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Booking',
				'graphql_plural_name' => 'Bookings',
			)
		);
	}
}
