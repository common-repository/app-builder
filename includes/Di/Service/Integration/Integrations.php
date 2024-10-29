<?php

/**
 * Integrations
 *
 * @link       https://appcheap.io
 * @since      4.2.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Integration;

use AppBuilder\Traits\Permission;
use AppBuilder\Di\App\Http\HttpClientInterface;
use WP_Error;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Integrations Class.
 */
class Integrations {
	use Permission;

	/**
	 * Integrations Identifier
	 */
	const IDENTIFIER = 'integrations';

	/**
	 * Integrations Class
	 *
	 * @var array $classes Integrations classes name.
	 */
	private $classes = array();

	/**
	 * Http client.
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private $http_client;

	/**
	 * Integrations constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Init
	 */
	public function init() {
		/**
		 * Register integrations by files
		 */
		$this->register_integrations();

		/**
		 * Register hooks
		 */
		$this->register_hooks();
	}

	/**
	 * Register hooks
	 */
	public function register_hooks() {

		/**
		 * Add custom data to app builder
		 */
		add_filter( 'app_builder_custom_data', array( $this, 'app_builder_custom_data' ), 10, 1 );
		/**
		 * Register rest api
		 */
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		/**
		 * Register integrations hooks
		 */
		$integrations = $this->get_integrations();
		foreach ( array_keys( $this->classes ) as $name ) {
			if ( isset( $integrations[ $name ] ) && isset( $integrations[ $name ]['enable'] ) && 1 === (int) $integrations[ $name ]['enable'] ) {
				$class_name     = __NAMESPACE__ . '\\' . $name;
				$class_instance = new $class_name();
				// Check if the class has a rest_api_init method.
				if ( method_exists( $class_instance, 'register_hooks' ) ) {
					$class_instance->register_hooks();
				}
			}
		}
	}

	/**
	 * Register integrations by files
	 */
	public function register_integrations() {
		/**
		 * Get all files in Integrations folder
		 */
		$files = glob( __DIR__ . '/*.php' );

		foreach ( $files as $file ) {
			/**
			 * Ignore this file
			 */
			if ( __FILE__ === $file ) {
				continue;
			}
			/**
			 * Init file end with Integrations.php
			 */
			if ( strpos( $file, 'Integration.php' ) ) {
				$name = basename( $file, '.php' );
				if ( isset( $this->classes[ $name ] ) ) {
					continue;
				} else {
					$class_name = __NAMESPACE__ . '\\' . $name;
					if ( class_exists( $class_name ) && is_subclass_of( $class_name, IntegrationInterface::class ) ) {
						$this->classes[ $name ] = $class_name::get_info();
					}
				}
			}
		}
	}

	/**
	 * Register Rest API
	 */
	public function rest_api_init() {
		register_rest_route(
			'app-builder/v1',
			'/' . self::IDENTIFIER,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_integrations' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
			)
		);
	}

	/**
	 * App Builder Custom Data
	 *
	 * @param array $data Data.
	 *
	 * @return array
	 */
	public function app_builder_custom_data( $data ) {
		$data['integrations'] = array(
			'settings' => $this->classes,
			'values'   => $this->get_integrations(),
		);
		return $data;
	}

	/**
	 * Get Integrations
	 *
	 * @return array
	 */
	public function get_integrations() {
		$integrations = get_option( 'app_builder_integrations', array() );
		return $integrations;
	}

	/**
	 * Save Integrations
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return array
	 */
	public function save_integrations( $request ) {
		$integrations = $request->get_param( 'integrations' );
		if ( ! is_array( $integrations ) ) {
			return new WP_Error( 'app_builder_integrations_error', __( 'Invalid integrations data', 'app-builder' ) );
		}

		$integrations = apply_filters( 'app_builder_integrations_before_save', $integrations );

		if ( is_wp_error( $integrations ) ) {
			return new WP_Error( 'app_builder_integrations_error', $integrations->get_error_message() );
		}

		$old_integrations = $this->get_integrations();

		// Merge old integrations with new integrations.
		$integrations = array_merge( $old_integrations, $integrations );

		update_option( 'app_builder_integrations', $integrations );
		return $integrations;
	}
}
