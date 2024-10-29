<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Classs\Token;

/**
 * DetermineAuth class
 */
class DetermineAuth {
	/**
	 * Param name
	 *
	 * @var string $param_name Param name.
	 */
	private string $param_name = 'app-builder-token';

	/**
	 * Param decode
	 *
	 * @var string $param_decode Param decode.
	 */
	private string $param_decode = 'app_builder_decode';

	/**
	 * Cookie name
	 *
	 * @var string $cookie_name Cookie name.
	 */
	private string $cookie_name = 'cirilla_auth_token';

	/**
	 * DetermineAuth constructor.
	 */
	public function __construct() {
		$this->param_name   = APP_BUILDER_TOKEN_PARAM_NAME;
		$this->param_decode = APP_BUILDER_DECODE;
	}

	/**
	 * Determine current user.
	 *
	 * @param int|bool $user_id User ID.
	 *
	 * @return int|bool User ID.
	 */
	public static function determine_current_user( $user_id ) {
		$instance = new self();
		$user_id  = $instance->determine_current_user_param( $user_id );
		$user_id  = $instance->determine_current_user_header( $user_id );
		$user_id  = $instance->determine_current_user_cookie( $user_id );
		return $user_id;
	}

	/**
	 * This to determine the current user from the request’s thought header if available.
	 *
	 * @param int|bool $user_id ID if one has been determined, false otherwise.
	 *
	 * @return int|bool
	 * @author ngocdt
	 *
	 * @since 1.0.0
	 */
	public function determine_current_user_param( $user_id ) {

		/* Decode user if pass thought param */
		if ( isset( $_GET[ $this->param_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$token = wp_unslash( $_GET[ $this->param_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $this->verify_token( $user_id, $token );
		}

		return $user_id;
	}

	/**
	 * This to determine the current user from the request’s thought header if available.
	 *
	 * @param int|bool $user_id  ID if one has been determined, false otherwise.
	 *
	 * @return int|bool
	 * @author ngocdt
	 *
	 * @since 1.0.0
	 */
	public function determine_current_user_header( $user_id ) {

		/* Run only app_builder_decode param exist */
		if ( ! isset( $_GET[ $this->param_decode ] ) || empty( $_GET[ $this->param_decode ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $user_id;
		}

		/* Decode authorization on the header to determine current user */
		$token = app_builder()->get( 'token' )->verify_token();

		/* If facing any errors return current user id state*/
		if ( is_wp_error( $token ) ) {
			return $user_id;
		}

		/* Return current user id store in token */

		return $token->data->user_id;
	}

	/**
	 *
	 * Determine current user via cookie
	 *
	 * @param int|bool $user_id ID if one has been determined, false otherwise.
	 *
	 * @return mixed
	 */
	public function determine_current_user_cookie( $user_id ) {

		if ( isset( $_COOKIE[ $this->cookie_name ] ) ) {
			$token = wp_unslash( $_COOKIE[ $this->cookie_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $this->verify_token( $user_id, $token );
		}

		return $user_id;
	}

	/**
	 * Verify the token.
	 *
	 * @param int|bool $user_id ID if one has been determined, false otherwise.
	 * @param string   $str_token Token string.
	 *
	 * @return int|bool
	 */
	private function verify_token( $user_id, $str_token ) {
		/* Decode authorization on the header to determine current user */
		$token = app_builder()->get( 'token' )->verify_token( $str_token );

		/* If facing any errors return current user id state*/
		if ( is_wp_error( $token ) ) {
			return $user_id;
		}

		/* Return current user id store in token */
		return $token->data->user_id;
	}
}
