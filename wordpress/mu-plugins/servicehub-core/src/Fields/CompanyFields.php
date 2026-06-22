<?php
/**
 * Поля компании: ACF для админки + проекция в GraphQL + фильтры каталога.
 *
 * GraphQL-поля читают post meta напрямую (декаплинг от ACF→GraphQL), а where-
 * аргументы city/category на коннекшене companies транслируются в tax_query.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Fields;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\Taxonomies\CatalogTaxonomies;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Контактные/гео-поля компании и фильтрация выдачи.
 */
final class CompanyFields implements Module {

	private const META_PHONE      = 'company_phone';
	private const META_ADDRESS    = 'company_address';
	private const META_LATITUDE   = 'company_latitude';
	private const META_LONGITUDE  = 'company_longitude';
	private const META_PRICE_FROM = 'company_price_from';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'graphql_register_types', array( $this, 'register_graphql_fields' ) );
		add_filter(
			'graphql_post_object_connection_query_args',
			array( $this, 'filter_connection_query_args' ),
			10,
			5
		);
	}

	/**
	 * ACF-группа полей компании (для редактирования в админке).
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_company_details',
				'title'    => __( 'Данные компании', 'servicehub' ),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => CompanyPostType::POST_TYPE,
						),
					),
				),
				'fields'   => array(
					$this->acf_field( self::META_PHONE, __( 'Телефон', 'servicehub' ), 'text' ),
					$this->acf_field( self::META_ADDRESS, __( 'Адрес', 'servicehub' ), 'text' ),
					$this->acf_field( self::META_LATITUDE, __( 'Широта', 'servicehub' ), 'number' ),
					$this->acf_field( self::META_LONGITUDE, __( 'Долгота', 'servicehub' ), 'number' ),
					$this->acf_field( self::META_PRICE_FROM, __( 'Цена от, ₽', 'servicehub' ), 'number' ),
				),
			)
		);
	}

	/**
	 * Описание одного ACF-поля.
	 *
	 * @param string $name  Имя/мета-ключ.
	 * @param string $label Подпись.
	 * @param string $type  Тип поля ACF.
	 * @return array<string, mixed>
	 */
	private function acf_field( string $name, string $label, string $type ): array {
		return array(
			'key'   => 'field_' . $name,
			'name'  => $name,
			'label' => $label,
			'type'  => $type,
		);
	}

	/**
	 * Зарегистрировать поля компании в схеме GraphQL (чтение из meta).
	 */
	public function register_graphql_fields(): void {
		if ( ! function_exists( 'register_graphql_fields' ) ) {
			return;
		}

		register_graphql_fields(
			'Company',
			array(
				'phone'     => $this->graphql_meta_string( self::META_PHONE, __( 'Телефон.', 'servicehub' ) ),
				'address'   => $this->graphql_meta_string( self::META_ADDRESS, __( 'Адрес.', 'servicehub' ) ),
				'latitude'  => $this->graphql_meta_float( self::META_LATITUDE, __( 'Широта.', 'servicehub' ) ),
				'longitude' => $this->graphql_meta_float( self::META_LONGITUDE, __( 'Долгота.', 'servicehub' ) ),
				'priceFrom' => $this->graphql_meta_float( self::META_PRICE_FROM, __( 'Цена от.', 'servicehub' ) ),
			)
		);

		// Аргументы фильтрации каталога на коннекшене companies.
		register_graphql_fields(
			'RootQueryToCompanyConnectionWhereArgs',
			array(
				'city'     => array(
					'type'        => 'String',
					'description' => __( 'Слаг города для фильтрации.', 'servicehub' ),
				),
				'category' => array(
					'type'        => 'String',
					'description' => __( 'Слаг категории для фильтрации.', 'servicehub' ),
				),
			)
		);
	}

	/**
	 * Дескриптор строкового GraphQL-поля из meta.
	 *
	 * @param string $meta_key    Мета-ключ.
	 * @param string $description Описание.
	 * @return array<string, mixed>
	 */
	private function graphql_meta_string( string $meta_key, string $description ): array {
		return array(
			'type'        => 'String',
			'description' => $description,
			'resolve'     => static function ( $source ) use ( $meta_key ) {
				$value = get_post_meta( self::source_id( $source ), $meta_key, true );
				return '' !== $value ? (string) $value : null;
			},
		);
	}

	/**
	 * Дескриптор числового GraphQL-поля из meta.
	 *
	 * @param string $meta_key    Мета-ключ.
	 * @param string $description Описание.
	 * @return array<string, mixed>
	 */
	private function graphql_meta_float( string $meta_key, string $description ): array {
		return array(
			'type'        => 'Float',
			'description' => $description,
			'resolve'     => static function ( $source ) use ( $meta_key ) {
				$value = get_post_meta( self::source_id( $source ), $meta_key, true );
				return '' !== $value ? (float) $value : null;
			},
		);
	}

	/**
	 * Достать ID записи из источника-резолвера.
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

	/**
	 * Транслировать where-аргументы city/category в tax_query.
	 *
	 * @param array<string, mixed> $query_args Аргументы WP_Query.
	 * @param mixed                $source     Источник коннекшена.
	 * @param array<string, mixed> $args       Аргументы GraphQL (включая where).
	 * @param mixed                $context    Контекст.
	 * @param mixed                $info       Сведения резолвера.
	 * @return array<string, mixed>
	 */
	public function filter_connection_query_args( array $query_args, $source, array $args, $context, $info ): array {
		$post_type = $query_args['post_type'] ?? '';
		$is_company = CompanyPostType::POST_TYPE === $post_type
			|| ( is_array( $post_type ) && in_array( CompanyPostType::POST_TYPE, $post_type, true ) );
		if ( ! $is_company ) {
			return $query_args;
		}

		$where    = $args['where'] ?? array();
		$tax_query = array();

		if ( ! empty( $where['city'] ) ) {
			$tax_query[] = array(
				'taxonomy' => CatalogTaxonomies::CITY,
				'field'    => 'slug',
				'terms'    => sanitize_title( (string) $where['city'] ),
			);
		}
		if ( ! empty( $where['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => CatalogTaxonomies::CATEGORY,
				'field'    => 'slug',
				'terms'    => sanitize_title( (string) $where['category'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$existing                 = $query_args['tax_query'] ?? array();
			$query_args['tax_query']  = array_merge( $existing, $tax_query );
		}

		return $query_args;
	}
}
