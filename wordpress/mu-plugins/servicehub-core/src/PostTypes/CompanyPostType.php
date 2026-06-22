<?php
/**
 * CPT «Компания».
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\PostTypes;

use ServiceHub\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует тип записи company и открывает его в WPGraphQL.
 */
final class CompanyPostType implements Module {

	public const POST_TYPE = 'company';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Зарегистрировать CPT company.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Компании', 'servicehub' ),
				'labels'              => array(
					'name'          => __( 'Компании', 'servicehub' ),
					'singular_name' => __( 'Компания', 'servicehub' ),
					'add_new_item'  => __( 'Добавить компанию', 'servicehub' ),
					'edit_item'     => __( 'Редактировать компанию', 'servicehub' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'menu_icon'           => 'dashicons-store',
				'rewrite'             => array( 'slug' => 'company' ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'exclude_from_search' => false,
				'show_in_rest'        => true,
				// WPGraphQL.
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Company',
				'graphql_plural_name' => 'Companies',
			)
		);
	}
}
