<?php
/**
 * GraphQL-поле availableSlots и проекция полей брони.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Graphql;

use ServiceHub\Booking\AvailabilityService;
use ServiceHub\Contracts\Module;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Запрос доступных слотов и читаемые поля брони.
 */
final class AvailabilityField implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'graphql_register_types', array( $this, 'register_types' ) );
	}

	/**
	 * Зарегистрировать поле availableSlots и поля Booking.
	 */
	public function register_types(): void {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		register_graphql_field(
			'RootQuery',
			'availableSlots',
			array(
				'type'        => array( 'list_of' => 'String' ),
				'description' => __( 'Свободные времена записи на услугу в дату (Y-m-d).', 'servicehub' ),
				'args'        => array(
					'serviceId' => array( 'type' => array( 'non_null' => 'ID' ) ),
					'date'      => array( 'type' => array( 'non_null' => 'String' ) ),
				),
				'resolve'     => static function ( $root, array $args ) {
					$service = new AvailabilityService();
					return $service->available_slots( (int) $args['serviceId'], (string) $args['date'] );
				},
			)
		);

		// Читаемые поля брони (используются в payload мутации createBooking).
		$meta_fields = array(
			'bookingDate'   => array( 'booking_date', 'String', __( 'Дата записи.', 'servicehub' ) ),
			'bookingTime'   => array( 'booking_time', 'String', __( 'Время записи.', 'servicehub' ) ),
			'bookingStatus' => array( 'booking_status', 'String', __( 'Статус брони.', 'servicehub' ) ),
			'clientName'    => array( 'booking_client_name', 'String', __( 'Имя клиента.', 'servicehub' ) ),
		);
		foreach ( $meta_fields as $field => [$meta_key, $type, $description] ) {
			register_graphql_field(
				'Booking',
				$field,
				array(
					'type'        => $type,
					'description' => $description,
					'resolve'     => static function ( $source ) use ( $meta_key ) {
						$id = $source instanceof Post ? (int) $source->databaseId : (int) $source;
						$v  = get_post_meta( $id, $meta_key, true );
						return '' !== $v ? (string) $v : null;
					},
				)
			);
		}
	}
}
