<?php

namespace AppBuilder\Di\App\Http;

use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * Class WpHttpClient
 *
 * An HTTP client implementation using WordPress's wp_remote_request.
 */
class WpHttpClient implements HttpClientInterface {

	/**
	 * Send an HTTP request using wp_remote_request.
	 *
	 * @param string $method  The HTTP method (GET, POST, PUT, DELETE).
	 * @param string $url     The URL to send the request to.
	 * @param array  $options The options to send with the request.
	 * @return mixed The response from the server.
	 * @throws HttpClientError If there is an HTTP error.
	 */
	private function sendRequest( string $method, string $url, array $options = array() ) {
		$args = array_merge(
			array(
				'method'  => $method,
				'headers' => $options['headers'] ?? array(),
				'body'    => $options['body'] ?? null,
				'timeout' => 30,
			),
			$options
		);

		return wp_remote_request( $url, $args );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( string $url, array $headers = array() ) {
		return $this->sendRequest( 'GET', $url, array( 'headers' => $headers ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function post( string $url, mixed $data, array $headers = array() ) {
		return $this->sendRequest(
			'POST',
			$url,
			array(
				'headers' => $headers,
				'body'    => is_string( $data ) ? $data : wp_json_encode( $data ),
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function put( string $url, array $data, array $headers = array() ) {
		return $this->sendRequest(
			'PUT',
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
			)
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete( string $url, array $headers = array() ) {
		return $this->sendRequest( 'DELETE', $url, array( 'headers' => $headers ) );
	}
}
