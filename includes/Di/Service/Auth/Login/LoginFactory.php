<?php

namespace AppBuilder\Di\Service\Auth\Login;

use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * LoginFactory factory class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class LoginFactory {

	/**
	 * Create login instance.
	 *
	 * @param ?string             $type Request object.
	 * @param HttpClientInterface $http_client Http client.
	 *
	 * @return LoginInterface Login instance.
	 */
	public static function create( string $type = 'email', HttpClientInterface $http_client ): LoginInterface {
		switch ( $type ) {
			case 'email':
				return new LoginEmail( $http_client );
			case 'facebook':
				return new LoginFacebook( $http_client );
			case 'google':
				return new LoginGoogle( $http_client );
			case 'apple':
				return new LoginApple( $http_client );
			case 'phone':
				return new LoginPhoneNumber( $http_client );
			default:
				return new LoginEmail( $http_client );
		}
	}
}
