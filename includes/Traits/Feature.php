<?php

namespace AppBuilder\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait Feature
 *
 * @author ngocdt@rnlab.io
 * @since 5.0.0
 */
trait Feature {

	/**
	 * Check if the feature is disabled
	 *
	 * @param array $setitng the jwt.
	 * @param bool  $default_enable the default value.
	 *
	 * @return bool the result.
	 */
	private function is_feature_disabled( $setitng, $default_enable = false ) {

		// Default the feature is enabled.
		if ( ! isset( $setitng['status'] ) ) {
			return $default_enable;
		}

		return ! filter_var( $setitng['status'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if the feature is enabled
	 *
	 * @param array $setitng the jwt.
	 * @param bool  $default_enable the default value.
	 *
	 * @return bool the result.
	 */
	private function is_feature_enabled( $setitng, $default_enable = true ) {

		// Default the feature is enabled.
		if ( ! isset( $setitng['status'] ) ) {
			return $default_enable;
		}

		return filter_var( $setitng['status'], FILTER_VALIDATE_BOOLEAN );
	}
}
