<?php

namespace AppBuilder;

/**
 * Class Setting
 *
 * @author ngocdt@rnlab.io
 * @since 1.0.0
 */
class Setting {

	/**
	 * Option key store in database
	 *
	 * @var string
	 */
	public $option_key = 'app_builder_settings';

	/**
	 * Get all the settings
	 *
	 * @return array
	 */
	public function settings(): array {
		$default = apply_filters(
			$this->option_key,
			array(
				'jwt'      => array(
					'secret_key' => defined( 'AUTH_KEY' ) ? AUTH_KEY : home_url( '/app' ),
					'exp'        => 30,
				),
				'facebook' => array(
					'app_id'     => '',
					'app_secret' => '',
				),
				'google'   => array(
					'key' => '',
				),
			)
		);

		$settings = get_option( $this->option_key, array() );

		return wp_parse_args( $settings, $default );
	}

	/**
	 * Get setting by key.
	 *
	 * @param string $key The key to get the setting for.
	 *
	 * @return false|mixed
	 */
	public function get( string $key ) {
		$settings = $this->settings();

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return false;
	}

	/**
	 * Update or Create settings.
	 *
	 * @param array $value The settings to update.
	 *
	 * @return bool
	 */
	public function set( array $value ): bool {
		if ( get_option( $this->option_key ) !== false ) {
			return update_option( $this->option_key, $value );
		} else {
			return add_option( $this->option_key, $value );
		}
	}

	/**
	 * Get settings of feature.
	 *
	 * @param string $feature The option name of settings.
	 *
	 * @return array the settings
	 */
	public function feature( $feature ) {
		// Not start with app_builder_.
		if ( strpos( $feature, 'app_builder_' ) !== 0 ) {
			$feature = 'app_builder_' . $feature;
		}
		// Not end with _settings.
		if ( strpos( $feature, '_settings' ) !== strlen( $feature ) - 9 ) {
			$feature .= '_settings';
		}
		$settings = get_option( $feature, array() );

		return wp_parse_args( $settings, array() );
	}
}
