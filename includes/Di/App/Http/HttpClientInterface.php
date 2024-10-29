<?php

namespace AppBuilder\Di\App\Http;

/**
 * Interface HttpClientInterfaceHttpClientInterface
 *
 * An interface for an HTTP client.
 */
interface HttpClientInterface {

	/**
	 * Send a GET request to the specified URL.
	 *
	 * @param string $url
	 * @param array  $headers
	 * @return mixed
	 */
	public function get( string $url, array $headers = array() );

	/**
	 * Send a POST request to the specified URL.
	 *
	 * @param string $url
	 * @param mixed  $data
	 * @param array  $headers
	 * @return mixed
	 */
	public function post( string $url, mixed $data, array $headers = array() );

	/**
	 * Send a PUT request to the specified URL.
	 *
	 * @param string $url
	 * @param array  $data
	 * @param array  $headers
	 * @return mixed
	 */
	public function put( string $url, array $data, array $headers = array() );

	/**
	 * Send a DELETE request to the specified URL.
	 *
	 * @param string $url
	 * @param array  $headers
	 * @return mixed
	 */
	public function delete( string $url, array $headers = array() );
}
