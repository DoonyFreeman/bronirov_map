<?php
/**
 * ServiceHub Core — основной плагин платформы.
 *
 * Регистрирует контент-модель (CPT/таксономии), расширения схемы WPGraphQL,
 * сервис-слой доступности, уведомления и вебхуки ревалидации.
 *
 * На Спринте 0 — только каркас и smoke-поле в GraphQL для проверки загрузки.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'SERVICEHUB_VERSION', '0.1.0' );
define( 'SERVICEHUB_DIR', __DIR__ );

/**
 * Простой PSR-4-автозагрузчик для неймспейса ServiceHub\ → src/.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'ServiceHub\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$path     = SERVICEHUB_DIR . '/src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

// Старт плагина.
( new ServiceHub\Plugin() )->boot();
