<?php
/**
 * Главный класс плагина: точка сборки модулей.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub;

defined( 'ABSPATH' ) || exit;

/**
 * Загружает и инициализирует модули платформы.
 *
 * Модули добавляются по мере роста (Спринт 1: ContentModel; Спринт 3:
 * Availability; Спринт 4: Notifications и т.д.). Каждый модуль реализует
 * Contracts\Module и регистрирует свои хуки в register().
 */
final class Plugin {

	/**
	 * Список классов модулей в порядке инициализации.
	 *
	 * @var array<int, class-string<Contracts\Module>>
	 */
	private array $modules = array(
		Graphql\SmokeField::class,
	);

	/**
	 * Подключить все модули к хукам WordPress.
	 */
	public function boot(): void {
		foreach ( $this->modules as $module_class ) {
			$module = new $module_class();
			if ( $module instanceof Contracts\Module ) {
				$module->register();
			}
		}
	}
}
