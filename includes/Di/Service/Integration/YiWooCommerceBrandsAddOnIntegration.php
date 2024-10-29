<?php
/**
 * YITH WooCommerce Brands Add-On Integration.
 *
 * @package AppBuilder\Integration
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Class YiWooCommerceBarcodesAndQrCodesIntegration
 */
class YiWooCommerceBrandsAddOnIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Integrations infomation.
	 *
	 * @var string $identifier infomation.
	 */
	public static $infomation = array(
		'identifier'    => 'YiWooCommerceBrandsAddOn',
		'title'         => 'YITH WooCommerce Brands Add-On',
		'description'   => 'A tool to show your products\' brands, generate reliability and guarantee the quality of your products.',
		'icon'          => 'https://yithemes.com/wp-content/uploads/2015/06/25_brand-add-on.svg',
		'url'           => 'https://yithemes.com/themes/plugins/yith-woocommerce-brands-add-on/',
		'author'        => 'Yithemes',
		'documentation' => 'https://appcheap.io/docs/cirilla-developers-docs/integrations/yith-woocommerce-brands-add-on/',
		'category'      => 'brand, product',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ) );
        
        add_filter( 'woocommerce_rest_product_object_query', array( $this, 'rest_api_filter_products_by_brand' ), 10, 2 );
        add_filter( 'woocommerce_rest_prepare_product', array( $this, 'rest_api_prepare_brands_to_product' ), 10, 2 ); // WC 2.6.x
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'rest_api_prepare_brands_to_product' ), 10, 2 ); // WC 3.x
	}

	/**
	 * Register REST API.
	 */
	public function rest_api_init() {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			return;
		}
	}

    /**
	 * Register REST API route for /products/brands.
	 *
	 * @since 1.5.0
	 *
	 * @return void
	 */
	public function rest_api_register_routes() {
		// WooCommerce 3.5 has moved v2 endpoints to legacy classes
		require_once APP_BUILDER_ABSPATH . 'rest-api/class-yith-brands-rest-api-v2-controller.php';

		$controllers = array(
			'Yith_Brands_REST_API_V2_Controller',
		);

		foreach ( $controllers as $controller ) {
			( new $controller() )->register_routes();
		}
	}

    /**
	 * Filters products by taxonomy yith_product_brand.
	 *
	 * @param array           $args    Request args.
	 * @param WP_REST_Request $request Request data.
	 * @return array Request args.
	 * @since 1.6.9
	 * @version 1.6.9
	 */
	public function rest_api_filter_products_by_brand( $args, $request ) {
		if ( ! empty( $request['brand'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'yith_product_brand',
				'field'    => 'term_id',
				'terms'    => $request['brand'],
			);
		}

		return $args;
	}

    /**
	 * Prepare brands in product response.
	 *
	 * @param WP_REST_Response $response   The response object.
	 * @param WP_Post|WC_Data  $post       Post object or WC object.
	 * @since 1.5.0
	 * @version 1.5.2
	 * @return WP_REST_Response
	 */
	public function rest_api_prepare_brands_to_product( $response, $post ) {
		$post_id = is_callable( array( $post, 'get_id' ) ) ? $post->get_id() : ( ! empty( $post->ID ) ? $post->ID : null );

		if ( empty( $response->data['brands'] ) ) {
			$terms = array();

			foreach ( wp_get_post_terms( $post_id, 'yith_product_brand' ) as $term ) {
				$terms[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}

			$response->data['brands'] = $terms;
		}

		return $response;
	}
}
