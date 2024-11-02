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
 * CacheControl Class.
 */
class CacheControl extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_cache_control_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'                  => false,

			'cache_on'                => false,
			'expire'                  => 0,

			'cache_control_header_on' => false,
			's-maxage'                => 86400,
			'max-age'                 => 3600,
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
				'title'  => 'Server-Side Caching',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'cache_on',
						'label' => 'Cache On',
						'type'  => 'switch',
						'value' => isset( $data['cache_on'] ) ? $data['cache_on'] : false,
					),
					array(
						'name'  => 'expire',
						'label' => 'Expiration Time',
						'hint'  => 'When to expire the cache contents, in seconds.',
						'type'  => 'number',
						'value' => isset( $data['expire'] ) ? $data['expire'] : 0,
					),
				),
			),
			array(
				'title'  => 'Cache Control Headers',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'cache_control_header_on',
						'label' => 'Cache Control Header On',
						'type'  => 'switch',
						'value' => isset( $data['cache_control_header_on'] ) ? $data['cache_control_header_on'] : false,
					),
					array(
						'name'  => 's-maxage',
						'label' => 'S-maxage',
						'hint'  => 'This directive is specific to shared caches (like proxies or CDNs). It sets the maximum amount of time, in seconds, that the response is considered fresh in a shared cache.',
						'type'  => 'number',
						'value' => isset( $data['s-maxage'] ) ? $data['s-maxage'] : 86400,
					),
					array(
						'name'  => 'max-age',
						'label' => 'Max-age',
						'hint'  => 'This is for private caches (like a user\'s device, or browser). It indicates that the resource is fresh for 3600 seconds (1 hour).',
						'type'  => 'number',
						'value' => isset( $data['max-age'] ) ? $data['max-age'] : 3600,
					),
				),
			),
		);
	}
}
