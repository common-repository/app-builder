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

defined( 'ABSPATH' ) || exit;

trait IntegrationTraits {
	/**
	 * Get integration information.
	 *
	 * @return array Integration information.
	 */
	public static function get_info() {
		return self::$infomation;
	}
}
