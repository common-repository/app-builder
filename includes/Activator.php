<?php

/**
 * Fired during plugin activation
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 */

namespace AppBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Activator class
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public function activate() {
		self::create_folder();
	}

	/**
	 * Create app builder folder
	 */
	private function create_folder() {
		global $wp_filesystem;

		// Initialize the WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$dir = APP_BUILDER_PREVIEW_DIR;
		if ( ! $wp_filesystem->is_dir( $dir ) ) {
			$wp_filesystem->mkdir( $dir, 0755 );
		}
		$wp_filesystem->chmod( $dir, 0755 );
	}
}
