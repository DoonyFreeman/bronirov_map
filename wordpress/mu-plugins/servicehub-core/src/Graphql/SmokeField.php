<?php
/**
 * Smoke-поле GraphQL — подтверждает, что плагин загрузился и расширяет схему.
 *
 * Запрос:  { serviceHubInfo { version } }
 * Заменится реальными типами в Спринте 1.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Graphql;

use ServiceHub\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует объектный тип ServiceHubInfo и корневое поле serviceHubInfo.
 */
final class SmokeField implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// WPGraphQL может быть ещё не активен на самой ранней загрузке —
		// действие graphql_register_types сработает только при его наличии.
		add_action( 'graphql_register_types', array( $this, 'register_types' ) );
	}

	/**
	 * Зарегистрировать тип и поле в схеме.
	 */
	public function register_types(): void {
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}

		register_graphql_object_type(
			'ServiceHubInfo',
			array(
				'description' => __( 'Служебная информация платформы ServiceHub.', 'servicehub' ),
				'fields'      => array(
					'version' => array(
						'type'        => 'String',
						'description' => __( 'Версия плагина servicehub-core.', 'servicehub' ),
					),
				),
			)
		);

		register_graphql_field(
			'RootQuery',
			'serviceHubInfo',
			array(
				'type'        => 'ServiceHubInfo',
				'description' => __( 'Метаданные платформы ServiceHub.', 'servicehub' ),
				'resolve'     => static function (): array {
					return array( 'version' => defined( 'SERVICEHUB_VERSION' ) ? SERVICEHUB_VERSION : 'unknown' );
				},
			)
		);
	}
}
