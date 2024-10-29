<?php

namespace AppBuilder\Di\Service\Auth\Register;

use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * RegisterFactory factory class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class RegisterFactory {

	/**
	 * Create login instance.
	 *
	 * @param ?string             $type Request object.
	 * @param HttpClientInterface $http_client Http client.
	 *
	 * @return RegisterInterface Register instance.
	 */
	public static function create( ?string $type = 'email', HttpClientInterface $http_client ): RegisterInterface {
		switch ( $type ) {
			case 'email':
				return new RegisterEmail( $http_client );
			case 'phone_number':
				return new RegisterPhoneNumber( $http_client );
			default:
				return new RegisterEmail( $http_client );
		}
	}
}
