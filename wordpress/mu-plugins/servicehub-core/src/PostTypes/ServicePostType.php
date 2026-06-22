<?php
/**
 * CPT «Услуга».
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\PostTypes;

use ServiceHub\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует тип записи service и открывает его в WPGraphQL.
 */
final class ServicePostType implements Module {

	public const POST_TYPE = 'service';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Зарегистрировать CPT service.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Услуги', 'servicehub' ),
				'labels'              => array(
					'name'          => __( 'Услуги', 'servicehub' ),
					'singular_name' => __( 'Услуга', 'servicehub' ),
					'add_new_item'  => __( 'Добавить услугу', 'servicehub' ),
					'edit_item'     => __( 'Редактировать услугу', 'servicehub' ),
				),
				'public'              => true,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-clipboard',
				'rewrite'             => array( 'slug' => 'service' ),
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Service',
				'graphql_plural_name' => 'Services',
			)
		);
	}
}
