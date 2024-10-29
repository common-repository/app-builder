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
 * SampleFeature Class.
 */
class SampleFeature extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_sample_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status' => true,
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
						'name'  => 'name',
						'label' => 'Text',
						'hint'  => 'Hind',
						'type'  => 'text',
						'value' => isset( $data['name'] ) ? $data['name'] : '',
					),
				),
			),
		);
	}
}
