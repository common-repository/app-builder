<?php



namespace AppBuilder\Hooks;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_Error;

/**
 * The class CaptchaHook
 *
 * @link       https://appcheap.io
 * @author     ngocdt
 * @since      2.9.0
 */
class CaptchaHook {
	/**
	 * CaptchaHook constructor.
	 */
	public function __construct() {
		add_filter( 'app_builder_validate_form_data', array( $this, 'app_builder_validate_form_data' ), 10, 3 );
	}

	/**
	 * Filter captcha
	 *
	 * @param bool            $validate Validate form data.
	 * @param WP_REST_Request $request Request data.
	 * @param string          $type Type of form.
	 *
	 * @return WP_Error|bool
	 */
	public function app_builder_validate_form_data( bool $validate, $request, string $type ) {

		$enable_captcha = (bool) app_builder()->get( 'app_builder_template' )->get_settings_general( 'enableCaptcha' . $type, false );

		if ( ! empty( $type ) && false === $enable_captcha ) {
			return $validate;
		}

		$phrase  = $request->get_param( 'phrase' );
		$captcha = $request->get_param( 'captcha' );

		if ( empty( $captcha ) || empty( $phrase ) ) {
			return new WP_Error(
				'app_builder_captcha',
				__( 'Captcha or phrase not provider.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$captcha_store = get_option( 'app_builder_captcha', array() );

		if ( ! isset( $captcha_store[ $phrase ] ) ) {
			return new WP_Error(
				'app_builder_captcha',
				__( 'Phrase not validate.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		$captcha_data = $captcha_store[ $phrase ];
		unset( $captcha_store[ $phrase ] );

		update_option( 'app_builder_captcha', $captcha_store );

		if ( strtolower( $captcha_data['phrase'] ) !== strtolower( $captcha ) ) {
			return new WP_Error(
				'app_builder_captcha',
				__( 'Captcha not validate.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		if ( $captcha_data['time'] < time() ) {
			return new WP_Error(
				'app_builder_captcha',
				__( 'Captcha expired.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		return $validate;
	}
}
