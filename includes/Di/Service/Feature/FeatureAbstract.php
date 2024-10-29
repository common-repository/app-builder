<?php

/**
 * CustomIcon
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class FeatureAbstract.
 */
abstract class FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	protected $meta_key;

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $default_settings;

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	abstract public function activation_hooks();

	/**
	 * Both for admin and public hooks
	 *
	 * @return void
	 */
	abstract public function register_hooks();

	/**
	 * Get data
	 *
	 * @return array
	 */
	public function get_data() {
		$data = get_option( $this->meta_key, array() );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		return wp_parse_args( $data, $this->default_settings );
	}

	/**
	 * Get public meta key
	 *
	 * @return string
	 */
	public function get_public_meta_key() {
		// Remove app_builder_ first of the key.
		$key = str_replace( 'app_builder_', '', $this->meta_key );
		// Remove _settings last of the key.
		$key = str_replace( '_settings', '', $key );

		return $key;
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

		$features[ $key ] = $this->get_data();
		return $features;
	}

	/**
	 * Set data
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function set_data( $data ) {
		if ( isset( $data[ $this->meta_key ] ) ) {
			update_option( $this->meta_key, wp_parse_args( $data[ $this->meta_key ], $this->get_data() ) );
		}
	}

	/**
	 * Get meta key
	 *
	 * @return string
	 */
	public function get_meta_key() {
		return $this->meta_key;
	}

	/**
	 * Get form fields
	 */
	public function get_form_fields() {
		return array();
	}

	/**
	 * Register form fields
	 *
	 * @param array $features features.
	 *
	 * @return array features.
	 */
	public function register_form_fields( $features ) {
		$features[ $this->meta_key ] = $this->get_form_fields();
		return $features;
	}

	/**
	 * Check status of data
	 *
	 * @return boolean.
	 */
	public function get_status() {
		$data = $this->get_data();
		return isset( $data['status'] ) ? (int) $data['status'] : 0;
	}

	/**
	 * Check status of data
	 *
	 * @return boolean.
	 */
	public function is_active() {
		return in_array( $this->get_status(), array( 1, '1', true, 'true' ), true );
	}

	/**
	 * Check status of data
	 *
	 * @return boolean.
	 */
	public function is_deactive() {
		return ! $this->is_active();
	}

	/**
	 * Check value is truely
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return boolean.
	 */
	public function is_truely( $value ) {
		return in_array( $value, array( 1, '1', true, 'true' ), true );
	}
}
