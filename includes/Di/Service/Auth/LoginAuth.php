<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Permission;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * LoginAuth class
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginAuth implements AuthInterface {
	use Permission;
	use AuthTrails;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * LoginAuth constructor.
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
			'/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
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
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function login( $request ) {
		$type = $request->get_param( 'type' );
		$role = $request->get_param( 'role' );

		// Validate role before process to next step.
		$request->set_param( 'role', $this->verify_role( $role ) );

		$login_instance = Login\LoginFactory::create( $type, $this->http_client );
		$user           = $login_instance->login( $request );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		return $this->pre_data_response( $user, $request, true );
	}

	/**
	 * Get args.
	 *
	 * @return array
	 */
	public function schema() {
		return array(
			'type'     => array(
				'required'    => false,
				'type'        => 'string',
				'default'     => 'email',
				'enum'        => array( 'email', 'facebook', 'google', 'apple', 'phone' ),
				'description' => 'Type of login',
			),
			'username' => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Username or email for login with email method',
			),
			'password' => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Password for login with email method',
			),
		);
	}
}
