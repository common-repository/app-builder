<?php

/**
 * class Post
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     AppCheap <ngocdt@rnlab.io>
 */

namespace AppBuilder\Api;

defined( 'ABSPATH' ) || exit;

use WP_REST_Server;
use WP_Error;
use WP_REST_Controller;

class Delete extends WP_REST_Controller {

    /**
     * @var array Allowed post types to be deleted
     */
    private $post_types_allowed = array( 'app_builder_template', 'app_builder_upgrader' );

    public function __construct() {
        $this->namespace = 'app-builder/v1';
        $this->rest_base = 'delete';
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
            )
        );
    }

    public function delete_item_permissions_check( $request ) {
        return current_user_can( 'delete_posts' );
    }

    public function delete_item( $request ) {
        $id    = (int) $request['id'];
        $force = $request['force'] ?? false;
        $post  = get_post( $id );

        if ( empty( $post ) ) {
            return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.' ), array( 'status' => 404 ) );
        }

        // Check if the post type is allowed to be deleted
        if ( ! in_array( $post->post_type, $this->post_types_allowed ) ) {
            return new WP_Error( 'rest_cannot_delete', __( 'The post cannot be deleted.' ), array( 'status' => 500 ) );
        }

        $result = wp_delete_post( $id, $force );

        if ( ! $result ) {
            return new WP_Error( 'rest_cannot_delete', __( 'The post cannot be deleted.' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( $post );
    }
}