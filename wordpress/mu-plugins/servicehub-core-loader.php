<?php
/**
 * Plugin Name:  ServiceHub Core (loader)
 * Description:  Загрузчик основного плагина ServiceHub. mu-plugins не
 *               автозагружают файлы из подпапок, поэтому подключаем вручную.
 * Version:      0.1.0
 * Author:       ServiceHub
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/servicehub-core/servicehub-core.php';
