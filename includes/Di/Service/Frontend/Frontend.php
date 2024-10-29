<?php

/**
 * Admin
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend class
 */
class Frontend {
	/**
	 * Init
	 */
	public function init() {
		/**
		 * Add style for checkout page
		 *
		 * @since 1.0.0
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * Load class for body
		 *
		 * Example: https://appcheap.io/checkout/?app-builder-checkout-body-class=remove-header-folter-class
		 */
		if ( isset( $_GET[ APP_BUILDER_CHECKOUT_BODY_CLASS ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_filter(
				'body_class',
				function ( $classes ) {
					return array_merge( $classes, array( $_GET[ APP_BUILDER_CHECKOUT_BODY_CLASS ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			);
		}
	}
}
