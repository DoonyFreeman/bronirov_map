<?php
/**
 * Поля услуги и её связь с компанией (ACF + GraphQL).
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Fields;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ServicePostType;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Цена/длительность услуги, связь service ↔ company, список услуг компании.
 */
final class ServiceFields implements Module {

	private const META_PRICE    = 'service_price';
	private const META_DURATION = 'service_duration';
	private const META_COMPANY  = 'service_company';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'graphql_register_types', array( $this, 'register_graphql_fields' ) );
	}

	/**
	 * ACF-поля услуги.
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_service_details',
				'title'    => __( 'Данные услуги', 'servicehub' ),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => ServicePostType::POST_TYPE,
						),
					),
				),
				'fields'   => array(
					array(
						'key'   => 'field_' . self::META_PRICE,
						'name'  => self::META_PRICE,
						'label' => __( 'Цена, ₽', 'servicehub' ),
						'type'  => 'number',
					),
					array(
						'key'   => 'field_' . self::META_DURATION,
						'name'  => self::META_DURATION,
						'label' => __( 'Длительность, мин', 'servicehub' ),
						'type'  => 'number',
					),
					array(
						'key'           => 'field_' . self::META_COMPANY,
						'name'          => self::META_COMPANY,
						'label'         => __( 'Компания', 'servicehub' ),
						'type'          => 'post_object',
						'post_type'     => array( CompanyPostType::POST_TYPE ),
						'return_format' => 'id',
					),
				),
			)
		);
	}

	/**
	 * Поля услуги и компании в GraphQL.
	 */
	public function register_graphql_fields(): void {
		if ( ! function_exists( 'register_graphql_field' ) ) {
			return;
		}

		register_graphql_field(
			'Service',
			'price',
			array(
				'type'        => 'Float',
				'description' => __( 'Цена услуги, ₽.', 'servicehub' ),
				'resolve'     => static function ( $source ) {
					$value = get_post_meta( self::source_id( $source ), self::META_PRICE, true );
					return '' !== $value ? (float) $value : null;
				},
			)
		);

		register_graphql_field(
			'Service',
			'duration',
			array(
				'type'        => 'Int',
				'description' => __( 'Длительность услуги, мин.', 'servicehub' ),
				'resolve'     => static function ( $source ) {
					$value = get_post_meta( self::source_id( $source ), self::META_DURATION, true );
					return '' !== $value ? (int) $value : null;
				},
			)
		);

		register_graphql_field(
			'Service',
			'company',
			array(
				'type'        => 'Company',
				'description' => __( 'Компания-владелец услуги.', 'servicehub' ),
				'resolve'     => static function ( $source ) {
					$company_id = (int) get_post_meta( self::source_id( $source ), self::META_COMPANY, true );
					$company    = $company_id ? get_post( $company_id ) : null;
					return $company ? new Post( $company ) : null;
				},
			)
		);

		register_graphql_field(
			'Company',
			'services',
			array(
				'type'        => array( 'list_of' => 'Service' ),
				'description' => __( 'Услуги компании.', 'servicehub' ),
				'resolve'     => static function ( $source ) {
					$company_id = self::source_id( $source );
					$query      = new \WP_Query(
						array(
							'post_type'      => ServicePostType::POST_TYPE,
							'post_status'    => 'publish',
							'posts_per_page' => 50,
							'orderby'        => 'menu_order title',
							'order'          => 'ASC',
							'meta_key'       => self::META_COMPANY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_value'     => $company_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						)
					);
					return array_map(
						static fn ( $post ) => new Post( $post ),
						$query->posts
					);
				},
			)
		);
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
