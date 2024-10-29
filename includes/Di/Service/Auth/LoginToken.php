<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Permission;
use WP_REST_Server;
use WP_REST_Request;

/**
 * The LoginToken class is responsible for handling the login token by rest api then redirect to checkout page.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginToken implements AuthInterface {
	use Permission;
	use AuthTrails;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * LoginToken constructor.
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
			'/login-token',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/login-token',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
	}

	/**
	 * Login callback.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function login( $request ) {
		$body_class_key = defined( 'APP_BUILDER_CHECKOUT_BODY_CLASS' ) ? APP_BUILDER_CHECKOUT_BODY_CLASS : 'app-builder-checkout-body-class';

		$redirect = $request->get_param( 'redirect' );
		$url      = wc_get_checkout_url();
		if ( $redirect ) {
			$url = $redirect;
		}

		// Add theme, currency, lang, body class to checkout.
		$url = add_query_arg(
			array(
				'theme'         => $request->get_param( 'theme' ),
				'currency'      => $request->get_param( 'currency' ),
				'lang'          => $request->get_param( '_lang' ),
				$body_class_key => $request->get_param( $body_class_key ),
			),
			$url
		);

		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id );
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Get args.
	 *
	 * @return array
	 */
	public function schema() {
		return array(
			'theme'                           => array(
				'description' => __( 'Theme', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
			'currency'                        => array(
				'description' => __( 'Currency', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
			'_lang'                           => array(
				'description' => __( 'Language', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
			'redirect'                        => array(
				'description' => __( 'Redirect', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
			'app-builder-checkout-body-class' => array(
				'description' => __( 'Body class', 'app-builder' ),
				'type'        => 'string',
				'required'    => false,
			),
		);
	}
}
