<?php

namespace AppBuilder\Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShortcodeHook.
 *
 * @link       https://appcheap.io
 * @author     ngocdt
 * @since      2.5.1
 */
class ShortcodeHook {

	/**
	 * ShortcodeHook constructor.
	 */
	public function __construct() {
		add_shortcode( 'ads', array( $this, 'ads_shortcode' ) );
	}

	/**
	 * Return custom tags.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function ads_shortcode( $atts ): string {

		$atts = shortcode_atts(
			array(
				'adSize' => 'banner',
				'width'  => '320',
				'height' => '50',
			),
			$atts,
			'ads'
		);

		$size   = esc_attr( $atts['adSize'] );
		$width  = esc_attr( $atts['width'] );
		$height = esc_attr( $atts['height'] );

		return '<div class="mobile-ads" data-size="' . $size . '" data-width="' . $width . '" data-height="' . $height . '"></div>';
	}
}
