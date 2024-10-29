<?php

namespace AppBuilder\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cart
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 */
class Cart extends Base {
	public function __construct() {
		$this->namespace = APP_BUILDER_REST_BASE . '/v1';
	}

	public function register_routes() {
	}
}
