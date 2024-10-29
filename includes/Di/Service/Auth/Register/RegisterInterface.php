<?php

namespace AppBuilder\Di\Service\Auth\Register;

use WP_REST_Request;
use WP_Error;
use WP_User;

/**
 * RegisterInterface interface
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
interface RegisterInterface {
	/**
	 * Login action.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return Wp_User|WP_Error Response object.
	 */
	public function register( WP_REST_Request $request );
}
