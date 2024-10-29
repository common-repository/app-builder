<?php

namespace AppBuilder\Di\Service\Auth;

use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * Class Auth
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class Auth {

	/**
	 * Http client
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private HttpClientInterface $http_client;

	/**
	 * Auth constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Init function.
	 */
	public function init() {
		add_filter( 'determine_current_user', array( 'AppBuilder\Di\Service\Auth\DetermineAuth', 'determine_current_user' ), 10, 1 );
	}

	/**
	 * API init function.
	 */
	public function api_init() {
		$classs = array(
			'AppBuilder\Di\Service\Auth\CurrentAuth',
			'AppBuilder\Di\Service\Auth\LoginAuth',
			'AppBuilder\Di\Service\Auth\RegisterAuth',
			'AppBuilder\Di\Service\Auth\ChangePassword',
			'AppBuilder\Di\Service\Auth\LostPassword',
			'AppBuilder\Di\Service\Auth\ForgotPassword',
			'AppBuilder\Di\Service\Auth\LoginToken',
			'AppBuilder\Di\Service\Auth\DeleteAuth',
		);

		foreach ( $classs as $class_name ) {
			$instance = new $class_name( $this->http_client );
			$instance->register_rest_route();
		}
	}
}
