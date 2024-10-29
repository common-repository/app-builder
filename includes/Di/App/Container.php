<?php
namespace AppBuilder\Di\App;

use AppBuilder\Di\App\Exception\DiException;

/**
 * Container
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @package    AppBuilder
 */
class Container {

	/**
	 * The array of registered services.
	 *
	 * @var array
	 */
	protected $services = array();

	/**
	 * The array of instantiated services.
	 *
	 * @var array
	 */
	protected $instances = array();

	/**
	 * Register a service with the container.
	 *
	 * @param string $name The service name.
	 * @param mixed  $value The service value (raw data, instantiated class, or closure).
	 *
	 * @return void
	 */
	public function set( $name, $value ) {
		$this->services[ $name ] = $value;
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $name the service name.
	 * @return mixed The service.
	 * @throws DiException If the service is not found.
	 */
	public function get( $name ) {

		if ( ! isset( $this->services[ $name ] ) ) {
			throw new DiException( sprintf( 'Service %s not found.', esc_html( $name ) ) );
		}

		// Check if the service is already instantiated.
		if ( ! isset( $this->instances[ $name ] ) ) {
			$service = $this->services[ $name ];

			// If the service is a closure, instantiate it.
			if ( $service instanceof \Closure ) {
				$this->instances[ $name ] = $service( $this );
			} elseif ( is_string( $service ) && class_exists( $service ) ) {
				$this->instances[ $name ] = new $service();
			} else {
				$this->instances[ $name ] = $service;
			}
		}

		return $this->instances[ $name ];
	}

	/**
	 * Check if a service is registered.
	 *
	 * @param string $name The service name.
	 * @return bool
	 */
	public function has( $name ) {
		return isset( $this->services[ $name ] );
	}
}
