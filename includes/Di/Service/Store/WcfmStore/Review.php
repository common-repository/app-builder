<?php
/**
 * Category file
 *
 * @link       https://appcheap.io
 * @since      4.0.0
 *
 * @author     AppCheap <ngocdt@rnlab.io>
 * @package    AppBuilder\Di\Service\Store
 */

namespace AppBuilder\Di\Service\Store\WcfmStore;

defined( 'ABSPATH' ) || exit;

use WP_REST_Response;
use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * Class Review
 *
 * @package AppBuilder\Di\Service\Store
 */
class Review extends WP_REST_Controller {

	/**
	 * Constructor
	 *
	 * @param string $rest_namespace The namespace.
	 * @param string $rest_base The rest base.
	 */
	public function __construct( $rest_namespace, $rest_base ) {
		$this->namespace = $rest_namespace;
		$this->rest_base = $rest_base;
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = $this->namespace;
		$base      = $this->rest_base;
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( true ),
				),
			)
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/schema',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_public_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/form',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_public_review_form' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get a collection of items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$store_id = $request->get_param( 'store_id' );
		if ( ! $store_id ) {
			return new WP_Error( 'cant-get', __( 'Store ID is required.', 'app-builder' ), array( 'status' => 401 ) );
		}

		$store_user = wcfmmp_get_store( $store_id );

		$paged  = max( 1, $request->get_param( 'page' ) );
		$length = $request->get_param( 'per_page' );
		$offset = ( $paged - 1 ) * $length;

		$items = $store_user->get_lastest_reviews( $offset, $length );
		if ( ! is_array( $items ) ) {
			return new WP_Error( 'cant-get', __( 'Can not get reviews.', 'app-builder' ), array( 'status' => 500 ) );
		}

		$data = array();
		foreach ( $items as $item ) {
			$itemdata = $this->prepare_item_for_response( $item, $request );
			$data[]   = $this->prepare_response_for_collection( $itemdata );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		global $WCFMmp;
		$controllers_path = $WCFMmp->plugin_path . 'controllers/reviews/';

		$item = $this->prepare_item_for_database( $request );

		include_once $controllers_path . 'wcfmmp-controller-reviews-submit.php';

		$_POST['wcfm_store_review_form'] = http_build_query( $item );

		new \WCFMmp_Reviews_Submit_Controller();

		return new WP_Error( 'cant-create', __( 'Write review Error.', 'app-builder' ), array( 'status' => 500 ) );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {

		$wcfm_store_review_category  = $request->get_param( 'wcfm_store_review_category' );
		$wcfm_review_store_id        = $request->get_param( 'wcfm_review_store_id' );
		$wcfm_review_author_id       = $request->get_param( 'wcfm_review_author_id' );
		$wcfmmp_store_review_comment = $request->get_param( 'wcfmmp_store_review_comment' );

		return array(
			'wcfm_store_review_category'  => $wcfm_store_review_category,
			'wcfm_review_store_id'        => $wcfm_review_store_id,
			'wcfm_review_author_id'       => $wcfm_review_author_id,
			'wcfmmp_store_review_comment' => $wcfmmp_store_review_comment,
		);
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed           $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		$store_id   = $request->get_param( 'store_id' );
		$store_user = wcfmmp_get_store( $store_id );

		$wcfm_review_categories = get_wcfm_marketplace_active_review_categories();
		$category_review_rating = $store_user->get_review_meta( $item->ID );

		$meta = array();
		if ( $category_review_rating && ! empty( $category_review_rating ) && is_array( $category_review_rating ) ) {
			foreach ( $wcfm_review_categories as $wcfm_review_cat_key => $wcfm_review_category ) {
				if ( isset( $category_review_rating[ $wcfm_review_cat_key ] ) ) {
					$meta[] = array(
						'category' => $wcfm_review_category['category'],
						'rating'   => $category_review_rating[ $wcfm_review_cat_key ],
					);
				}
			}
		}

		$item->meta = $meta;
		return $item;
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'store_id' => array(
				'description'       => 'Store ID.',
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Get the review form
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_public_review_form( $request ) {
		$wcfm_review_categories = get_wcfm_marketplace_active_review_categories();

		$categories = array();
		foreach ( $wcfm_review_categories as $key => $category ) {
			$item                = $category;
			$item['index']       = $key;
			$item['type']        = 'integer';
			$item['input']       = 'rate-star';
			$item['default']     = 5;
			$item['description'] = 'The rating for the category ' . $category['category'];
			$categories[ $key ]  = $item;
		}

		$form = array(
			'wcfm_store_review_category'  => array(
				'type'        => 'array',
				'input'       => 'rating',
				'description' => 'The review category.',
				'items'       => $categories,
			),
			'wcfm_review_store_id'        => array(
				'type'        => 'integer',
				'input'       => 'hidden',
				'description' => 'The store id.',
				'hidden'      => true,
			),
			'wcfm_review_author_id'       => array(
				'type'        => 'integer',
				'input'       => 'hidden',
				'description' => 'The author id.',
				'hidden'      => true,
			),
			'wcfmmp_store_review_comment' => array(
				'type'        => 'string',
				'input'       => 'textarea',
				'description' => 'The review comment.',
			),
		);

		return new WP_REST_Response( $form, 200 );
	}

	/**
	 * Get the review schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'review',
			'type'       => 'object',
			'properties' => array(
				'wcfm_store_review_category'  => array(
					'type'        => 'array',
					'description' => 'The review category.',
					'items'       => array(
						'type'        => 'integer',
						'input'       => 'rating',
						'description' => 'The rating for the category.',
					),
				),
				'wcfm_review_store_id'        => array(
					'type'        => 'integer',
					'description' => 'The store id.',
				),
				'wcfm_review_author_id'       => array(
					'type'        => 'integer',
					'description' => 'The author id.',
				),
				'wcfmmp_store_review_comment' => array(
					'type'        => 'string',
					'description' => 'The review comment.',
				),
			),
		);
	}
}
