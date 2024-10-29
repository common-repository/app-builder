<?php

namespace AppBuilder\Di\App\Exception;

use Exception;

/**
 * ExceptionAbstract
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @package    AppBuilder
 */
abstract class ExceptionAbstract extends Exception implements ExceptionInterface {

	protected $message = 'Unknown exception';     // Exception message
	private $string;                            // Unknown
	protected $code = 0;                       // User-defined exception code
	protected $file;                              // Source filename of exception
	protected $line;                              // Source line of exception
	private $trace;                             // Unknown

	public function __construct( $message = null, $code = 0 ) {
		if ( ! $message ) {
			throw new $this( 'Unknown ' . esc_html( get_class( $this ) ) );
		}
		parent::__construct( $message, $code );
	}

	public function __toString() {

		$str = esc_html( get_class( $this ) ) . " '" . esc_html( $this->message ) . "' in " . esc_html( $this->file ) . '(' . esc_html( $this->line ) . ")\n"
			. esc_html( $this->getTraceAsString() );

		return $str;
	}
}
