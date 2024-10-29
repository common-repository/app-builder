<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Permission;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * RegisterAuth class
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class RegisterAuth implements AuthInterface {
	use Permission;
	use AuthTrails;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * RegisterAuth constructor.
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
			'/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/register-phone-number',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/register-phone-number',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema(),
			)
		);
	}

	/**
	 * Register action.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function register( WP_REST_Request $request ) {

		$validate = apply_filters( 'app_builder_validate_form_data', true, $request, 'Register' );
		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		$type                = $request->get_param( 'type' );
		$role                = $request->get_param( 'role' );
		$enable_phone_number = $request->get_param( 'enable_phone_number' );
		$subscribe           = (bool) $request->get_param( 'subscribe' );
		$agree_privacy_term  = (bool) $request->get_param( 'agree_privacy_term' );

		// Validate role before process to next step.
		$request->set_param( 'role', $this->verify_role( $role ) );

		if ( $enable_phone_number ) {
			$type = 'phone_number';
		}

		// Check route.
		if ( '/register-phone-number' === $request->get_route() || '/auth/register-phone-number' === $request->get_route() ) {
			$type = 'phone_number';
		}

		$register_instance = Register\RegisterFactory::create( $type, $this->http_client );
		$user              = $register_instance->register( $request );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Subscribe.
		add_user_meta( $user->ID, 'app_builder_subscribe', $subscribe, true );

		// Agree term and privacy.
		add_user_meta( $user->ID, 'app_builder_agree_privacy_term', $agree_privacy_term, true );

		return $this->pre_data_response( $user, $request, true );
	}

	/**
	 * Get register schema.
	 *
	 * @return array
	 */
	public function schema() {
		return array(
			'email'               => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Your Email',
			),
			'user_login'          => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Your Username',
			),
			'first_name'          => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'First name',
			),
			'last_name'           => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Last name',
			),
			'password'            => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Password',
			),
			'subscribe'           => array(
				'required'    => false,
				'type'        => 'boolean',
				'default'     => true,
				'description' => 'Subscribe',
			),
			'agree_privacy_term'  => array(
				'required'    => true,
				'type'        => 'boolean',
				'description' => 'Agree privacy term',
				'default'     => false,
			),
			'role'                => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Role',
				'default'     => 'subscriber',
			),
			'enable_phone_number' => array(
				'required'    => false,
				'type'        => 'boolean',
				'description' => 'Enable phone number',
				'default'     => false,
			),
			'phone'               => array(
				'required'    => false,
				'type'        => 'string',
				'description' => 'Your Phone Number',
			),
		);
	}
}
