<?php
/**
 * WcfmStore
 *
 * @package AppBuilder\Di\Service\Store\WcfmStore
 */

namespace AppBuilder\Di\Service\Store\WcfmStore;

use AppBuilder\Di\Service\Store\AbstractStore;
use AppBuilder\Di\Service\Store\WcfmStore\Review;
use AppBuilder\Di\Service\Store\WcfmStore\Store;

/**
 * Class WcfmStore
 *
 * @package AppBuilder\Di\Service\Store\WcfmStore
 */
class WcfmStore extends AbstractStore {

	/**
	 * The namespace
	 *
	 * @var string
	 */
	private $rest_namespace;

	/**
	 * The rest base
	 *
	 * @var string
	 */
	private $rest_base;

	/**
	 * Constructor
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 */
	public function __construct( $rest_namespace, $rest_base ) {
		$this->rest_namespace = $rest_namespace;
		$this->rest_base      = $rest_base;
	}

	/**
	 * Register review routes
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 *
	 * @return void
	 */
	public function register_routes() {
		$this->register_store_routes( $this->rest_namespace, $this->rest_base );
		$this->register_review_routes( $this->rest_namespace, $this->rest_base );
	}

	/**
	 * Register store routes
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 *
	 * @return void
	 */
	public function register_store_routes( $rest_namespace, $rest_base ) {
		$store = new Store( $rest_namespace, $rest_base );
		$store->register_routes();
	}

	/**
	 * Register review routes
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 *
	 * @return void
	 */
	public function register_review_routes( $rest_namespace, $rest_base ) {
		$review = new Review( $rest_namespace, $rest_base . '/reviews' );
		$review->register_routes();
	}
}
