<?php
/**
 * Store class
 *
 * @link       https://appcheap.io
 * @since      4.0.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 * @package    AppBuilder\Di\Service\Store
 */

namespace AppBuilder\Di\Service\Store\WcfmStore;

defined( 'ABSPATH' ) || exit;

use WP_REST_Response;
use WP_REST_Controller;

/**
 * Class Review
 *
 * @package AppBuilder\Di\Service\Store
 */
class Store extends WP_REST_Controller {

	/**
	 * Constructor
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 */
	public function __construct( $rest_namespace, $rest_base ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = $rest_base;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Get items
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		global $WCFMmp;

		$search         = $request->get_param( 'search' ) ? sanitize_text_field( $request->get_param( 'search' ) ) : '';
		$category       = $request->get_param( 'category' ) ? sanitize_text_field( $request->get_param( 'category' ) ) : '';
		$page           = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;
		$per_page       = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 10;
		$includes       = $request->get_param( 'includes' ) ? sanitize_text_field( $request->get_param( 'includes' ) ) : '';
		$excludes       = $request->get_param( 'excludes' ) ? sanitize_text_field( $request->get_param( 'excludes' ) ) : '';
		$has_product    = $request->get_param( 'has_product' ) ? sanitize_text_field( $request->get_param( 'has_product' ) ) : '';
		$order          = $request->get_param( 'order' ) ? sanitize_text_field( $request->get_param( 'order' ) ) : 'ASC';
		$orderby        = $request->get_param( 'orderby' ) ? sanitize_text_field( $request->get_param( 'orderby' ) ) : 'ID';
		$store_category = $request->get_param( 'store_category' ) ? sanitize_text_field( $request->get_param( 'store_category' ) ) : '';

		$search_data = array();

		$length = absint( $per_page );
		$offset = ( $page - 1 ) * $length;

		$search_data['excludes'] = $excludes;

		if ( $includes ) {
			$includes = explode( ',', $includes );
		} else {
			$includes = array();
		}

		if ( $store_category ) {
			$search_data['wcfmsc_store_categories'] = $store_category;
		}

		$search_data = apply_filters( 'app_builder_wcfm_search_data', $search_data, $request );

		$stores = $WCFMmp->wcfmmp_vendor->wcfmmp_get_vendor_list( true, $offset, $length, $search, $includes, $order, $orderby, $search_data, $category, $has_product );

		$data_objects = array();

		foreach ( $stores as $id => $store ) {
			$stores_data    = $this->prepare_item_for_response( $id, $request );
			$data_objects[] = $this->prepare_response_for_collection( $stores_data );
		}

		/**
		 * Filter store list data before response to client
		 */
		$results = apply_filters( 'app_builder_get_stores', $data_objects, $request );

		return rest_ensure_response( $results );
	}

	/**
	 * Prepare a single user output for response
	 *
	 * @param int             $id User ID.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $additional_fields Additional fields.
	 *
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $id, $request, $additional_fields = array() ) {
		$store = get_user_meta( $id, 'wcfmmp_profile_settings', true );

		// Gravatar image.
		$gravatar_url = isset( $store['gravatar'] ) ? wp_get_attachment_url( $store['gravatar'] ) : '';

		// List Banner URL.
		$list_banner_url = isset( $store['list_banner'] ) ? wp_get_attachment_url( $store['list_banner'] ) : '';

		// Banner URL.
		$banner_url = isset( $store['banner'] ) ? wp_get_attachment_url( $store['banner'] ) : '';

		// Mobile Banner URL.
		$mobile_banner_url = isset( $store['mobile_banner'] ) ? wp_get_attachment_url( $store['mobile_banner'] ) : '';

		$store_user = wcfmmp_get_store( $id );

		$data = array(
			'id'               => intval( $id ),
			'store_name'       => $store['store_name'] ?? '',
			'first_name'       => $store['first_name'] ?? '',
			'last_name'        => $store['last_name'] ?? '',
			'phone'            => $store['phone'] ?? '',
			'show_email'       => true,
			'email'            => $store['store_email'] ?? '',
			'vendor_address'   => $store_user->get_address_string(),
			'banner'           => $banner_url,
			'mobile_banner'    => $mobile_banner_url,
			'list_banner'      => $list_banner_url,
			'gravatar'         => $gravatar_url,
			'shop_description' => $store['shop_description'] ?? '',
			'social'           => $store['social'] ?? '',
			'address'          => $store['address'] ?? '',
			'customer_support' => $store['customer_support'] ?? '',
			'featured'         => false,
			'rating'           => array(
				'rating' => intval( $store_user->get_total_review_rating() ),
				'count'  => intval( $store_user->get_total_review_count() ),
				'avg'    => intval( $store_user->get_avg_review_rating() ),
			),
			'geolocation'      => $store['geolocation'] ?? '',
		);

		$response = rest_ensure_response( $data );

		return apply_filters( 'app_builder_wcfm_rest_prepare_store_item_for_response', $response );
	}

	/**
	 * Prepare object for product response
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param object           $_ The original object.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 *
	 * @return WP_REST_Response
	 */
	public function woocommerce_rest_prepare_product_object( $response, $_, $request ) {
		$data = $response->get_data();

		if ( isset( $data['store'] ) && is_array( $data['store'] ) && $data['store']['vendor_id'] ) {
			$store_data    = $this->prepare_item_for_response( $data['store']['vendor_id'], $request );
			$data['store'] = $this->prepare_response_for_collection( $store_data );
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Get the query params for collections
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}
}
