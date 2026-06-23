<?php
/**
 * Профильные поля компании: часы работы (repeater) и галерея.
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\Fields;

use ServiceHub\Contracts\Module;
use ServiceHub\PostTypes\CompanyPostType;
use WPGraphQL\Model\Post;

defined( 'ABSPATH' ) || exit;

/**
 * Часы работы и галерея для страницы профиля компании.
 */
final class CompanyProfileFields implements Module {

	private const FIELD_HOURS   = 'company_hours';
	private const FIELD_GALLERY = 'company_gallery';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'acf/init', array( $this, 'register_acf_fields' ) );
		add_action( 'graphql_register_types', array( $this, 'register_graphql_types' ) );
	}

	/**
	 * ACF: repeater часов работы + галерея.
	 */
	public function register_acf_fields(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_company_profile',
				'title'    => __( 'Профиль: часы и галерея', 'servicehub' ),
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
					array(
						'key'        => 'field_' . self::FIELD_HOURS,
						'name'       => self::FIELD_HOURS,
						'label'      => __( 'Часы работы', 'servicehub' ),
						'type'       => 'repeater',
						'layout'     => 'table',
						'sub_fields' => array(
							array(
								'key'     => 'field_hours_day',
								'name'    => 'day',
								'label'   => __( 'День', 'servicehub' ),
								'type'    => 'select',
								'choices' => array(
									'mon' => __( 'Пн', 'servicehub' ),
									'tue' => __( 'Вт', 'servicehub' ),
									'wed' => __( 'Ср', 'servicehub' ),
									'thu' => __( 'Чт', 'servicehub' ),
									'fri' => __( 'Пт', 'servicehub' ),
									'sat' => __( 'Сб', 'servicehub' ),
									'sun' => __( 'Вс', 'servicehub' ),
								),
							),
							array(
								'key'   => 'field_hours_open',
								'name'  => 'open_time',
								'label' => __( 'Открытие', 'servicehub' ),
								'type'  => 'text',
							),
							array(
								'key'   => 'field_hours_close',
								'name'  => 'close_time',
								'label' => __( 'Закрытие', 'servicehub' ),
								'type'  => 'text',
							),
						),
					),
					array(
						'key'           => 'field_' . self::FIELD_GALLERY,
						'name'          => self::FIELD_GALLERY,
						'label'         => __( 'Галерея', 'servicehub' ),
						'type'          => 'gallery',
						'return_format' => 'array',
					),
				),
			)
		);
	}

	/**
	 * GraphQL: типы CompanyHours/GalleryImage и поля hours/gallery на Company.
	 */
	public function register_graphql_types(): void {
		if ( ! function_exists( 'register_graphql_object_type' ) ) {
			return;
		}

		register_graphql_object_type(
			'CompanyHours',
			array(
				'description' => __( 'Часы работы на один день недели.', 'servicehub' ),
				'fields'      => array(
					'day'   => array( 'type' => 'String' ),
					'open'  => array( 'type' => 'String' ),
					'close' => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_object_type(
			'GalleryImage',
			array(
				'description' => __( 'Изображение галереи компании.', 'servicehub' ),
				'fields'      => array(
					'sourceUrl' => array( 'type' => 'String' ),
					'altText'   => array( 'type' => 'String' ),
				),
			)
		);

		register_graphql_field(
			'Company',
			'hours',
			array(
				'type'        => array( 'list_of' => 'CompanyHours' ),
				'description' => __( 'Часы работы по дням.', 'servicehub' ),
				'resolve'     => function ( $source ) {
					$rows = $this->get_field_value( self::FIELD_HOURS, self::source_id( $source ) );
					if ( ! is_array( $rows ) ) {
						return array();
					}
					return array_map(
						static fn ( $row ) => array(
							'day'   => $row['day'] ?? null,
							'open'  => $row['open_time'] ?? null,
							'close' => $row['close_time'] ?? null,
						),
						$rows
					);
				},
			)
		);

		register_graphql_field(
			'Company',
			'gallery',
			array(
				'type'        => array( 'list_of' => 'GalleryImage' ),
				'description' => __( 'Галерея изображений.', 'servicehub' ),
				'resolve'     => function ( $source ) {
					$images = $this->get_field_value( self::FIELD_GALLERY, self::source_id( $source ) );
					if ( ! is_array( $images ) ) {
						return array();
					}
					return array_map(
						static fn ( $img ) => array(
							'sourceUrl' => $img['url'] ?? null,
							'altText'   => $img['alt'] ?? null,
						),
						$images
					);
				},
			)
		);
	}

	/**
	 * Прочитать значение ACF-поля (если ACF доступен).
	 *
	 * @param string $field   Имя поля.
	 * @param int    $post_id ID записи.
	 * @return mixed
	 */
	private function get_field_value( string $field, int $post_id ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $field, $post_id );
		}
		return null;
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
