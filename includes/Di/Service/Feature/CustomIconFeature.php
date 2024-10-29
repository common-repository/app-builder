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
 * CustomIconFeature Class.
 */
class CustomIconFeature extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_custom_icon_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status' => false,
			'icons'  => '',
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {
		add_filter( 'app_builder_custom_css', array( $this, 'custom_css' ), 10, 1 );
		add_filter( 'app_builder_custom_data', array( $this, 'custom_data' ), 10, 1 );
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
						'value' => isset( $data['status'] ) ? $data['status'] : false,
					),
				),
			),
			array(
				'title'  => 'Custom Icon',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'icons',
						'label' => 'Add icon links',
						'hint'  => 'You can put multi icon urls with .json and .css file generate in admin.appcheap.io and separate by new line.',
						'type'  => 'textarea',
						'value' => isset( $data['icons'] ) ? $data['icons'] : '',
					),
				),
			),
		);
	}

	/**
	 * Get url icons
	 *
	 * @return string
	 */
	public function get_url_icons() {
		$data = $this->get_data();
		return $data['icons'];
	}

	/**
	 * Custom css
	 *
	 * @param array $css css.
	 *
	 * @return array
	 */
	public function custom_css( $css ) {

		$icons = $this->get_url_icons();

		if ( empty( $icons ) ) {
			return $css;
		}

		// Init time.
		$custom_css = is_array( $css ) ? $css : array();

		// Parse icons.
		$list_css_urls = $this->parse_urls( $icons, 'css' );

		foreach ( $list_css_urls as $css_url ) {
			$query = wp_parse_url( $css_url, PHP_URL_QUERY );
			parse_str( $query, $params );
			$custom_css[] = array(
				'name'    => md5( $css_url ),
				'url'     => $css_url,
				'version' => $params['v'],
			);
		}

		return $custom_css;
	}

	/**
	 * Custom data
	 *
	 * @param array $data data.
	 *
	 * @return array
	 */
	public function custom_data( $data = array() ) {
		$icons = $this->get_url_icons();

		if ( empty( $icons ) ) {
			return $data;
		}

		$custom_json = array();

		// Parse icons.
		$icon_json_urls = $this->parse_urls( $icons, 'json' );

		foreach ( $icon_json_urls as $json_url ) {
			// Use wp_remote_get instead of file_get_contents.
			$json_data = wp_remote_get( $json_url );
			if ( is_wp_error( $json_data ) ) {
				continue;
			}

			$json_data_body = wp_remote_retrieve_body( $json_data );
			if ( empty( $json_data_body ) ) {
				continue;
			}

			$json_data_body_decoded = json_decode( $json_data_body, true );
			if ( empty( $json_data_body_decoded ) ) {
				continue;
			}

			// Validate json data has fontName and icon property.
			if ( ! isset( $json_data_body_decoded['fontName'] ) || ! isset( $json_data_body_decoded['icons'] ) ) {
				continue;
			}

			$custom_json[] = $json_data_body_decoded;
		}

		$data['custom_icons'] = wp_json_encode( $custom_json );

		return $data;
	}

	/**
	 * Parse urls
	 *
	 * @param string $urls urls.
	 * @param string $mime_type mime type.
	 *
	 * @return array
	 */
	public function parse_urls( $urls, $mime_type = 'css' ) {
		if ( empty( $urls ) ) {
			return array();
		}

		// Parse by new line.
		$urls = explode( "\n", $urls );

		$result = array();
		foreach ( $urls as $url ) {
			$url = trim( $url );
			if ( empty( $url ) ) {
				continue;
			}

			// Continue if not valid url.
			if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
				continue;
			}

			// Continue if not css file.
			if ( pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) !== $mime_type ) {
				continue;
			}

			$result[] = $url;
		}

		return $result;
	}
}
