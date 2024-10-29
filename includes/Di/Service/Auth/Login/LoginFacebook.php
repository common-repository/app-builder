<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use WP_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Traits\Feature;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Exception;

/**
 * LoginFacebook class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginFacebook implements LoginInterface {
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

		$settings = app_builder()->get( 'settings' )->feature( 'login_facebook' );
		if ( $this->is_feature_disabled( $settings, true ) ) {
			return new WP_Error(
				'login_facebook',
				__( 'Login with Facebook is not enable.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$access_token      = $request->get_param( 'token' );
		$role              = $request->get_param( 'role' );
		$facebook_user_id  = $request->get_param( 'facebook_user_id' );
		$nonce             = $request->get_param( 'nonce' );
		$access_token_type = $request->get_param( 'access_token_type' );

		if ( ! $access_token || ! $facebook_user_id ) {
			return new WP_Error(
				'login_facebook_error',
				__( 'Token or Facebook user id is required.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$body = array();

		if ( $access_token_type === 'LimitedToken' ) {
			$body = $this->verifyOIDCToken( $access_token, $nonce, $settings );
		} else {
            $body = $this->verifyClassicToken( $access_token, $facebook_user_id );
		}

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		// User data.
		$email      = $body['email'];
		$id         = $body['id'];
		$first_name = $body['first_name'];
		$last_name  = $body['last_name'];
		$name       = $body['name'];
		$picture    = $body['picture'];

		if ( ! $email ) {
			return new WP_Error(
				'email_not_exist',
				__( 'User not provider email', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user = get_user_by( 'email', $email );

		// Return data if user exist in database.
		if ( $user ) {
			return $user;
		}

		$user_id = wp_insert_user(
			array(
				'user_pass'     => wp_generate_password(),
				'user_login'    => $email,
				'user_nicename' => $name,
				'user_email'    => $email,
				'display_name'  => $name,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'role'          => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Get user.
		$user = get_user_by( 'id', $user_id );

		add_user_meta( $user_id, 'app_builder_login_type', 'facebook', true );
		add_user_meta( $user_id, 'app_builder_login_avatar', $picture, true );

		return $user;
	}

    /**
     * Verify classic token.
     *
     * @param string $access_token Access token.
     * @param string $facebook_user_id Facebook user id.
     *
     * @return array|WP_Error
     */
	private function verifyClassicToken( $access_token, $facebook_user_id ) {
		$grap_api = 'https://graph.facebook.com/' . $facebook_user_id;

		// Add params to grap api.
		$grap_api = add_query_arg(
			array(
				'fields'       => 'id,first_name,last_name,name,picture,email',
				'access_token' => $access_token,
			),
			$grap_api
		);

		$response = $this->http_client->get( $grap_api );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'login_facebook_error',
				$response->get_error_message(),
				array(
					'status' => 403,
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );

		$body = json_decode( $body, true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error(
				'login_facebook_error',
				$body['error']['message'],
				array(
					'status' => 403,
				)
			);
		}

		// User data.
		$email      = $body['email'];
		$id         = $body['id'];
		$first_name = $body['first_name'];
		$last_name  = $body['last_name'];
		$name       = $body['name'];
		$picture    = isset( $body['picture']['data']['url'] ) ? $body['picture']['data']['url'] : '';
		return array(
			'email'      => $email,
			'id'         => $id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'name'       => $name,
			'picture'    => $picture,
		);
	}

    /**
     * Verify OIDC token.
     *
     * @param string $token OIDC token.
     * @param string $nonce Nonce.
     * @param array  $settings Settings.
     *
     * @return array|WP_Error
     */
	private function verifyOIDCToken( $token, $nonce, $settings ) {
		$jwks_url      = 'https://www.facebook.com/.well-known/oauth/openid/jwks/';
		$jwks_response = $this->http_client->get( $jwks_url );

		if ( is_wp_error( $jwks_response ) ) {
			return new WP_Error(
				'login_facebook_error',
				$jwks_response->get_error_message(),
				array(
					'status' => 403,
				)
			);
		}

		$body = wp_remote_retrieve_body( $jwks_response );
		$keys = json_decode( $body, true );

		try {
			$decoded = JWT::decode( $token, JWK::parseKeySet( $keys ) );
			error_log( print_r( $decoded, true ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'invalid_token', 'The provided ID token is invalid.', array( 'status' => 401 ) );
		}

		// Validate claims
		if ( $decoded->iss !== 'https://www.facebook.com' ) {
			return new WP_Error( 'invalid_issuer', 'The token issuer is invalid.', array( 'status' => 401 ) );
		}

		if ( $decoded->aud !== $settings['app_id'] ) {
			return new WP_Error( 'invalid_audience', 'The token audience is invalid.', array( 'status' => 401 ) );
		}

		if ( $decoded->exp < time() ) {
			return new WP_Error( 'token_expired', 'The token has expired.', array( 'status' => 401 ) );
		}

        // Nonce check
        if ( $decoded->nonce !== $nonce ) {
            return new WP_Error( 'invalid_nonce', 'The nonce is invalid.', array( 'status' => 401 ) );
        }

		return array(
			'email'      => $decoded->email,
			'id'         => $decoded->sub,
			'first_name' => $decoded->given_name,
			'last_name'  => $decoded->family_name,
			'name'       => $decoded->name,
			'picture'    => $decoded->picture,
		);
	}
}
