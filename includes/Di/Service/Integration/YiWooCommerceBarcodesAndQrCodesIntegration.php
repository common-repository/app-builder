<?php
/**
 * Yi WooCommerce Barcodes and QR Codes Integration.
 *
 * @package AppBuilder\Integration
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Class YiWooCommerceBarcodesAndQrCodesIntegration
 */
class YiWooCommerceBarcodesAndQrCodesIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Integrations infomation.
	 *
	 * @var string $identifier infomation.
	 */
	public static $infomation = array(
		'identifier'    => 'YITHWooCommerceBarcodesandQRCodes',
		'title'         => 'YITH WooCommerce Barcodes and QR Codes',
		'description'   => 'Generate and apply barcodes and QR codes to your products automatically..',
		'icon'          => 'https://yithemes.com/wp-content/uploads/2019/05/yith-woocommerce-barcodes-and-qr-codes.svg',
		'url'           => 'https://yithemes.com/themes/plugins/yith-woocommerce-barcodes-and-qr-codes/',
		'author'        => 'Yithemes',
		'documentation' => 'https://appcheap.io/docs/cirilla-developers-docs/integrations/yith-woocommerce-barcodes-and-qr-codes/',
		'category'      => 'Search, Navigation',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	/**
	 * Register REST API.
	 */
	public function rest_api_init() {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}
		add_filter(
			'woocommerce_rest_product_object_query',
			array(
				$this,
				'woocommerce_rest_product_object_query',
			),
			10,
			2
		);
	}

	/**
	 * Filter search product by barcode value
	 *
	 * @param $args
	 * @param $request
	 *
	 * @return array
	 */
	public function woocommerce_rest_product_object_query( $args, $request ): array {
		if ( class_exists( '\YITH_Barcode' ) && isset( $request['barcode'] ) && $request['barcode'] != '' ) {
			$value = sanitize_text_field( $request['barcode'] );
			// Validate barcode value.
			if ( ! preg_match( '/^[a-zA-Z0-9]+$/', $value ) ) {
				return $args;
			}
			isset( $args['meta_query'] ) || $args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$args['meta_query'][]                               = array(
				'key'     => \YITH_Barcode::YITH_YWBC_META_KEY_BARCODE_DISPLAY_VALUE,
				'value'   => $value,
				'compare' => 'LIKE',
			);
		}
	}
}
