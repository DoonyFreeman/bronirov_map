<?php
/**
 * Контракт модуля плагина.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Любая логическая часть плагина (контент-модель, GraphQL, уведомления…)
 * реализует этот интерфейс и подключает свои хуки в register().
 */
interface Module {

	/**
	 * Зарегистрировать хуки/действия модуля в WordPress.
	 */
	public function register(): void;
}
