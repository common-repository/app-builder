<?php
/**
 * Cache class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Api;

/**
 * Cache class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class Cache {
	/**
	 * Settings.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor.
	 *
	 * @param array $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Get cache.
	 *
	 * @param string $key Cache key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {

		if ( ! $this->settings['status'] || ! $this->settings['cache_on'] ) {
			return false;
		}

		return get_transient( $key );
	}

	/**
	 * Set cache.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Cache value.
	 * @param int    $expire Cache expire.
	 *
	 * @return bool
	 */
	public function set( $key, $value, $expire = 0 ) {

		if ( ! $this->settings['status'] || ! $this->settings['cache_on'] ) {
			return false;
		}

		$expire = $expire ? $expire : $this->settings['expire'];

		return set_transient( $key, $value, $expire );
	}

	/**
	 * Set cache control header.
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return WP_REST_Response $response Response object.
	 */
	public function set_cache_control( $response ) {
		if ( ! $this->settings['status'] || ! $this->settings['cache_control_header_on'] ) {
			return $response;
		}

		$response->header( 'Cache-Control', 'must-revalidate, s-maxage=' . $this->settings['s-maxage'] . ', max-age=' . $this->settings['max-age'] );
		return $response;
	}

	/**
	 * Set ETag header.
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return WP_REST_Response $response Response object.
	 */
	public function set_etag( $response ) {

		if ( ! $this->settings['status'] || ! $this->settings['cache_on'] ) {
			return $response;
		}
		$etag = md5( wp_json_encode( $response->get_data() ) );
		$response->header( 'ETag', $etag );
		return $response;
	}

	/**
	 * Set header.
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return WP_REST_Response $response Response object.
	 */
	public function set_header( $response ) {
		return $this->set_cache_control( $this->set_etag( $response ) );
	}
}
