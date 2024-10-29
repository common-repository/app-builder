<?php

namespace AppBuilder\Di\Service\Auth\Register;

use WP_REST_Request;
use Wp_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * RegisterEmail class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class RegisterEmail implements RegisterInterface {
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
	public function register( WP_REST_Request $request ) {
		$user_data = array();

		$email              = $request->get_param( 'email' );
		$user_login         = $request->get_param( 'user_login' );
		$first_name         = $request->get_param( 'first_name' );
		$last_name          = $request->get_param( 'last_name' );
		$password           = $request->get_param( 'password' );
		$agree_privacy_term = $request->get_param( 'agree_privacy_term' );
		$role               = $request->get_param( 'role' );

		if ( ! $agree_privacy_term ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'To register you need agree our term and privacy.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		// Validate first name.
		if ( mb_strlen( $first_name ) < 1 ) {
			return new WP_Error(
				'app_builder_register_error',
				__( "First name isn't valid.", 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		// Validate last name.
		if ( mb_strlen( $last_name ) < 1 ) {
			return new WP_Error(
				'app_builder_register_error',
				__( "Last name isn't valid.", 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		$enable_email = app_builder()->get( 'app_builder_template' )->get_screen_data( 'register', 'register', 'enableEmail', true );

		if ( $enable_email ) {

			// Validate email.
			if ( ! is_email( $email ) ) {
				return new WP_Error(
					'app_builder_register_error',
					__( 'The email address isn\'t correct.', 'app-builder' ),
					array(
						'status' => 401,
					)
				);
			}

			if ( email_exists( $email ) ) {
				return new WP_Error(
					'app_builder_register_error',
					__( 'The email address is already registered', 'app-builder' ),
					array(
						'status' => 401,
					)
				);
			}

			$user_data['email'] = $email;
		}

		// Validate username.
		if ( empty( $user_login ) || mb_strlen( $user_login ) < 2 ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'Username too short.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		if ( ! validate_username( $user_login ) ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		if ( username_exists( $user_login ) ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'Username already exists.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		// Validate password.
		if ( empty( $password ) ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'Password is required.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		if ( mb_strlen( $password ) < 6 ) {
			return new WP_Error(
				'app_builder_register_error',
				__( 'Password is too short.', 'app-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		$user_data = array_merge(
			$user_data,
			array(
				'user_pass'    => $password,
				'user_email'   => $email,
				'user_login'   => $user_login,
				'display_name' => "$first_name $last_name",
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'role'         => $role,
			)
		);

		$user_id = wp_insert_user( apply_filters( 'app_builder_register_user_data', $user_data, $request ) );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		do_action( 'app_builder_after_insert_user', $user_id, $request );

		$user = get_user_by( 'id', $user_id );

		return $user;
	}
}
