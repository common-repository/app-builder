<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class LostPassword
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LostPassword {
	use \AppBuilder\Traits\Permission;

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * LostPassword constructor.
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
		register_rest_route(
			'app-builder/v1',
			'/lost-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'lost_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
		register_rest_route(
			'app-builder/v1',
			'/auth/lost-password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'lost_password' ),
				'permission_callback' => array( $this, 'public_permissions_callback' ),
			)
		);
	}

	/**
	 * Lost password.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function lost_password( WP_REST_Request $request ) {
		$errors = new WP_Error();

		$user_login = $request->get_param( 'user_login' );

		if ( empty( $user_login ) || ! is_string( $user_login ) ) {
			$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Enter a username or email address.', 'mobile-builder' ) );
		} elseif ( strpos( $user_login, '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $user_login ) ) );
			if ( empty( $user_data ) ) {
				$errors->add(
					'invalid_email',
					__( '<strong>ERROR</strong>: There is no account with that username or email address.', 'mobile-builder' ),
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
				__( '<strong>ERROR</strong>: There is no account with that username or email address.', 'mobile-builder' ),
				array(
					'status' => 404,
				)
			);

			return $errors;
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;
		$key        = get_password_reset_key( $user_data );

		if ( is_wp_error( $key ) ) {
			return $key;
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

		$message = __( 'Someone has requested a password reset for the following account:', 'mobile-builder' ) . "\r\n\r\n";
		/* translators: %s: site name */
		$message .= sprintf( __( 'Site Name: %s', 'mobile-builder' ), $site_name ) . "\r\n\r\n";
		/* translators: %s: user login */
		$message .= sprintf( __( 'Username: %s', 'mobile-builder' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'mobile-builder' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:', 'mobile-builder' ) . "\r\n\r\n";
		$message .= '<' . network_site_url(
			"wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ),
			'login'
		) . ">\r\n";

		/* translators: Password reset notification email subject. %s: Site title */
		$title = sprintf( __( '[%s] Password Reset', 'mobile-builder' ), $site_name );

		/**
		 * Filters the subject of the password reset email.
		 *
		 * @param string $title Default email title.
		 * @param string $user_login The username for the user.
		 * @param WP_User $user_data WP_User object.
		 *
		 * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
		 *
		 * @since 2.8.0
		 */
		$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

		/**
		 * Filters the message body of the password reset mail.
		 *
		 * If the filtered message is empty, the password reset email will not be sent.
		 *
		 * @param string $message Default mail message.
		 * @param string $key The activation key.
		 * @param string $user_login The username for the user.
		 * @param WP_User $user_data WP_User object.
		 *
		 * @since 2.8.0
		 * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
		 */
		$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

		if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
			return new WP_Error(
				'send_email',
				__( 'Possible reason: your host may have disabled the mail() function.', 'mobile-builder' ),
				array(
					'status' => 401,
				)
			);
		}

		return rest_ensure_response( true );
	}
}
