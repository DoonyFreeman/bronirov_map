<?php
/**
 * CPT «Отзыв».
 *
 * @package ServiceHub
 */

declare( strict_types=1 );

namespace ServiceHub\PostTypes;

use ServiceHub\Contracts\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Регистрирует тип записи review и открывает его в WPGraphQL.
 */
final class ReviewPostType implements Module {

	public const POST_TYPE = 'review';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Зарегистрировать CPT review.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => __( 'Отзывы', 'servicehub' ),
				'labels'              => array(
					'name'          => __( 'Отзывы', 'servicehub' ),
					'singular_name' => __( 'Отзыв', 'servicehub' ),
				),
				// Публично читаемый в API, но без своих страниц/архива/поиска.
				'public'              => true,
				'publicly_queryable'  => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'menu_icon'           => 'dashicons-star-filled',
				'supports'            => array( 'title', 'editor' ),
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Review',
				'graphql_plural_name' => 'Reviews',
			)
		);
	}
}
