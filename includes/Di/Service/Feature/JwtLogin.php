<?php

/**
 * The Login With Firebase Phone Number feature class.
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

defined( 'ABSPATH' ) || exit;

/**
 * JwtLogin Class.
 */
class JwtLogin extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_jwt_login_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'     => true,
			'secret_key' => defined( 'AUTH_KEY' ) ? AUTH_KEY : home_url( '/app' ),
			'exp'        => 30,
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {}

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
						'value' => isset( $data['status'] ) ? $data['status'] : true,
					),
				),
			),
			array(
				'title'  => 'Settings',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'secret_key',
						'label' => 'Secret Key',
						'hint'  => 'Use a strong, random string to enhance security. This key is crucial for signing and verifying JWTs, so it must be kept confidential and secure. Avoid using simple or easily guessable values to prevent unauthorized access and potential security breaches.',
						'type'  => 'text',
						'value' => $this->get_old_setting_key( $data ),
					),
					array(
						'name'  => 'exp',
						'label' => 'Expiration Time',
						'hint'  => 'The expiration time of the JWT in minutes. The default is 30 minutes.',
						'type'  => 'number',
						'value' => isset( $data['exp'] ) ? $data['exp'] : 30,
					),
				),
			),
		);
	}

	/**
	 * Get old setting key
	 *
	 * @param array $data The data to get the setting for.
	 *
	 * @return string
	 */
	private function get_old_setting_key( $data ) {
		if ( isset( $data['secret_key'] ) ) {
			return $data['secret_key'];
		}

		$old_settings = get_option( 'app_builder_settings', array() );

		if ( isset( $old_settings['jwt']['secret_key'] ) ) {
			return $old_settings['jwt']['secret_key'];
		}

		return '';
	}
}
