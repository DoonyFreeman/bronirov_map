<?php
/**
 * Таксономии каталога: категория услуги, город, страна.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Taxonomies;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use ServiceHub\PostTypes\ServicePostType;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует таксономии и привязывает их к CPT.
 *
 * Ключ категории — service_category (не встроенный category), чтобы не
 * конфликтовать со стандартной таксономией WordPress.
 */
final class CatalogTaxonomies implements Module {

	public const CATEGORY = 'service_category';
	public const CITY     = 'city';
	public const COUNTRY  = 'country';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Зарегистрировать все таксономии каталога.
	 */
	public function register_taxonomies(): void {
		$company = CompanyPostType::POST_TYPE;
		$service = ServicePostType::POST_TYPE;

		register_taxonomy(
			self::CATEGORY,
			array( $company, $service ),
			$this->args(
				__( 'Категории', 'servicehub' ),
				__( 'Категория', 'servicehub' ),
				'ServiceCategory',
				'ServiceCategories',
				'category'
			)
		);

		register_taxonomy(
			self::CITY,
			array( $company ),
			$this->args(
				__( 'Города', 'servicehub' ),
				__( 'Город', 'servicehub' ),
				'City',
				'Cities',
				'city'
			)
		);

		register_taxonomy(
			self::COUNTRY,
			array( $company ),
			$this->args(
				__( 'Страны', 'servicehub' ),
				__( 'Страна', 'servicehub' ),
				'Country',
				'Countries',
				'country'
			)
		);
	}

	/**
	 * Собрать аргументы register_taxonomy с настройками GraphQL.
	 *
	 * @param string $plural        Множественная подпись.
	 * @param string $singular      Единственная подпись.
	 * @param string $graphql_one   Имя типа GraphQL (единственное).
	 * @param string $graphql_many  Имя типа GraphQL (множественное).
	 * @param string $slug          URL-слаг.
	 * @return array<string, mixed>
	 */
	private function args(
		string $plural,
		string $singular,
		string $graphql_one,
		string $graphql_many,
		string $slug
	): array {
		return array(
			'label'               => $plural,
			'labels'              => array(
				'name'          => $plural,
				'singular_name' => $singular,
			),
			'public'              => true,
			'hierarchical'        => true,
			'show_admin_column'   => true,
			'show_in_rest'        => true,
			'rewrite'             => array( 'slug' => $slug ),
			'show_in_graphql'     => true,
			'graphql_single_name' => $graphql_one,
			'graphql_plural_name' => $graphql_many,
		);
	}
}
