<?php
/**
 * Register Country API
 *
 * @link       https://appcheap.io
 * @since      1.0.21
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Api;

use stdClass;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class Country
 *
 * @link       https://appcheap.io
 * @since      1.0.21
 *
 * @package    AppBuilder
 */
class Country {
	protected $namespace;

	public function __construct() {
		$this->namespace = APP_BUILDER_REST_BASE . '/v1';
	}

	/**
	 * Registers a REST API route
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		/**
		 * Update customer
		 *
		 * @author Ngoc Dang
		 * @since 1.0.21
		 */
		if ( class_exists( '\WC_Countries' ) ) {
			/**
			 * @since 1.0.21
			 */
			register_rest_route(
				$this->namespace,
				'get-country-locale',
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_country_locale' ),
					'permission_callback' => '__return_true',
				)
			);

			/**
			 * @since 1.0.21
			 */
			register_rest_route(
				$this->namespace,
				'address',
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'address' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Get country locale settings.
	 *
	 * @param $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_country_locale( $request ): WP_REST_Response {
		$obj       = new \WC_Countries();
		$countries = $obj->get_country_locale();

		$countries = apply_filters( 'app_builder_prepare_address_fields_response', $countries );

		return new WP_REST_Response( $countries, 200 );
	}

	/**
	 * Get address form configs.
	 *
	 * @param WP_REST_Request $request Request data.
	 *
	 * @return WP_REST_Response Response data.
	 */
	public function address( $request ) {
		$cache_key   = 'app_builder_address_form';
		$cache_store = app_builder()->get( 'cache' );

		$cached = $cache_store->get( $cache_key );
		if ( false !== $cached ) {
			$response = new WP_REST_Response( $cached, 200 );
			return $cache_store->set_header( $response );
		}

		$obj = new \WC_Countries();

		$country = $request->get_param( 'country' );
		if ( ! $country ) {
			$country = $obj->get_base_country();
		}

		$_POST['billing_country']  = $country;
		$_POST['shipping_country'] = $country;
		$checkout                  = new \WC_Checkout();

		$fields = $checkout->get_checkout_fields();

		$data = array(
			'country'                     => $country,
			'billing'                     => $fields['billing'],
			'shipping'                    => $fields['shipping'],
			'address_format'              => $obj->get_address_formats(),
			'billing_countries_selected'  => get_option( 'woocommerce_allowed_countries' ),
			'billing_countries'           => $obj->get_allowed_countries(),
			'billing_countries_states'    => $obj->get_allowed_country_states(),
			'shipping_countries_selected' => get_option( 'woocommerce_ship_to_countries' ),
			'shipping_countries'          => $obj->get_shipping_countries(),
			'shipping_country_states'     => $obj->get_shipping_country_states(),
			'additional'                  => isset( $fields['additional'] ) ? $fields['additional'] : new stdClass(),
		);

		// Cache response.
		$cache_store->set( $cache_key, $data );

		$response = new WP_REST_Response( $data, 200 );
		return $cache_store->set_header( $response );
	}
}
