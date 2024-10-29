<?php

namespace AppBuilder\Di\Service\Admin;

/**
 * Admin
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class Admin {
	/**
	 * Init admin functions.
	 */
	public function init() {
		$editor = new Editor();
		$editor->init_hooks();
	}
}
