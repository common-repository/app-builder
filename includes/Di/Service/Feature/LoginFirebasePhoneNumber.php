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
 * LoginFirebasePhoneNumber Class.
 */
class LoginFirebasePhoneNumber extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_login_firebase_phone_number_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status' => true,
			'key'    => '',
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
						'value' => isset( $data['status'] ) ? $data['status'] : false,
					),
				),
			),
			array(
				'title'  => 'Settings',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'key',
						'label' => 'Google Api Key',
						'hint'  => 'Put your google api key here',
						'type'  => 'text',
						'value' => $this->get_old_setting_key( $data ),
					),
				),
			),
		);
	}

	/**
	 * Get old setting key
	 *
	 * @param string $key The key to get the setting for.
	 *
	 * @return string
	 */
	private function get_old_setting_key( $data ) {

		if ( isset( $data['key'] ) ) {
			return $data['key'];
		}

		$old_settings = get_option( 'app_builder_settings', array() );

		if ( isset( $old_settings['google']['key'] ) ) {
			return $old_settings['google']['key'];
		}

		return '';
	}

    /**
	 * Get public data
	 *
	 * @param array $features features.
	 *
	 * @return array
	 */
	public function get_public_data( $features ) {
		$key = $this->get_public_meta_key();

		$data = $this->get_data();
		unset( $data['key'] );

		$features[ $key ] = $data;
		return $features;
	}
}
