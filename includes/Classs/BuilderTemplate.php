<?php

namespace AppBuilder\Classs;

defined( 'ABSPATH' ) || exit;

/**
 * Class BuilderTemplate
 *
 * @package AppBuilder\Template
 */
class BuilderTemplate {

	/**
	 * The app builder active template id.
	 *
	 * @var int template id.
	 */
	protected int $template_id;

	/**
	 * BuilderTemplate constructor.
	 */
	public function __construct() {
		$this->template_id = (int) get_option( 'app_builder_template_active_id', 0 );
	}

	/**
	 * Get template id.
	 *
	 * @return int template id.
	 */
	public function get_template_id(): int {
		return $this->template_id;
	}

	/**
	 * Set template id.
	 *
	 * @param int $template_id template id.
	 *
	 * @return void
	 */
	public function set_template_id( int $template_id ): void {
		$this->template_id = $template_id;
	}


	/**
	 * Get template data
	 *
	 * @return array|mixed
	 */
	public function get_data() {
		/**
		 * Get post by id
		 */
		$template = get_post( $this->template_id );

		/**
		 * Get at least one template in list
		 */
		if ( ! $template ) {
			$templates = get_posts(
				array(
					'post_type'   => 'app_builder_template',
					'status'      => 'publish',
					'numberposts' => 1,
				)
			);
			$template  = count( $templates ) > 0 ? $templates[0] : null;
		}

		return is_null( $template ) ? array() : json_decode( $template->post_content, true );
	}

	/**
	 * Get screen config data.
	 *
	 * @param string $screen screen.
	 * @param string $key key.
	 * @param string $field field.
	 * @param mixed  $default_value default value.
	 *
	 * @return false|mixed
	 */
	public function get_screen_data( string $screen, string $key, string $field, $default_value ) {
		$data = $this->get_data();

		if ( ! isset( $data['screens'][ $screen ]['widgets'][ $key ]['fields'][ $field ] ) ) {
			return $default_value;
		}

		return $data['screens'][ $screen ]['widgets'][ $key ]['fields'][ $field ];
	}

	/**
	 * Get settings general
	 *
	 * @param string $field field.
	 * @param mixed  $default_value default value.
	 *
	 * @return mixed
	 */
	public function get_settings_general( string $field, $default_value ) {
		$data = $this->get_data();

		if ( ! isset( $data['settings']['general']['widgets']['general']['fields'][ $field ] ) ) {
			return $default_value;
		}

		return $data['settings']['general']['widgets']['general']['fields'][ $field ];
	}
}
