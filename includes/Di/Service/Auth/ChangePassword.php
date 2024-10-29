<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class ChangePassword
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class ChangePassword {
	use \AppBuilder\Traits\Permission;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * ChangePassword constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Register rest route.
	 */
	public function register_rest_route() {
		register_rest_route(
			'app-builder/v1',
			'/change-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'change_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/change-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'change_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
	}

	/**
	 * Change password.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function change_password( WP_REST_Request $request ) {
		/**
		 * Verify user is login.
		 */
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return new WP_Error(
				'user_not_login',
				__( 'Please login first.', 'mobile-builder' ),
				array(
					'status' => 404,
				)
			);
		}

		$username     = $current_user->user_login;
		$password_old = $request->get_param( 'password_old' );
		$password_new = $request->get_param( 'password_new' );

		// try login with username and password.
		$user = wp_authenticate( $username, $password_old );

		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();

			return new WP_Error(
				$error_code,
				$user->get_error_message( $error_code ),
				array(
					'status' => 404,
				)
			);
		}

		// Change password.
		wp_set_password( $password_new, $current_user->ID );

		return rest_ensure_response( $current_user->ID );
	}
}
