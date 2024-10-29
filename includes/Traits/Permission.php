<?php

namespace AppBuilder\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait Permission
 *
 * @author ngocdt@rnlab.io
 * @since 5.0.0
 */
trait Permission {
	/**
	 * Admin permission
	 *
	 * @return bool
	 */
	public function admin_permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Public permission
	 */
	public function public_permissions_callback() {
		return true;
	}

	/**
	 * Logged permission
	 */
	public function logged_permissions_check() {
		return is_user_logged_in();
	}
}
