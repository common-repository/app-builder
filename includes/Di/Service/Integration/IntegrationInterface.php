<?php
/**
 * The IntegrationInterface interface file.
 *
 * @link       https://appcheap.io
 * @since      4.2.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Define IntegrationInterface for integrations classes.
 */
interface IntegrationInterface {
	/**
	 * Get integration information.
	 *
	 * @return array Integration information.
	 */
	public static function get_info();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks();
}
