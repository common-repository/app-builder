<?php
/**
 * The abstract class AbstractStore
 *
 * @link       https://appcheap.io
 * @since      2.5.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 * @package    AppBuilder\Di\Service\Store
 */

namespace AppBuilder\Di\Service\Store;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractStore
 *
 * @package AppBuilder\Di\Service\Store
 */
abstract class AbstractStore {

	/**
	 * Register routes
	 *
	 * @return void
	 */
	abstract public function register_routes();

	/**
	 * Register store routes
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 *
	 * @return void
	 */
	abstract public function register_store_routes( $rest_namespace, $rest_base );

	/**
	 * Register review routes
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 *
	 * @return void
	 */
	abstract public function register_review_routes( $rest_namespace, $rest_base );
}
