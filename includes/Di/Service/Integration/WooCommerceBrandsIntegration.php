<?php
/**
 * WooCommerce Brands Integration.
 *
 * @package AppBuilder\Integration
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Class WooCommerceBrands
 */
class WooCommerceBrandsIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Integrations infomation.
	 *
	 * @var string $identifier infomation.
	 */
	public static $infomation = array(
		'identifier'    => 'WooCommerceBrands',
		'title'         => 'WooCommerce Brands',
		'description'   => 'Create, assign and list brands for products, and allow customers to view by brand.',
		'icon'          => 'https://woocommerce.com/wp-content/uploads/2012/09/WooCommerce_Brands_icon-marketplace-80x80-1.png',
		'url'           => 'https://woocommerce.com/products/brands/',
		'author'        => 'Woo',
		'documentation' => 'https://appcheap.io/docs/cirilla-developers-docs/integrations/woocommerce-brands/',
		'category'      => 'brand, product',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
	}

	/**
	 * Register REST API.
	 */
	public function rest_api_init() {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}
	}
}
