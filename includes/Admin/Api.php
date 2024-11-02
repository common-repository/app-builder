<?php

namespace AppBuilder\Admin;

use AppBuilder\Api\Base;
use WP_HTTP_Response;
use WP_REST_Server;
use WP_REST_Response;
use AppBuilder\Utils;
use WP_Error;

/**
 *
 * @author     Appcheap <ngocdt@rnlab.io>
 * @since 1.0.0
 */
class Api extends Base {

	public function __construct() {
		$this->namespace = APP_BUILDER_REST_BASE . '/v1';
	}

	/**
	 * Registers a REST API route
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'settings' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_configs' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'features',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_features' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'license',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'license' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'active-template',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'active_template' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'fonts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'fonts' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'download',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'download' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'font-awesome',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'font_awesome' ),
					'permission_callback' => array( $this, 'admin_permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	public function fonts( $request ) {
		$res = wp_remote_get( plugin_dir_url( APP_BUILDER_PLUGIN_FILE ) . 'assets/fonts/fonts.json' );

		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'error', 'Error fetching fonts', array( 'status' => 500 ) );
		}

		$fonts = wp_remote_retrieve_body( $res );

		return rest_ensure_response( json_decode( $fonts ) );
	}

	public function font_awesome( $request ) {
		$search = $request->get_param( 'search' );

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'text/plain',
			),
			'body'    => 'query {search(version:"5.15.4", query: "' . $search . '", first: 100) {id label membership {free } } }',
		);

		$response = wp_remote_post( FONTAWESOME_API_URL, $args );

		if ( $response instanceof WP_Error ) {
			return $response;
		}

		$fonts = array();
		if ( 200 === $response['response']['code'] ) {
			$body = json_decode( $response['body'] );
			if ( isset( $body->data->search ) ) {
				foreach ( $body->data->search as $value ) {
					if ( isset( $value->membership->free ) && count( $value->membership->free ) > 0 ) {
						foreach ( $value->membership->free as $type ) {
							$fonts[] = array(
								'id'    => $value->id,
								'label' => $value->label,
								'type'  => $type,
							);
						}
					}
				}
			}
		}

		return rest_ensure_response( $fonts );
	}

	/**
	 * Download
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function download( $request ) {

		$url     = $request->get_param( 'url' );
		$version = $request->get_param( 'version' );

		$download = Utils::download( $url, $version );

		if ( is_wp_error( $download ) ) {
			return $download;
		}

		if ( get_option( 'app_builder_cirilla_version' ) ) {
			update_option( 'app_builder_cirilla_version', $version );
		} else {
			add_option( 'app_builder_cirilla_version', $version );
		}

		return rest_ensure_response( array( 'download' => 'success' ) );
	}

	/**
	 *
	 * Get Settings
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function settings( $request ) {

		/**
		 * Template via param
		 */
		$id = (int) $request->get_param( 'id' );

		/**
		 * Template active
		 */
		$template_active_id = (int) get_option( 'app_builder_template_active_id', 0 );

		/**
		 * Get template from query param or from active
		 */
		$template_id = $id > 0 ? $id : $template_active_id;

		$cache_key   = "app_builder_settings-$template_id";
		$cache_store = app_builder()->get( 'cache' );

		/**
		 * Get settings from cached
		 */
		$result = $cache_store->get( $cache_key );
		if ( false !== $result ) {
			$response = new WP_REST_Response( $result, 200 );
			return $cache_store->set_header( $response );
		}

		/**
		 * Get post by id
		 */
		$template = get_post( $id > 0 ? $id : $template_active_id );

		/**
		 * Get at least one template in list
		 */

		if ( ! $template ) {
			$templates = get_posts(
				array(
					'post_type'   => 'app_builder_template',
					'status'      => 'publish',
					'numberposts' => 1,
				)
			);
			$template  = count( $templates ) > 0 ? $templates[0] : null;
		}

		/**
		 * Prepare template data
		 */
		$template_data = array(
			'data'     => is_null( $template ) ? array() : json_decode( $template->post_content ),
			'features' => apply_filters( 'app_builder_features_public_data', array() ),
		);

		/**
		 * Filter data setting before response
		 */
		$data = apply_filters( 'app_builder_prepare_settings_data', $template_data );

		/**
		 * Return data
		 */
		$response = new WP_REST_Response( $data, 200 );

		// Cache response.
		$cache_store->set( $cache_key, $data );

		$response = new WP_REST_Response( $data, 200 );
		return $cache_store->set_header( $response );
	}

	/**
	 * Update configs.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 * @since    1.0.0
	 */
	public function update_configs( $request ) {

		$data = $request->get_param( 'data' );

		if ( get_option( 'app_builder_configs' ) ) {
			$status = update_option( 'app_builder_configs', maybe_serialize( $data ) );
		} else {
			$status = add_option( 'app_builder_configs', maybe_serialize( $data ) );
		}

		return new WP_REST_Response( array( 'status' => $status ), 200 );
	}

	/**
	 * Update features
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_features( $request ) {
		$data = $request->get_params();
		do_action( 'app_builder_features_post', $data );
		return new WP_REST_Response( array( 'status' => true ), 200 );
	}

	/**
	 *
	 * Add license code
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 * @since    1.0.0
	 */
	public function license( $request ): WP_REST_Response {

		$license = $request->get_param( 'license' );
		$app     = $request->get_param( 'app' );

		delete_option( 'app_builder_license' );
		delete_option( 'app_builder_app' );

		add_option( 'app_builder_app', $app );
		$status = add_option( 'app_builder_license', $license );

		return new WP_REST_Response( array( 'status' => $status ), 200 );
	}

	/**
	 *
	 * Active template
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response | WP_Error
	 */
	public function active_template( $request ) {

		$template_id = (int) $request->get_param( 'template_id' );

		if ( ! $template_id ) {
			return new WP_Error(
				'active_template',
				__( 'Template id not validate.', 'app-builder' ),
				array(
					'status' => 403,
				)
			);
		}

		if ( get_option( 'app_builder_template_active_id', false ) ) {
			$status = update_option( 'app_builder_template_active_id', $template_id );
		} else {
			$status = add_option( 'app_builder_template_active_id', $template_id );
		}

		return new WP_REST_Response( array( 'status' => $status ), 200 );
	}
}
