<?php

/**
 * The Login With Firebase Phone Number feature class.
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

defined( 'ABSPATH' ) || exit;

use WP_REST_Server;
use WP_Error;
use WP_REST_Response;

const APP_BUILDER_SHOPPING_VIDEO_ADDONS             = 'app_builder_shopping_video_addons';
const APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN = 'app-builder-shopping-video-addons';

global $app_builder_db_version;
$app_builder_db_version = '1.0';

/**
 * ShoppingVideoFeature Class.
 */
class ShoppingVideo extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_shopping_video_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status' => true,
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {
		add_action( 'rest_api_init', array( $this, 'app_builder_shopping_video_rest_init' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'app_builder_shopping_video_custom_product_data_tab' ), 99, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'app_builder_shopping_video_custom_product_data_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'app_builder_shopping_video_woocommerce_process_product_meta_fields_save' ) );
		add_filter( 'app_builder_prepare_product_object', array( $this, 'app_builder_shopping_video_addons_prepare_product_object' ), 999, 3 );
		add_filter( 'rest_product_collection_params', array( $this, 'add_rand_orderby_rest_product_collection_params' ) );
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks() {}

	/**
	 * Get form fields
	 */
	public function get_form_fields() {
		$data = $this->get_data();
		return array(
			array(
				'title'      => 'Enable/Disable',
				'page'       => 'index',
				'show_panel' => false,
				'fields'     => array(
					array(
						'name'  => 'status',
						'label' => 'Enable/Disable',
						'type'  => 'switch',
						'value' => isset( $data['status'] ) ? $data['status'] : false,
					),
				),
			),
		);
	}

	/**
	 * Support filter product by rand
	 *
	 * @param $query_params
	 *
	 * @return array
	 */
	public function add_rand_orderby_rest_product_collection_params( $query_params ) {
		$query_params['orderby']['enum'][] = 'rand';

		return $query_params;
	}

	/**
	 *
	 * Add likes and liked for each product
	 *
	 * @param $data
	 * @param $post
	 * @param $request
	 *
	 * @return mixed
	 */
	public function app_builder_shopping_video_addons_prepare_product_object( $data, $post, $request ) {
		global $wpdb;

		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		$meta_data  = $data['meta_data'] ?? array();
		$user_id    = $request->get_param( 'user_id' ) ? (int) $request->get_param( 'user_id' ) : 0;
		$table_name = $wpdb->prefix . APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes';
		$post_id    = (int) $data['id'];

		$likes = get_post_meta( $post_id, APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes', true );

		$meta_data[] = array(
			'key'   => 'app_builder_shopping_video_likes',
			'value' => empty( $likes ) ? 0 : (int) $likes,
		);

		if ( $user_id > 0 ) {

			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND user_id = %d",
					array( $post_id, $user_id )
				)
			);

			$meta_data[] = array(
				'key'   => 'app_builder_shopping_video_liked',
				'value' => $total > 0 ? 'true' : 'false',
			);

		}

		$data['meta_data'] = $meta_data;

		return $data;
	}

	public function app_builder_shopping_video_woocommerce_process_product_meta_fields_save( $post_id ) {
		$input_name = '_' . APP_BUILDER_SHOPPING_VIDEO_ADDONS;

		$url         = $_POST[ $input_name . '_video_url' ] ?? '';
		$name        = $_POST[ $input_name . '_video_name' ] ?? '';
		$description = $_POST[ $input_name . '_video_description' ] ?? '';

		update_post_meta( $post_id, $input_name . '_video_url', $url );
		update_post_meta( $post_id, $input_name . '_video_name', $name );
		update_post_meta( $post_id, $input_name . '_video_description', $description );
	}

	public function app_builder_shopping_video_custom_product_data_tab( $product_data_tabs ) {
		$product_data_tabs[ APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ] = array(
			'label'  => __( 'Shopping Video', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
			'target' => APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN,
		);

		return $product_data_tabs;
	}

	public function app_builder_shopping_video_custom_product_data_fields() {
		global $woocommerce, $post;
		$name = '_' . APP_BUILDER_SHOPPING_VIDEO_ADDONS;
		?>
		<!-- id below must match target registered in above app_builder_shopping_video_custom_product_data_tab function -->
		<div id="<?php echo APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN; ?>" class="panel woocommerce_options_panel">
			<?php
			woocommerce_wp_text_input(
				array(
					'id'          => $name . '_video_url',
					'label'       => __( 'Video URL', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'description' => __( 'My Custom Field Description', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'default'     => '',
					'desc_tip'    => true,
					'value'       => get_post_meta( get_the_ID(), $name . '_video_url', true ),
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => $name . '_video_name',
					'label'       => __( 'Video Name', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'description' => __( 'My Custom Field Description', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'default'     => '',
					'desc_tip'    => true,
					'value'       => get_post_meta( get_the_ID(), $name . '_video_name', true ),
				)
			);
			woocommerce_wp_textarea_input(
				array(
					'id'          => $name . '_video_description',
					'label'       => __( 'Video Description', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'description' => __( 'My Custom Field Description', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ),
					'default'     => '',
					'desc_tip'    => true,
					'value'       => get_post_meta( get_the_ID(), $name . '_video_description', true ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * API like video
	 *
	 * @param $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function app_builder_shopping_video_like_video( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes';

		if ( empty( $request->get_param( 'post_id' ) ) ) {
			return new WP_Error( 'error', __( 'Post ID not provider.', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ) );
		}

		// Type is one of [positive, negative]
		$type             = $request->get_param( 'type' ) ?? 'positive';
		$post_id          = (int) $request->get_param( 'post_id' );
		$user_id          = get_current_user_id();
		$guest_like_video = apply_filters( APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_guess_like_video', true, $request );
		$likes_meta       = get_post_meta( $post_id, APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes', true );
		$likes            = empty( $likes_meta ) ? 0 : (int) $likes_meta;
		$like_type        = 'like';

		// Do not allow user like video when logged out
		if ( $user_id == 0 && ! $guest_like_video ) {
			return new WP_Error( 'error', __( 'User not logged.', APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN ) );
		}

		if ( $user_id == 0 && $guest_like_video ) {
			if ( $type == 'positive' ) {
				++$likes;
			} else {
				--$likes;
				$like_type = 'unlike';
			}
		} else {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND user_id = %d",
					$post_id,
					$user_id
				)
			);

			if ( $total > 0 ) {
				// The user unlike
				--$likes;
				$like_type = 'unlike';
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM ' . $table_name . ' WHERE user_id = %d AND post_id = %d',
						$user_id,
						$post_id
					)
				);
			} else {
				++$likes;
				$wpdb->query(
					$wpdb->prepare(
						'INSERT INTO ' . $table_name . ' (`user_id`, `post_id`) VALUES (%d, %s)',
						$user_id,
						$post_id,
					)
				);
			}
		}

		update_post_meta( $post_id, APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes', $likes );

		$result = array(
			'likes' => $likes,
			'type'  => $like_type,
		);

		return new WP_REST_Response( $result );
	}

	public function app_builder_shopping_video_rest_init() {
		$namespace = APP_BUILDER_SHOPPING_VIDEO_ADDONS_TEXT_DOMAIN . '/v1';
		$route     = 'likes';

		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'app_builder_shopping_video_like_video' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Set data
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function set_data( $data ) {
		// Check if enable is set to true create table
		if ( isset( $data[ $this->meta_key ] ) && $data[ $this->meta_key ]['status'] == true ) {
			global $wpdb;
			global $app_builder_db_version;

			$table_name = $wpdb->prefix . APP_BUILDER_SHOPPING_VIDEO_ADDONS . '_likes';

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id mediumint(9) NOT NULL,
                user_id mediumint(9) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			add_option( APP_BUILDER_SHOPPING_VIDEO_ADDONS, $app_builder_db_version );
		}
		parent::set_data( $data );
	}
}
