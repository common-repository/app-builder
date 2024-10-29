<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use WP_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * LoginGoogle class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginGoogle implements LoginInterface {
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
		$id_token = $request->get_param( 'idToken' );
		$role     = $request->get_param( 'role' );

		$url = add_query_arg(
			array(
				'id_token' => $id_token,
			),
			'https://oauth2.googleapis.com/tokeninfo'
		);

		$response = $this->http_client->get( $url );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'login_google_error',
				__( 'Get Firebase user info error!', 'app_builder' ),
				array(
					'status' => 400,
				)
			);
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body );

		if ( false === $result ) {
			return new WP_Error(
				'login_google_error',
				__( 'Get Firebase user info error!', 'app_builder' ),
				array(
					'status' => 400,
				)
			);
		}

		// Email not exist.
		$email = $result->email;
		if ( ! $email ) {
			return new WP_Error(
				'login_google_error',
				__( 'User not provider email', 'app_builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'email', $email );

		// Response if the email already exis in database.
		if ( $user ) {
			return $user;
		}

		// Insert new user.
		$user_id = wp_insert_user(
			array(
				'user_login'    => $result->email,
				'user_pass'     => wp_generate_password(),
				'user_nicename' => $result->name,
				'user_email'    => $result->email,
				'display_name'  => $result->name,
				'first_name'    => $result->given_name,
				'last_name'     => $result->family_name,
				'role'          => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'id', $user_id );

		add_user_meta( $user_id, 'app_builder_login_type', 'google', true );
		add_user_meta( $user_id, 'app_builder_login_avatar', $result->picture, true );

		return $user;
	}
}
