<?php

namespace AppBuilder;

use WP_Filesystem;
use WP_Error;

/**
 * Class Utils
 *
 * @author ngocdt@rnlab.io
 * @since 1.0.0
 */
class Utils {
	/**
	 * Returns true if we are making a REST API request for App builder.
	 *
	 * @return  bool
	 */
	public static function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$uri         = $_SERVER['REQUEST_URI'];

		if ( empty( $rest_prefix ) || empty( $uri ) ) {
			return false;
		}

		$allows = array( 'wc/store/cart', 'wc/store/checkout', 'app-builder/v1/points-and-rewards' );

		foreach ( $allows as $allow ) {
			$check = strpos( $uri, $rest_prefix . $allow ) !== false;
			if ( $check ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Download the app builder web app.
	 *
	 * @param string $download_url Download URL.
	 * @param string $version Version.
	 *
	 * @return bool|WP_Error
	 */
	public static function download( $download_url, $version ) {
		global $wp_filesystem;

		// Initialize the WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$upload = wp_get_upload_dir();
		$dir    = $upload['basedir'] . DIRECTORY_SEPARATOR . 'app-builder';
		// Check if the directory exists, if not create it.
		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			$wp_filesystem->mkdir( $path, 0755 );
		}

		$dir_ver = $dir . DIRECTORY_SEPARATOR . $version;

		if ( is_dir( $dir_ver ) ) {
			// The folder of existing will be deleted.
			$wp_filesystem->rmdir( $dir_ver, true );

		}

		$tmp_file = download_url( $download_url );

		$unzip = unzip_file( $tmp_file, $dir_ver );
		$wp_filesystem->rmdir( $tmp_file );

		if ( is_wp_error( $unzip ) ) {
			return $unzip;
		}

		return true;
	}

	/**
	 *
	 * Check vendor plugin active
	 *
	 * @return string
	 * @since 1.0.11
	 */
	public static function vendor_active(): string {
		if ( class_exists( 'WeDevs_Dokan' ) || class_exists( 'Dokan_Pro' ) ) {
			return 'dokan';
		}

		if ( class_exists( 'WCFMmp' ) ) {
			return 'wcfm';
		}

		if ( class_exists( 'WCMp' ) ) {
			return 'wcmp';
		}

		if ( class_exists( 'WC_Product_Vendors' ) ) {
			return 'wc_pv';
		}

		if ( class_exists( 'WC_Vendors' ) ) {
			return 'wc_vendors';
		}

		if ( class_exists( 'WooCommerce' ) ) {
			return 'single';
		}

		return 'blog';
	}

	/**
	 * Convert currency
	 *
	 * @param $price
	 * @param $currency
	 *
	 * @return mixed
	 */
	public static function convert_currency( $price, $currency ) {

		$default_currency = get_option( 'woocommerce_currency' ) ? get_option( 'woocommerce_currency' ) : 'USD';

		if ( class_exists( 'WCML_Multi_Currency_Prices' ) ) {

			global $woocommerce_wpml;
			if ( $default_currency == $currency || empty( $currency ) || empty( $woocommerce_wpml->multi_currency ) || empty( $woocommerce_wpml->settings['currencies_order'] ) ) {
				return $price;
			}

			return $woocommerce_wpml->multi_currency->prices->raw_price_filter( $price, $currency );
		}

		if ( function_exists( 'wmc_get_price' ) ) {
			return wmc_get_price( $price, $currency );
		}

		return $price;
	}
}
