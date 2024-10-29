<?php

namespace AppBuilder\Di\App\Http;

use AppBuilder\Di\App\Exception\HttpException;
use AppBuilder\Di\App\Http\WpHttpClient;
use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * Class HttpClientFactory
 *
 * Factory for creating HTTP clients.
 */
class HttpClientFactory {

	/**
	 * Create an HTTP client.
	 *
	 * @param string $type   The type of HTTP client to create.
	 * @param array  $config The configuration for the HTTP client.
	 *
	 * @return HttpClientInterface
	 */
	public static function createHttpClient( string $type, array $config = array() ): HttpClientInterface {
		switch ( $type ) {
			case 'WordPress':
				return new WpHttpClient();
			default:
				throw new HttpException( 'Unknown HTTP client type' );
		}
	}
}
