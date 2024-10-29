<?php
/**
 * Category file
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

/**
 * Trait Category
 *
 * @author ngocdt@rnlab.io
 * @since 5.0.0
 */
trait Category {
	/**
	 * Get store categories
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_store_categories( $request ) {
		return new WP_REST_Response( array(), 200 );
	}
}
