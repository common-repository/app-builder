<?php


/**
 * class WCVendors
 *
 * @link       https://appcheap.io
 * @since      1.0.13
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 */

namespace AppBuilder\Di\Service\Vendor;

defined( 'ABSPATH' ) || exit;

use WP_User_Query;

class WCVendors extends BaseStore {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'vendor';

	public function register_routes() {
		// add_filter( 'woocommerce_rest_prepare_product_object', array(
		// $this,
		// 'woocommerce_rest_prepare_product_object'
		// ), 100, 3 );
		add_filter(
			'woocommerce_rest_product_object_query',
			array(
				$this,
				'enable_vendor_on_list_product_query',
			),
			10,
			2
		);
		parent::register_routes();
	}

	public function enable_vendor_on_list_product_query( $args, $request ) {
		$args['author']         = isset( $request['vendor'] ) ? $request['vendor'] : '';
		$args['author__in']     = isset( $request['include_vendor'] ) ? $request['include_vendor'] : '';
		$args['author__not_in'] = isset( $request['exclude_vendor'] ) ? $request['exclude_vendor'] : '';

		return $args;
	}

	public function get_stores( $request ) {
		$params = $request->get_params();

		$args = array(
			'number' => $params['per_page'],
			'offset' => ( $params['page'] - 1 ) * $params['per_page'],
		);

		if ( ! empty( $params['orderby'] ) ) {
			$args['orderby'] = $params['orderby'];
		}

		if ( ! empty( $params['order'] ) ) {
			$args['order'] = $params['order'];
		}

		if ( ! empty( $params['status'] ) ) {
			if ( $params['status'] == 'pending' ) {
				$args['role'] = 'pending_vendor';
			} else {
				$args['role'] = $this->post_type;
			}
		}

		$object   = array();
		$response = array();

		$args = wp_parse_args(
			$args,
			array(
				'role'    => 'vendor',
				'fields'  => 'ids',
				'orderby' => 'registered',
				'order'   => 'ASC',
			)
		);

		$includes = isset( $params['includes'] ) ? sanitize_text_field( $params['includes'] ) : '';
		$excludes = isset( $params['excludes'] ) ? sanitize_text_field( $params['excludes'] ) : '';

		if ( $includes ) {
			$args['include'] = explode( ',', $includes );
		}

		if ( $excludes ) {
			$args['exclude'] = explode( ',', $excludes );
		}

		$search = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		if ( $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_nicename', 'user_email', 'display_name' );
		}

		$user_query = new WP_User_Query( $args );

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $vendor_id ) {
				$vendor   = get_userdata( $vendor_id );
				$is_block = get_user_meta( $vendor->id, '_vendor_turn_off', true );
				if ( $is_block ) {
					continue;
				}
				$vendor_data = $this->prepare_item_for_response( $vendor, $request );
				$object[]    = $this->prepare_response_for_collection( $vendor_data );
			}

			$per_page    = (int) ( ! empty( $request['per_page'] ) ? $request['per_page'] : 10 );
			$page        = (int) ( ! empty( $request['page'] ) ? $request['page'] : 1 );
			$total_count = $user_query->get_total();
			$max_pages   = ceil( $total_count / $per_page );

			$response = rest_ensure_response( $object );

			$response->header( 'X-WP-Total', $total_count );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

			if ( $page > 1 ) {
				$prev_page = $page - 1;
				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}
				$prev_link = add_query_arg( 'page', $prev_page, $base );
				$response->link_header( 'prev', $prev_link );
			}

			if ( $max_pages > $page ) {
				$next_page = $page + 1;
				$next_link = add_query_arg( 'page', $next_page, $base );
				$response->link_header( 'next', $next_link );
			}
		}

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data $object Object data.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( "wcmp_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	/**
	 * Prepare a single vendor output for response
	 *
	 * @param object          $method
	 * @param WP_REST_Request $request Request object.
	 * @param array           $additional_fields (optional)
	 *
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $method, $request, $additional_fields = array() ) {
		$vendor_meta_data = get_user_meta( $method->id, );

		// echo $vendor_meta_data['_wcv_store_address1'][0];
		// echo $vendor_meta_data['_wcv_store_address2'][0];
		// print_r( json_encode( $vendor_meta_data ) );
		// die;

		$address = array(
			'address_1' => $this->get_value( '_wcv_store_address1', $vendor_meta_data ),
			'address_2' => $this->get_value( '_wcv_store_address2', $vendor_meta_data ),
			'city'      => $this->get_value( '_wcv_store_city', $vendor_meta_data ),
			'state'     => $this->get_value( '_wcv_store_state', $vendor_meta_data ),
			'country'   => $this->get_value( '_wcv_store_country', $vendor_meta_data ),
			'postcode'  => $this->get_value( '_wcv_store_postcode', $vendor_meta_data ),
			'phone'     => $this->get_value( '_wcv_store_phone', $vendor_meta_data ),
		);

		$banner_id = $this->get_value( '_wcv_store_banner_id', $vendor_meta_data );
		$icon_id   = $this->get_value( '_wcv_store_icon_id', $vendor_meta_data );

		$avatar = $icon_id !== '' ? wp_get_attachment_url( $icon_id ) : '';
		$banner = $banner_id !== '' ? wp_get_attachment_url( $banner_id ) : '';

		$data = array(
			'id'               => intval( $method->id ),
			'store_name'       => $this->get_value( 'pv_shop_name', $vendor_meta_data ),
			'first_name'       => $method->first_name,
			'last_name'        => $method->last_name,
			'phone'            => '',
			'show_email'       => true,
			'email'            => $method->user_email,
			'vendor_address'   => $this->get_address_string( $address ),
			'banner'           => $banner,
			'mobile_banner'    => $banner,
			'list_banner'      => $banner,
			'gravatar'         => $avatar,
			'shop_description' => $this->get_value( 'pv_shop_description', $vendor_meta_data ),
			'social'           => array(
				'facebook'    => $this->get_value( '_wcv_facebook_url', $vendor_meta_data ),
				'twitter'     => $this->get_value( '_wcv_twitter_url', $vendor_meta_data ),
				'google_plus' => $this->get_value( '_wcv_google_plus_url', $vendor_meta_data ),
				'linkdin'     => $this->get_value( '_wcv_linkdin_url', $vendor_meta_data ),
				'youtube'     => $this->get_value( '_wcv_youtube_url', $vendor_meta_data ),
				'instagram'   => $this->get_value( '_wcv_instagram_url', $vendor_meta_data ),
			),
			'address'          => $address,
			'customer_support' => '',
			'featured'         => false,
			'rating'           => array(
				'rating' => 0,
				'count'  => 0,
				'avg'    => 0,
			),
		);

		$vendor_object = apply_filters( 'wcvendors_rest_prepare_vendor_object_args', $data, $method, $request );

		$vendor_object = array_merge( $vendor_object, $additional_fields );
		$response      = rest_ensure_response( $vendor_object );
		$response->add_links( $this->prepare_links( $vendor_object, $request ) );

		return apply_filters( "wcvendors_rest_prepare_{$this->post_type}_method", $response, $method, $request );
	}

	public function get_value( $key, $meta ) {
		return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : '';
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Data         $object Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $object['id'] ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Get the shop address
	 *
	 * @return array
	 */
	public function get_address_string( $store ) {

		$store_address = $store['address_1'] . ' ' . $store['city'] . ' ' . $store['state'] . ' ' . $store['postcode'] . ' ' . $store['country'];

		return apply_filters( 'wcpm_store_address_string', $store_address, $store );
	}
}
