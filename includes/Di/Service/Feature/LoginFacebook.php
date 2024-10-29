<?php

/**
 * The Login With Facebook feature class.
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

defined( 'ABSPATH' ) || exit;

/**
 * LoginFacebook Class.
 */
class LoginFacebook extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_login_facebook_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'     => true,
			'app_id'     => '',
			'app_secret' => '',
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
						'name'  => 'app_id',
						'label' => 'App ID',
						'type'  => 'text',
						'value' => isset( $data['app_id'] ) ? $data['app_id'] : '',
					),
					array(
						'name'  => 'app_secret',
						'label' => 'App Secret',
						'type'  => 'text',
						'value' => isset( $data['app_secret'] ) ? $data['app_secret'] : '',
					),
				),
			),
		);
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
		unset( $data['app_secret'] );

		$features[ $key ] = $data;
		return $features;
	}
}
