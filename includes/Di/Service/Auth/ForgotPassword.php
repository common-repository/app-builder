<?php
/**
 * ForgotPassword
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;
use WP_User;

/**
 * Class ForgotPassword
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class ForgotPassword {
	use \AppBuilder\Traits\Permission;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * ForgotPassword constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Register rest route.
	 */
	public function register_rest_route() {
		// Step 1: Forgot password with action send OTP to email.
		register_rest_route(
			'app-builder/v1',
			'/forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'forgot_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( null ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'forgot_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( null ),
			)
		);

		// Step 2: Verify OTP and return user token.
		register_rest_route(
			'app-builder/v1',
			'/verify-otp-forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'verify_otp_forgot_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( 'verify' ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/verify-otp-forgot-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'verify_otp_forgot_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( 'verify' ),
			)
		);

		// Step 3: Update new password.
		register_rest_route(
			'app-builder/v1',
			'/update-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( 'update' ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/update-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
				'args'                => $this->schema( 'update' ),
			)
		);
	}

	/**
	 * Get settings.
	 *
	 * @return array settings.
	 */
	private function settings() {
		$default_settings = array(
			'status'                          => 0,
			'otp_expiration_time'             => 1,
			'otp_attempt_limit'               => 3,
			'otp_verification_block_duration' => 15,
		);
		$settings         = app_builder()->get( 'settings' )->feature( 'forgot_password' );
		return wp_parse_args( $settings, $default_settings );
	}

	/**
	 * Check if the feature is disabled.
	 *
	 * @return bool
	 */
	private function is_feature_disabled() {
		$settings = $this->settings();
		return ! $settings['status'];
	}

	/**
	 * Get OTP expiration time.
	 *
	 * @return int
	 */
	private function get_otp_expiration_time() {
		$settings = $this->settings();
		return (int) $settings['otp_expiration_time'] * MINUTE_IN_SECONDS;
	}

	/**
	 * Get OTP attempt limit.
	 *
	 * @return int
	 */
	private function get_otp_attempt_limit() {
		$settings = $this->settings();
		return (int) $settings['otp_attempt_limit'];
	}

	/**
	 * Get OTP verification block duration.
	 *
	 * @return int
	 */
	private function get_otp_verification_block_duration() {
		$settings = $this->settings();
		return (int) $settings['otp_verification_block_duration'] * MINUTE_IN_SECONDS;
	}

	/**
	 * Verify OTP attempt limit.
	 *
	 * @param int $user_id user id.
	 *
	 * @return WP_Error|bool
	 */
	private function verify_attempt_limit( $user_id ) {
		$otp_attempt = get_transient( 'app_builder_forgot_password_attempt_' . $user_id );
		$otp_attempt = $otp_attempt ? (int) $otp_attempt : 0;

		if ( $otp_attempt >= $this->get_otp_attempt_limit() ) {
			return new WP_Error(
				'otp_attempt_limit',
				__( 'OTP attempt limit exceeded', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		return $otp_attempt;
	}

	/**
	 * Forgot password.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function forgot_password( WP_REST_Request $request ) {

		if ( $this->is_feature_disabled() ) {
			return new WP_Error(
				'feature_disabled',
				__( 'Forgot password is disabled', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$user_login = $request->get_param( 'user_login' );

		$user_data = $this->verify( $user_login );

		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		// Verify OTP attempt limit.
		$verify_attempt_limit = $this->verify_attempt_limit( $user_data->ID );
		if ( is_wp_error( $verify_attempt_limit ) ) {
			return $verify_attempt_limit;
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
		$key        = get_password_reset_key( $user_data );

		if ( is_wp_error( $key ) ) {
			return new WP_Error(
				'invalid_key',
				$key->get_error_message(),
				array(
					'status' => 403,
				)
			);
		}

		if ( is_multisite() ) {
			$site_name = get_network()->site_name;
		} else {
			/*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */
			$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		// Generate OTP 6 digits.
		$key = wp_rand( 100000, 999999 );

		// Hi [User Name].
		$message = __( 'Hi', 'app-builder' ) . ' ' . $user_login . ',' . "\r\n\r\n";

		// We heard you're having trouble remembering your password for [App Name]. No worries, it happens to the best of us!
		$message .= __( 'We heard you\'re having trouble remembering your password for', 'app-builder' ) . ' ' . $site_name . '. ' . __( 'No worries, it happens to the best of us!', 'app-builder' ) . "\r\n\r\n";

		// Your OTP is: [6-digit OTP].
		$message .= __( 'Your OTP is:', 'app-builder' ) . ' ' . $key . "\r\n\r\n";

		// Please note: This OTP is confidential and should not be shared with anyone.
		$message .= __( 'Please note: This OTP is confidential and should not be shared with anyone.', 'app-builder' ) . "\r\n\r\n";

		// If you didn't request a password reset, please disregard this email. However, we recommend updating your password regularly to keep your account secure.
		$message .= __( 'If you didn\'t request a password reset, please disregard this email. However, we recommend updating your password regularly to keep your account secure.', 'app-builder' ) . "\r\n\r\n";

		// Subject: Access Your [App Name] Account again - One-Time Password Inside.
		$title = __( 'Access Your', 'app-builder' ) . ' ' . $site_name . ' ' . __( 'Account again - One-Time Password Inside', 'app-builder' );

		// Send email.
		if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
			return new WP_Error(
				'send_email',
				__( 'Possible reason: your host may have disabled the mail() function.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		// Update OTP to user meta expire after [otp_expiration_time] minutes setting.
		set_transient( 'app_builder_forgot_password_' . $user_data->ID, $key, $this->get_otp_expiration_time() );
		set_transient( 'app_builder_forgot_password_attempt_' . $user_data->ID, $verify_attempt_limit, $this->get_otp_verification_block_duration() );

		// Response.
		return rest_ensure_response( array( 'message' => __( 'OTP sent to your email.', 'app-builder' ) ) );
	}

	/**
	 * Verify OTP for forgot password.
	 *
	 * @param WP_REST_Request $request request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function verify_otp_forgot_password( $request ) {
		$user_login = $request->get_param( 'user_login' );
		$otp        = $request->get_param( 'otp' );

		$validate = apply_filters( 'app_builder_validate_form_data', true, $request, 'ForgotPassword' );
		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		$user_data = $this->verify( $user_login );
		if ( is_wp_error( $user_data ) ) {
			return $user_data;
		}

		// Verify OTP attempt limit.
		$verify_attempt_limit = $this->verify_attempt_limit( $user_data->ID );
		if ( is_wp_error( $verify_attempt_limit ) ) {
			return $verify_attempt_limit;
		}
		set_transient( 'app_builder_forgot_password_attempt_' . $user_data->ID, $verify_attempt_limit + 1, $this->get_otp_verification_block_duration() );

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;

		// Get OTP from user meta.
		$otp_user = get_transient( 'app_builder_forgot_password_' . $user_data->ID );

		// Check OTP.
		if ( $otp !== $otp_user ) {
			return new WP_Error(
				'invalid_otp',
				__( 'Invalid OTP', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$data = array(
			'user' => array(
				'id' => $user_data->ID,
			),
			'otp'  => $otp,
		);

		$token = app_builder()->get( 'token' )->sign_token( $user_data->ID, $data, 1800 );

		// Response.
		return rest_ensure_response(
			array(
				'token' => $token,
			)
		);
	}

	/**
	 * Update password for forgot password.
	 *
	 * @param WP_REST_Request $request request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function update_password( $request ) {
		$token        = $request->get_param( 'token' );
		$new_password = $request->get_param( 'new_password' );

		// Verify token.
		$data = app_builder()->get( 'token' )->verify_token( $token );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$user_id = $data->data->user_id;
		$otp     = $data->data->otp;

		// Get OTP from user meta.
		$otp_user = get_transient( 'app_builder_forgot_password_' . $user_id );

		// Check OTP.
		if ( $otp !== $otp_user ) {
			return new WP_Error(
				'invalid_otp',
				__( 'Expired or Password changed', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		} else {
			// Delete OTP.
			delete_transient( 'app_builder_forgot_password_' . $user_id );
		}

		// Update new password.
		wp_set_password( $new_password, $user_id );

		// Response.
		return rest_ensure_response(
			array(
				'message' => __( 'Password updated', 'app-builder' ),
			)
		);
	}

	/**
	 * Verify user login.
	 *
	 * @param string $user_login user login.
	 *
	 * @return WP_Error|WP_User
	 */
	private function verify( $user_login ) {
		$errors = new WP_Error();
		if ( empty( $user_login ) || ! is_string( $user_login ) ) {
			$errors->add(
				'empty_username',
				__( 'Enter a username or email address.', 'app-builder' ),
				array(
					'status' => 404,
				)
			);
		} elseif ( strpos( $user_login, '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $user_login ) ) );
			if ( empty( $user_data ) ) {
				$errors->add(
					'invalid_email',
					__( 'There is no account with that username or email address.', 'app-builder' ),
					array(
						'status' => 404,
					)
				);
			}
		} else {
			$login     = trim( $user_login );
			$user_data = get_user_by( 'login', $login );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		if ( ! $user_data ) {
			$errors->add(
				'invalidcombo',
				__( 'There is no account with that username or email address.', 'app-builder' ),
				array(
					'status' => 404,
				)
			);

			return $errors;
		}
		return $user_data;
	}

	/**
	 * Schema.
	 *
	 * @param string $type type of schema.
	 *
	 * @return array
	 */
	public function schema( string $type = null ): array {

		if ( 'update' === $type ) {
			return array(
				'token'        => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'Token',
				),
				'new_password' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'New password',
				),
			);
		}

		if ( 'verify' === $type ) {
			return array(
				'user_login' => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'User login or email address',
				),
				'otp'        => array(
					'type'        => 'string',
					'required'    => true,
					'description' => 'OTP code',
				),
			);
		}

		return array(
			'user_login' => array(
				'type'        => 'string',
				'required'    => true,
				'description' => 'Username or email address',
			),
		);
	}
}
