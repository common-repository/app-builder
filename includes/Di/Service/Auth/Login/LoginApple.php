<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use WP_User;
use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Classs\PublicKey;

/**
 * LoginApple class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginApple implements LoginInterface {
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
			$identity_token = $request->get_param( 'identityToken' );
			$user_identity  = $request->get_param( 'userIdentifier' );
			$given_name     = $request->get_param( 'givenName' );
			$family_name    = $request->get_param( 'familyName' );
			$role           = $request->get_param( 'role' );

			$tks = explode( '.', $identity_token );
			if ( count( $tks ) !== 3 ) {
				return new WP_Error(
					'login_apple_error',
					__( 'Wrong number of segments', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			list( $headb64 ) = $tks;

			$header = JWT::jsonDecode( JWT::urlsafeB64Decode( $headb64 ) );

			if ( null === $header ) {
				return new WP_Error(
					'login_apple_error',
					__( 'Invalid header encoding', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			if ( ! isset( $header->kid ) ) {
				return new WP_Error(
					'login_apple_error',
					__( '"kid" empty, unable to lookup correct key', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			$public_key_details = PublicKey::getPublicKey( $header->kid );
			$public_key         = $public_key_details['publicKey'];
			$alg                = $public_key_details['alg'];

			$payload = JWT::decode( $identity_token, new Key( $public_key, $alg ) );

			if ( $payload->sub !== $user_identity ) {
				return new WP_Error(
					'validate-user',
					__( 'User not validate', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			// User already exist in database with Email.
			$user = get_user_by( 'email', $payload->email );
			if ( $user ) {
				return $user;
			}

			// User already exist in database with user Identity.
			$user = get_user_by( 'login', $payload->sub );
			if ( $user ) {
				return $user;
			}

			// Register new user.
			$userdata = array(
				'user_pass'    => wp_generate_password(),
				'user_login'   => $payload->sub,
				'user_email'   => $payload->email,
				'display_name' => $given_name,
				'first_name'   => $family_name,
				'last_name'    => $given_name,
				'role'         => $role,
			);

			$user_id = wp_insert_user( $userdata );

			if ( is_wp_error( $user_id ) ) {
				$error_code = $user_id->get_error_code();

				return new WP_Error(
					$error_code,
					$user_id->get_error_message( $error_code ),
					array(
						'status' => 403,
					)
				);
			}

			$user = get_user_by( 'id', $user_id );

			/**
			 * Add user meta for login type.
			 */
			add_user_meta( $user_id, 'app_builder_login_type', 'apple', true );

			return $user;
		} catch ( Exception $e ) {
			return new WP_Error(
				'login_apple_error',
				$e->getMessage(),
				array(
					'status' => 403,
				)
			);
		}
	}
}
