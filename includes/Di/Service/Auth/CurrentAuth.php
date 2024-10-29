<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Permission;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * CurrentAuth class
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class CurrentAuth implements AuthInterface {
	use Permission;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * CurrentAuth constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Register rest route.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			'app-builder/v1',
			'/current',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_current_user' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/current',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_current_user' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
	}

	/**
	 * Get current user.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_current_user( WP_REST_Request $request ) {
		$user = wp_get_current_user();

		if ( empty( $user ) || 0 === $user->ID ) {
			return new WP_Error(
				'no_current_login',
				__( 'User not login.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		return apply_filters( APP_BUILDER_NAME . '_current', apply_filters( 'app_builder_prepare_userdata', $user ), $request );
	}
}
