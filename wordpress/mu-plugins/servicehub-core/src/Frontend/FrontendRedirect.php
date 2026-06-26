<?php
/**
 * Редирект публичных URL WordPress на headless-фронтенд (Next.js).
 *
 * WP — только бэкенд; его собственные страницы не используются. Любой
 * фронт-запрос (включая «Visit Site») уводим на Next. Админка/REST/GraphQL
 * не затрагиваются — template_redirect срабатывает только для фронт-рендера.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Frontend;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Уводит фронт WordPress на адрес фронтенда.
 */
final class FrontendRedirect implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_frontend_host' ) );
	}

	/**
	 * Перенаправить текущий фронт-запрос на Next.
	 */
	public function redirect(): void {
		$base = $this->frontend_url();

		$target = is_singular( CompanyPostType::POST_TYPE )
			? $base . '/company/' . get_post_field( 'post_name', get_queried_object_id() )
			: $base;

		wp_safe_redirect( $target, 302 ); // ponytail: 302 для разработки, 301 в проде.
		exit;
	}

	/**
	 * Разрешить хост фронтенда для wp_safe_redirect.
	 *
	 * @param array<int, string> $hosts Разрешённые хосты.
	 * @return array<int, string>
	 */
	public function allow_frontend_host( array $hosts ): array {
		$host = wp_parse_url( $this->frontend_url(), PHP_URL_HOST );
		if ( $host ) {
			$hosts[] = $host;
		}
		return $hosts;
	}

	/**
	 * Базовый URL фронтенда из окружения.
	 */
	private function frontend_url(): string {
		$url = getenv( 'FRONTEND_URL' );
		return rtrim( $url ? $url : 'http://localhost:3000', '/' );
	}
}
