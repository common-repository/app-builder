<?php
/**
 * Captcha feature
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Captcha Class.
 */
class Captcha extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_captcha_settings';

	/**
	 * Captcha constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'         => true,
			'Login'          => false,
			'Register'       => false,
			'CommentPost'    => false,
			'ReviewProduct'  => false,
			'ForgotPassword' => true,
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {
		add_filter( 'app_builder_validate_form_data', array( $this, 'validate_captcha' ), 10, 3 );
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks() {}

	/**
	 * Get form fields
	 */
	public function get_form_fields() {
		$data = $this->get_data();
		return array(
			array(
				'title'      => 'Enable/Disable',
				'page'       => 'index',
				'show_panel' => false,
				'fields'     => array(
					array(
						'name'  => 'status',
						'label' => 'Enable/Disable',
						'type'  => 'switch',
						'value' => isset( $data['status'] ) ? $data['status'] : 1,
					),
				),
			),
			array(
				'title'  => 'Settings',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'Login',
						'label' => 'Login Form',
						'type'  => 'switch',
						'value' => isset( $data['Login'] ) ? $data['Login'] : 0,
					),
					array(
						'name'  => 'Register',
						'label' => 'Register Form',
						'type'  => 'switch',
						'value' => isset( $data['Register'] ) ? $data['Register'] : 0,
					),
					array(
						'name'  => 'CommentPost',
						'label' => 'Comment Post Form',
						'type'  => 'switch',
						'value' => isset( $data['CommentPost'] ) ? $data['CommentPost'] : 0,
					),
					array(
						'name'  => 'ReviewProduct',
						'label' => 'Review Product Form',
						'type'  => 'switch',
						'value' => isset( $data['ReviewProduct'] ) ? $data['ReviewProduct'] : 0,
					),
					array(
						'name'  => 'ForgotPassword',
						'label' => 'Forgot Password OTP Form',
						'type'  => 'switch',
						'value' => isset( $data['ForgotPassword'] ) ? $data['ForgotPassword'] : 1,
					),
				),
			),
		);
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
	public function validate_captcha( bool $validate, $request, string $type ) {

		if ( empty( $type ) ) {
			return $validate;
		}

		// Check if the captcha is enabled for the form.
		$settings = app_builder()->get( 'settings' )->feature( 'captcha' );
		if ( ! $this->is_truely( $settings[ $type ] ) ) {
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
