<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use Wp_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * LoginEmail class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginEmail implements LoginInterface {
	/**
	 * Http client.
	 *
	 * @var HttpClientInterface
	 */
	protected $http_client;

	/**
	 * Constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Login action.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return Wp_User|WP_Error Response object.
	 */
	public function login( WP_REST_Request $request ) {
		// Username and password.
		$username = $request->get_param( 'username' );
		$password = $request->get_param( 'password' );

		$validate = apply_filters( 'app_builder_validate_form_data', true, $request, 'Login' );

		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		// try login with username and password.
		$user = wp_authenticate( $username, $password );

		// Return the errors to client.
		if ( is_wp_error( $user ) ) {
			return new WP_Error(
				'login_email_password_error',
				$user->get_error_message(),
				array(
					'status' => 403,
				)
			);
		}
		return $user;
	}
}
