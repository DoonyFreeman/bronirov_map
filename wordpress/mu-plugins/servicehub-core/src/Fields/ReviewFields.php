<?php
/**
 * Отзывы: ACF-поля, проекция в GraphQL, кэш-агрегаты рейтинга на компании.
 *
 * Средний рейтинг и число отзывов кэшируются в meta компании и пересчитываются
 * по хуку при сохранении/удалении отзыва — чтобы не агрегировать на каждый
 * GraphQL-запрос (защита от N+1).
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Fields;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ReviewPostType;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Поля отзыва, связь с компанией и кэш рейтинга.
 */
final class ReviewFields implements Module {

	private const META_COMPANY  = 'review_company';
	private const META_AUTHOR   = 'review_author';
	private const META_RATING   = 'review_rating';
	private const META_TEXT     = 'review_text';
	private const META_VERIFIED = 'review_verified';

	public const META_RATING_AVG = 'company_rating_avg';
	public const META_REVIEW_NUM = 'company_review_count';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'graphql_register_types', array( $this, 'register_graphql_fields' ) );

		// Пересчёт кэш-агрегатов рейтинга.
		add_action( 'save_post_' . ReviewPostType::POST_TYPE, array( $this, 'recompute_for_review' ), 20, 1 );
		add_action( 'trashed_post', array( $this, 'recompute_on_trash' ), 20, 1 );
		add_action( 'deleted_post', array( $this, 'recompute_on_trash' ), 20, 1 );
	}

	/**
	 * ACF-поля отзыва.
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_review_details',
				'title'    => __( 'Данные отзыва', 'servicehub' ),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => ReviewPostType::POST_TYPE,
						),
					),
				),
				'fields'   => array(
					array(
						'key'           => 'field_' . self::META_COMPANY,
						'name'          => self::META_COMPANY,
						'label'         => __( 'Компания', 'servicehub' ),
						'type'          => 'post_object',
						'post_type'     => array( CompanyPostType::POST_TYPE ),
						'return_format' => 'id',
					),
					array(
						'key'   => 'field_' . self::META_AUTHOR,
						'name'  => self::META_AUTHOR,
						'label' => __( 'Автор', 'servicehub' ),
						'type'  => 'text',
					),
					array(
						'key'   => 'field_' . self::META_RATING,
						'name'  => self::META_RATING,
						'label' => __( 'Оценка (1–5)', 'servicehub' ),
						'type'  => 'number',
						'min'   => 1,
						'max'   => 5,
					),
					array(
						'key'   => 'field_' . self::META_TEXT,
						'name'  => self::META_TEXT,
						'label' => __( 'Текст', 'servicehub' ),
						'type'  => 'textarea',
					),
					array(
						'key'   => 'field_' . self::META_VERIFIED,
						'name'  => self::META_VERIFIED,
						'label' => __( 'Подтверждён (после визита)', 'servicehub' ),
						'type'  => 'true_false',
					),
				),
			)
		);
	}

	/**
	 * Поля отзыва и кэш-рейтинг компании в GraphQL.
	 */
	public function register_graphql_fields(): void {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		register_graphql_field(
			'Review',
			'author',
			array(
				'type'        => 'String',
				'description' => __( 'Имя автора отзыва.', 'servicehub' ),
				'resolve'     => static fn ( $s ) => self::meta_string( $s, self::META_AUTHOR ),
			)
		);
		register_graphql_field(
			'Review',
			'rating',
			array(
				'type'        => 'Int',
				'description' => __( 'Оценка 1–5.', 'servicehub' ),
				'resolve'     => static function ( $s ) {
					$v = get_post_meta( self::source_id( $s ), self::META_RATING, true );
					return '' !== $v ? (int) $v : null;
				},
			)
		);
		register_graphql_field(
			'Review',
			'text',
			array(
				'type'        => 'String',
				'description' => __( 'Текст отзыва.', 'servicehub' ),
				'resolve'     => static fn ( $s ) => self::meta_string( $s, self::META_TEXT ),
			)
		);
		register_graphql_field(
			'Review',
			'verified',
			array(
				'type'        => 'Boolean',
				'description' => __( 'Отзыв подтверждён состоявшейся бронью.', 'servicehub' ),
				'resolve'     => static fn ( $s ) => (bool) get_post_meta( self::source_id( $s ), self::META_VERIFIED, true ),
			)
		);

		register_graphql_field(
			'Company',
			'averageRating',
			array(
				'type'        => 'Float',
				'description' => __( 'Средний рейтинг (кэш).', 'servicehub' ),
				'resolve'     => static function ( $s ) {
					$v = get_post_meta( self::source_id( $s ), self::META_RATING_AVG, true );
					return '' !== $v ? (float) $v : null;
				},
			)
		);
		register_graphql_field(
			'Company',
			'reviewCount',
			array(
				'type'        => 'Int',
				'description' => __( 'Число отзывов (кэш).', 'servicehub' ),
				'resolve'     => static fn ( $s ) => (int) get_post_meta( self::source_id( $s ), self::META_REVIEW_NUM, true ),
			)
		);

		register_graphql_field(
			'Company',
			'reviews',
			array(
				'type'        => array( 'list_of' => 'Review' ),
				'description' => __( 'Опубликованные отзывы компании.', 'servicehub' ),
				'resolve'     => static function ( $s ) {
					$query = new \WP_Query(
						array(
							'post_type'      => ReviewPostType::POST_TYPE,
							'post_status'    => 'publish',
							'posts_per_page' => 50,
							'orderby'        => 'date',
							'order'          => 'DESC',
							'meta_key'       => self::META_COMPANY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_value'     => self::source_id( $s ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						)
					);
					return array_map( static fn ( $p ) => new Post( $p ), $query->posts );
				},
			)
		);
	}

	/**
	 * Пересчитать рейтинг компании по сохранённому отзыву.
	 *
	 * @param int $review_id ID отзыва.
	 */
	public function recompute_for_review( int $review_id ): void {
		if ( wp_is_post_revision( $review_id ) || wp_is_post_autosave( $review_id ) ) {
			return;
		}
		$company_id = (int) get_post_meta( $review_id, self::META_COMPANY, true );
		if ( $company_id ) {
			$this->recompute_company_rating( $company_id );
		}
	}

	/**
	 * Пересчитать рейтинг при удалении/перемещении отзыва в корзину.
	 *
	 * @param int $post_id ID записи.
	 */
	public function recompute_on_trash( int $post_id ): void {
		if ( ReviewPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		$company_id = (int) get_post_meta( $post_id, self::META_COMPANY, true );
		if ( $company_id ) {
			$this->recompute_company_rating( $company_id );
		}
	}

	/**
	 * Посчитать средний рейтинг и число опубликованных отзывов компании.
	 *
	 * @param int $company_id ID компании.
	 */
	private function recompute_company_rating( int $company_id ): void {
		$query = new \WP_Query(
			array(
				'post_type'      => ReviewPostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => self::META_COMPANY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $company_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		$ratings = array();
		foreach ( $query->posts as $review_id ) {
			$rating = (int) get_post_meta( $review_id, self::META_RATING, true );
			if ( $rating > 0 ) {
				$ratings[] = $rating;
			}
		}

		$count = count( $ratings );
		$avg   = $count > 0 ? round( array_sum( $ratings ) / $count, 1 ) : 0;

		update_post_meta( $company_id, self::META_RATING_AVG, $avg );
		update_post_meta( $company_id, self::META_REVIEW_NUM, $count );
	}

	/**
	 * Строковое meta-значение или null.
	 *
	 * @param mixed  $source   Источник.
	 * @param string $meta_key Ключ.
	 */
	private static function meta_string( $source, string $meta_key ): ?string {
		$value = get_post_meta( self::source_id( $source ), $meta_key, true );
		return '' !== $value ? (string) $value : null;
	}

	/**
	 * ID записи из источника-резолвера.
	 *
	 * @param mixed $source Модель WPGraphQL или ID.
	 */
	private static function source_id( $source ): int {
		if ( $source instanceof Post ) {
			return (int) $source->databaseId;
		}
		if ( is_object( $source ) && isset( $source->databaseId ) ) {
			return (int) $source->databaseId;
		}
		return (int) $source;
	}
}
