<?php

namespace AppBuilder\Di\Service\Auth;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Auth Trails
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 *
 * @author     ngocdt
 */
trait AuthTrails {
	/**
	 * Prepare data response.
	 *
	 * @param WP_User         $user  User object.
	 * @param WP_REST_Request $request      Request object.
	 * @param bool            $exist User exist.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function pre_data_response( $user, $request, $exist = false ) {

		if ( $exist ) {
			do_action( 'wp_login', $user->user_login, $user );
		}

		/**
		 * Trigger action before response API
		 */
		do_action( 'app_builder_register_succes', $user, $request );

		/**
		 * Support jwt-authentication-for-wp-rest-api plugin.
		 * Save user data to token to authenticate by jwt-authentication-for-wp-rest-api plugin
		 */
		$data = array();
		if ( class_exists( '\Jwt_Auth' ) ) {
			$data = array(
				'user' => array(
					'id' => $user->data->ID,
				),
			);
		}

		/**
		 * Pre data response.
		 */
		$response = apply_filters(
			'app_builder_pre_auth_response',
			array(
				'token' => apply_filters(
					'app_builder_prepare_token',
					app_builder()->get( 'token' )->sign_token( $user->data->ID, $data ),
					$user,
				),
				'user'  => apply_filters(
					'app_builder_prepare_userdata',
					$user,
				),
			),
			$request
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Verify role before inserting to database
	 *
	 * @param string $role Role.
	 *
	 * @return string Role.
	 */
	private function verify_role( $role ): string {
		if ( ! $role || ! in_array( $role, array( 'subscriber', 'customer', 'wcfm_vendor' ), true ) ) {
			$role = get_option( 'default_role', 'subscriber' );
		}
		return $role;
	}
}
