<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use WP_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;
use Exception;
use AppBuilder\Di\Service\Auth\Register\RegisterPhoneNumber;
use AppBuilder\Classs\RawQuery;
use AppBuilder\Traits\Feature;

/**
 * LoginPhoneNumber class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginPhoneNumber implements LoginInterface {
	use Feature;

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
		try {
			$google = app_builder()->get( 'settings' )->feature( 'login_firebase_phone_number' );

			if ( $this->is_feature_disabled( $google ) ) {
				return new WP_Error(
					'login_firebase_phone_number',
					__( 'Login with Firebase phone number is not enable.', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			if ( ! $google || ! $google['key'] ) {
				return new WP_Error(
					'login_firebase_phone_number',
					__( 'Google Api key not config yet.', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			$token = $request->get_param( 'token' );

			$url = add_query_arg(
				array(
					'key' => $google['key'],
				),
				'https://www.googleapis.com/identitytoolkit/v3/relyingparty/getAccountInfo'
			);

			$data   = array( 'idToken' => $token );
			$header = array(
				'Content-Type' => 'application/json',
			);

			$response = $this->http_client->post( $url, $data, $header );

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'login_google_error',
					$response->get_error_message(),
					array(
						'status' => 403,
					)
				);
			}

			$body   = wp_remote_retrieve_body( $response );
			$result = json_decode( $body );

			if ( false === $result ) {
				return new WP_Error(
					'login_firebase_phone_number',
					__( 'Get Firebase user info error!', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			if ( isset( $result->error ) ) {
				return new WP_Error(
					'login_firebase_phone_number',
					$result->error->message,
					array(
						'status' => 403,
					)
				);
			}

            $register = new RegisterPhoneNumber( $this->http_client );

			/**
			 * If the user not exist in Firebase we try register in WP database
			 */
			if ( ! isset( $result->users[0]->phoneNumber ) ) {
				$request->set_param( 'phone', $result->users[0]->phoneNumber );
				return $register->register( $request );
			}

			$phone_number = $result->users[0]->phoneNumber;
			$user_ids     = RawQuery::get_user_ids_by_meta( 'digits_phone', $phone_number );

			/**
			 * Return login data if user already exist in the database
			 */
			if ( count( $user_ids ) > 0 ) {
				$user_id = reset( $user_ids );
				$user    = get_user_by( 'id', $user_id );
				return $user;
			}

			// Delete cache save empty user_ids.
			wp_cache_delete( 'user_ids_' . md5( 'digits_phone_' . $phone_number ), 'app_builder_user_ids' );

			$request->set_param( 'phone', $result->users[0]->phoneNumber );
			return $register->register( $request );
		} catch ( Exception $err ) {
			return new WP_Error(
				'login_firebase_phone_number',
				$err->getMessage(),
				array(
					'status' => 403,
				)
			);
		}
	}
}
