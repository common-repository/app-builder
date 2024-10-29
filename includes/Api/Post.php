<?php

/**
 * class Post
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     AppCheap <ngocdt@rnlab.io>
 */

namespace AppBuilder\Api;

defined( 'ABSPATH' ) || exit;

use AppBuilder\Utils;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class Post extends Base {

	public function __construct() {
		$this->namespace = APP_BUILDER_REST_BASE . '/v1';
	}

	/**
	 * Registers a REST API route
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		$postTypes = defined( 'APP_BUILDER_POST_TYPES' ) ? unserialize( APP_BUILDER_POST_TYPES ) : array( 'post' );

		/**
		 * Get recursion category
		 *
		 * @author Ngoc Dang
		 * @since 1.0.0
		 */
		register_rest_route(
			$this->namespace,
			'archives',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'archives' ),
				'permission_callback' => '__return_true',
			)
		);

		foreach ( $postTypes as $value ) {
			add_filter( 'rest_prepare_' . $value, array( $this, 'rest_prepare_post' ), 10, 3 );
		}

		add_filter( 'rest_pre_insert_app_builder_template', array( $this, 'prepare_create_template' ), 10, 2 );
		add_filter( 'rest_prepare_app_builder_template', array( $this, 'prepare_response_template' ), 10, 3 );

		add_filter( 'rest_pre_insert_app_builder_preset', array( $this, 'prepare_create_preset' ), 10, 2 );
		add_filter( 'rest_prepare_app_builder_preset', array( $this, 'prepare_response_preset' ), 10, 3 );
		add_filter( 'pre_get_posts', array( $this, 'filter_by_tag' ), 10, 3 );
		$this->get_blocks();
	}

	/**
	 * Get Gutenberg blocks
	 */
	public function get_blocks() {

		if ( is_admin() ) {
			return;
		}

		if ( ! function_exists( 'use_block_editor_for_post_type' ) ) {
			require ABSPATH . 'wp-admin/includes/post.php';
		}

		// Surface all Gutenberg blocks in the WordPress REST API
		$post_types = get_post_types_by_support( array( 'editor' ) );
		foreach ( $post_types as $post_type ) {
			if ( use_block_editor_for_post_type( $post_type ) ) {
				register_rest_field(
					$post_type,
					'blocks',
					array(
						'get_callback' => function ( array $post ) {
							return parse_blocks( $post['content']['raw'] );
						},
					)
				);
			}
		}
	}

	/**
	 * Get archives
	 *
	 * @param WP_Rest_Request $request Request data.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function archives( WP_Rest_Request $request ) {
		global $wpdb;

		$params   = $request->get_params();
		$defaults = array(
			'type'      => 'monthly',
			'order'     => 'DESC',
			'post_type' => 'post',
		);
		$r        = wp_parse_args( $params, $defaults );

		$key      = 'app_builder_archives_' . md5( wp_json_encode( $r ) );
		$archives = wp_cache_get( $key, 'app_builder' );
		if ( false !== $archives && count( $archives ) > 0 ) {
			return rest_ensure_response( $archives );
		}

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC",
				$r['post_type'],
			)
		);
		wp_cache_set( $key, $results, 'app_builder', 3600 );

		return rest_ensure_response( $results );
	}

	/**
	 * Pre post response
	 *
	 * @param $terms
	 *
	 * @return array
	 */
	public function prepare_response( $terms ): array {
		return array_map(
			function ( $item ) {

				$data = array();

				$data['id']          = (int) $item->term_id;
				$data['count']       = (int) $item->count;
				$data['description'] = $item->description;
				$data['name']        = $item->name;
				$data['slug']        = $item->slug;
				$data['parent']      = (int) $item->parent;

				return $data;
			},
			$terms
		);
	}

	public function rest_prepare_post( \WP_REST_Response $response, \WP_Post $post, \WP_REST_Request $request ): \WP_REST_Response {

		/**
		 * Get taxonomy for post category
		 */
		$taxonomy = $request->get_param( 'taxonomy' );

		$tags       = get_the_tags( $post->ID );
		$categories = get_the_terms( $post->ID, $taxonomy ?: 'category' );
		$author     = get_the_author_meta( 'display_name', $post->post_author );

		$data = $response->get_data();

		// Category
		$data['post_categories'] = $categories ? $this->prepare_response( $categories ) : array();

		// Tags
		$data['post_tags'] = $tags ? $this->prepare_response( $tags ) : array();

		// Author
		$data['post_author'] = $author ?: '';

		// Avatar
		$data['post_author_avatar_urls'] = apply_filters( 'app_builder_prepare_avatar_data', $post->post_author, array() );

		// Count comment
		$data['post_comment_count'] = (int) get_comments_number( $post );

		// Image
		$data['image']        = get_the_post_thumbnail_url( $post );
		$data['thumb']        = get_the_post_thumbnail_url( $post, 'thumbnail' );
		$data['thumb_medium'] = get_the_post_thumbnail_url( $post, 'medium' );

		// Get all thumbs size
		$sizes = wp_get_registered_image_subsizes();
		foreach ( $sizes as $size => $value ) {
			$data['images'][ $size ] = get_the_post_thumbnail_url( $post, $size );
		}

		// Post title
		$data['post_title'] = htmlspecialchars_decode( $post->post_title );

		$response->set_data( apply_filters( 'app_builder_prepare_post_object', $data ) );

		return $response;
	}

	/**
	 *
	 * Update data before save preset
	 *
	 * @param $prepared_post
	 * @param $request
	 *
	 * @return mixed
	 */
	public function prepare_create_template( $prepared_post, $request ) {
		if ( isset( $request['content'] ) && is_array( $request['content'] ) ) {
			$prepared_post->post_content = wp_json_encode( $request['content'] );
		}

		return $prepared_post;
	}

	/**
	 *
	 * Update data before get preset
	 *
	 * @param $response
	 * @param $post
	 * @param $request
	 *
	 * @return mixed|void
	 */
	public function prepare_response_template( $response, $post, $request ) {
		$data = $response->get_data();
		return array(
			'id'          => $data['id'],
			'image'       => get_the_post_thumbnail_url( $post ),
			'data'        => $post->post_content,
			'name'        => $post->post_title,
			'status'      => $post->post_status,
			'ping_status' => $post->ping_status,
			'tags'        => get_the_terms( $post->id, 'app_builder_template_tag' ),
			'modified'    => $post->post_modified ? date_i18n( 'Y-m-d G:i:s', strtotime( $post->post_modified ) ) : '',
		);
	}

	/**
	 *
	 * Update data before save preset
	 *
	 * @param $prepared_post
	 * @param $request
	 *
	 * @return mixed
	 */
	public function prepare_create_preset( $prepared_post, $request ) {
		if ( isset( $request['content'] ) && is_array( $request['content'] ) ) {
			$prepared_post->post_content = wp_json_encode( $request['content'] );
		}

		return $prepared_post;
	}

	/**
	 *
	 * Update data before get template
	 *
	 * @param $response
	 * @param $post
	 * @param $request
	 *
	 * @return mixed|void
	 */
	public function prepare_response_preset( $response, $post, $request ) {

		$response->data['image']       = get_the_post_thumbnail_url( $post );
		$response->data['data']        = json_decode( $post->post_content );
		$response->data['name']        = $post->post_title;
		$response->data['status']      = $post->post_status;
		$response->data['ping_status'] = $post->ping_status;
		$response->data['tags']        = get_the_terms( $post->id, 'app_builder_preset_tag' );

		return $response;
	}

	public function filter_by_tag( $query ) {

		$screen = isset( $_GET['screen'] ) ? sanitize_text_field( wp_unslash( $_GET['screen'] ) ) : 'app-builder'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( is_admin() || $query->get( 'post_type' ) !== 'app_builder_preset' ) {
			return $query;
		}

		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'app_builder_preset_tag',
					'field'    => 'slug',
					'terms'    => $screen,
				),
			)
		);

		return $query;
	}
}
