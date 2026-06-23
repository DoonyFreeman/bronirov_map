<?php
/**
 * Вебхук ревалидации: WordPress → Next.js (revalidateTag).
 *
 * При сохранении/смене статуса компании отправляем POST на эндпоинт Next с
 * секретом и списком тегов кэша. Адрес и секрет берём из переменных окружения
 * (заданы в docker-compose для контейнера wordpress).
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Revalidation;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ReviewPostType;
use ServiceHub\PostTypes\ServicePostType;

defined( 'ABSPATH' ) || exit;

/**
 * Дёргает Next для адресной инвалидации кэша по тегам.
 */
final class RevalidationWebhook implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'save_post_' . CompanyPostType::POST_TYPE, array( $this, 'on_company_saved' ), 10, 1 );
		add_action( 'trashed_post', array( $this, 'on_post_changed' ), 10, 1 );

		// Изменение услуги/отзыва ревалидирует страницу их компании.
		add_action( 'save_post_' . ServicePostType::POST_TYPE, array( $this, 'on_related_saved' ), 30, 1 );
		add_action( 'save_post_' . ReviewPostType::POST_TYPE, array( $this, 'on_related_saved' ), 30, 1 );
	}

	/**
	 * Сохранение компании → ревалидируем список и саму компанию.
	 *
	 * @param int $post_id ID записи.
	 */
	public function on_company_saved( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$this->revalidate( array( 'companies:list', 'company:' . $post_id ) );
	}

	/**
	 * Удаление записи компании → ревалидируем список.
	 *
	 * @param int $post_id ID записи.
	 */
	public function on_post_changed( int $post_id ): void {
		if ( CompanyPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		$this->revalidate( array( 'companies:list', 'company:' . $post_id ) );
	}

	/**
	 * Сохранение услуги/отзыва → ревалидируем страницу связанной компании.
	 *
	 * @param int $post_id ID записи (service или review).
	 */
	public function on_related_saved( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$meta_key   = ServicePostType::POST_TYPE === get_post_type( $post_id )
			? 'service_company'
			: 'review_company';
		$company_id = (int) get_post_meta( $post_id, $meta_key, true );
		if ( $company_id ) {
			$this->revalidate( array( 'companies:list', 'company:' . $company_id ) );
		}
	}

	/**
	 * Отправить теги на эндпоинт ревалидации Next (неблокирующе).
	 *
	 * @param array<int, string> $tags Теги кэша.
	 */
	private function revalidate( array $tags ): void {
		$endpoint = getenv( 'NEXT_REVALIDATE_URL' );
		$secret   = getenv( 'REVALIDATE_SECRET' );
		if ( empty( $endpoint ) || empty( $secret ) ) {
			return;
		}

		wp_remote_post(
			$endpoint,
			array(
				'timeout'  => 2,
				'blocking' => false,
				'headers'  => array(
					'Content-Type'        => 'application/json',
					'x-revalidate-secret' => $secret,
				),
				'body'     => wp_json_encode( array( 'tags' => $tags ) ),
			)
		);
	}
}
