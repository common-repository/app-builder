<?php


/**
 * class Search
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 */

namespace AppBuilder\Api;

defined( 'ABSPATH' ) || exit;

class Search extends Base {
	public function __construct() {
		$this->namespace = APP_BUILDER_REST_BASE . '/v1';
	}

	/**
	 * Registers a REST API route
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		/**
		 * search
		 *
		 * @author Ngoc Dang
		 * @since 1.0.0
		 */
		register_rest_route(
			$this->namespace,
			'search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search' ),
				'permission_callback' => array( $this, 'admin_permissions_check' ),
			)
		);

		/**
		 * Post search
		 *
		 * @author Ngoc Dang
		 * @since 1.0.0
		 */
		register_rest_route(
			$this->namespace,
			'post-search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'post_search' ),
				'permission_callback' => '__return_true',
			)
		);

		/**
		 * Term search
		 *
		 * @author Ngoc Dang
		 * @since 1.0.0
		 */
		register_rest_route(
			$this->namespace,
			'term-search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'term_search' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Search
	 *
	 * @param \WP_REST_Request $request request.
	 *
	 * @since 1.0.0
	 * @author Ngoc Dang
	 *
	 * @return array
	 */
	public function search( $request ) {
		$posts = $this->post_search( $request );
		$terms = $this->term_search( $request );
		$users = $this->user_search( $request );

		$results = array();

		array_push( $results, ...$posts );

		if ( ! is_wp_error( $terms ) ) {
			array_push( $results, ...$terms );
		}

		if ( ! is_wp_error( $users ) ) {
			array_push( $results, ...$users );
		}

		return $results;
	}

	/**
	 * @param $request
	 *
	 * @return array
	 * @since 1.0.0
	 * @author Ngoc Dang
	 */
	public function post_search( $request ): array {

		// Post
		$search    = $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '';
		$limit     = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 10;
		$post_type = is_array( $request->get_param( 'post_type' ) ) ? $request->get_param( 'post_type' ) : array();

		if ( count( $post_type ) == 0 ) {
			return array();
		}

		$return = array();
		$args   = array(
			's'              => $search,
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'post_type'      => $post_type,
		);
		$query  = new \WP_Query( $args );

		$posts = $query->get_posts();

		foreach ( $posts as $post ) {
			$newPost = array();

			$newPost['id']    = $post->ID;
			$newPost['title'] = $post->post_title;
			$newPost['type']  = $post->post_type;

			$return[] = $newPost;
		}

		return $return;
	}

	/**
	 *
	 * Search Terms
	 *
	 * @param $request
	 *
	 * @return int[]|string|string[]|WP_Error|WP_Term[]
	 * @author Ngoc Dang
	 * @since 1.0.0
	 */
	public function term_search( $request ) {

		$search   = $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '';
		$taxonomy = is_array( $request->get_param( 'taxonomy' ) ) ? $request->get_param( 'taxonomy' ) : array();

		if ( count( $taxonomy ) == 0 ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'search'     => $search,
			)
		);

		$return = array();

		foreach ( $terms as $term ) {
			$newTerm = array();

			$newTerm['id']    = $term->term_id;
			$newTerm['title'] = $term->name;
			$newTerm['type']  = $term->taxonomy;

			$return[] = $newTerm;
		}

		return $return;
	}

	/**
	 * Search Users
	 *
	 * @param \WP_REST_Request $request request.
	 *
	 * @return array
	 */
	public function user_search( $request ) {
		$search = $request->get_param( 'search' ) ? $request->get_param( 'search' ) : '';
		$limit  = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 10;
		$roles  = $request->get_param( 'roles' ) ? $request->get_param( 'roles' ) : array();
		$type   = $request->get_param( 'type' ) ? $request->get_param( 'type' ) : 'user';

		if ( 0 === count( $roles ) ) {
			return array();
		}

		$users = get_users(
			array(
				'role__in'       => $roles,
				'search'         => '*' . $search . '*',
				'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'user_url' ),
				'number'         => $limit,
			)
		);

		$return = array();

		foreach ( $users as $user ) {
			$new_user = array();

			$new_user['id']    = $user->ID;
			$new_user['title'] = "{$user->display_name} ({$user->user_email}) ({$user->user_login})";
			$new_user['type']  = $type;

			$return[] = $new_user;
		}

		return $return;
	}
}
