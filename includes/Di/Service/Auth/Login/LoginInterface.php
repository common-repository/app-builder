<?php

namespace AppBuilder\Di\Service\Auth\Login;

use WP_REST_Request;
use WP_Error;
use WP_User;

/**
 * LoginInterface interface
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
interface LoginInterface {
	/**
	 * Login action.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return Wp_User|WP_Error Response object.
	 */
	public function login( WP_REST_Request $request );
}
