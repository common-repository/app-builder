<?php

/**
 * WPC Linked Variation for WooCommerce
 *
 * @package AppBuilder\Integration
 */

namespace AppBuilder\Di\Service\Integration;

defined('ABSPATH') || exit;

use WP_Query;

/**
 * Class WpcLinkedVariationForWooCommerceIntegration
 */
class WpcLinkedVariationForWooCommerceIntegration implements IntegrationInterface
{
    use IntegrationTraits;

    protected static $settings = [];

    function __construct() {
        self::$settings     = (array) get_option( 'wpclv_settings', [] );
    }

    /**
     * Integrations infomation.
     *
     * @var string $identifier infomation.
     */
    public static $infomation = array(
        'identifier'    => 'WpcLinkedVariationForWooCommerceIntegration',
        'title'         => 'WPC Linked Variation for WooCommerce',
        'description'   => 'WPC Linked Variation is a sharp tool for WooCommerce store owners to make life simple and easy.',
        'icon'          => 'https://ps.w.org/wpc-linked-variation/assets/icon-128x128.png',
        'url'           => 'https://wordpress.org/plugins/wpc-linked-variation/',
        'author'        => 'WPClever',
        'documentation' => 'https://appcheap.io/docs/cirilla-developers-docs/integrations/wpc-linked-variation-for-woocommerce/',
        'category'      => 'Product Type',
    );

    /**
     * Register hooks.
     */
    public function register_hooks()
    {
        add_action('rest_api_init', array($this, 'rest_api_init'));
    }

    /**
     * Register REST API.
     */
    public function rest_api_init()
    {
        if (! class_exists('WooCommerce', false)) {
            return;
        }
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'        => 'product',
                'namespace'       => 'linked-variations',
                'data_callback'   => array($this, 'data_callback'),
                'schema_callback' => array($this, 'schema_callback'),
                'schema_type'     => ARRAY_A,
            )
        );
    }

    /**
     * Data callback.
     */
    public function data_callback($post)
    {

        $product_id = is_callable(array($post, 'get_id')) ? $post->get_id() : (! empty($post->ID) ? $post->ID : null);
        $data =  self::render($product_id);
        return is_array($data) ? $data : array();
    }

    public static function render( $product_id = null, $limit = 0, $hide = '' ) {

        if ( ! $product_id ) {
            global $product;
            $_product   = $product;
            $product_id = $_product->get_id();
        } else {
            $_product = wc_get_product( $product_id );
        }

        if ( ! $_product ) {
            return array();
        }

        $link_data = self::get_linked_data( $product_id );

        if ( empty( $link_data ) ) {
            return array();
        }

        $link_attributes = $link_data['attributes'] ?? [];
        $link_images     = $link_data['images'] ?? [];
        $link_swatches   = $link_data['swatches'] ?? [];
        $link_dropdown   = $link_data['dropdown'] ?? [];
        $hide_attributes = ! empty( $hide ) ? explode( ',', $hide ) : [];

        // get product ids
        $link_products = [];
        $link_source   = $link_data['source'] ?? 'products';
        $link_limit    = $link_data['limit'] ?? 100;
        $link_orderby  = $link_data['orderby'] ?? 'default';
        $link_order    = $link_data['order'] ?? 'default';

        if ( ( $link_source === 'products' ) && ! empty( $link_data['products'] ) ) {
            $link_products = explode( ',', $link_data['products'] );
        }

        // exclude hidden or unpurchasable
        if ( ( self::get_setting( 'exclude_hidden', 'no' ) === 'yes' ) || ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) ) {
            foreach ( $link_products as $key => $link_product_id ) {
                $link_product = wc_get_product( $link_product_id );

                if ( ! $link_product || ( ! $link_product->is_visible() && ( self::get_setting( 'exclude_hidden', 'no' ) === 'yes' ) ) || ( ( ! $link_product->is_purchasable() || ! $link_product->is_in_stock() ) && ( self::get_setting( 'exclude_unpurchasable', 'no' ) === 'yes' ) ) ) {
                    unset( $link_products[ $key ] );
                }
            }
        }

        // exclude current product
        $link_products = apply_filters( 'wpclv_linked_products', array_diff( $link_products, [ $product_id ] ), $product_id );

        if ( empty( $link_products ) ) {
            return;
        }

        $all_taxonomies     = [];
        $product_attributes = [];

        //$filter_assigned_attributes = array_filter( $_product->get_attributes(), 'wc_attributes_array_filter_visible' );
        $assigned_attributes = array_keys( $_product->get_attributes() );

        foreach ( $assigned_attributes as $assigned_attribute ) {
            $product_attributes[ $assigned_attribute ] = wc_get_product_terms( $product_id, $assigned_attribute, [ 'fields' => 'ids' ] );
        }

        $data_linked = array();

        if ( ! empty( $link_attributes ) ) {

                $link_attributes_ids = array_map( function ( $e ) {
                    return (int) filter_var( $e, FILTER_SANITIZE_NUMBER_INT );
                }, $link_attributes );

                foreach ( $link_attributes as $link_attribute ) {
                    $link_attribute_id = (int) filter_var( $link_attribute, FILTER_SANITIZE_NUMBER_INT );
                    $attribute         = wc_get_attribute( $link_attribute_id );
                    $use_images        = in_array( $link_attribute, $link_images );
                    $use_dropdown      = in_array( $link_attribute, $link_dropdown );
                    $use_swatches      = in_array( $link_attribute, $link_swatches ) && class_exists( 'WPCleverWpcvs' );

                    if ( ! $attribute || in_array( $attribute->slug, $hide_attributes ) ) {
                        continue;
                    }

                    array_push( $all_taxonomies, $attribute->slug );

                    $args          = apply_filters( 'wpclv_get_terms_args', [
                        'taxonomy'   => $attribute->slug,
                        'hide_empty' => false
                    ] );
                    $terms         = get_terms( $args );
                    $current_terms = wc_get_product_terms( $product_id, $attribute->slug, [ 'fields' => 'ids' ] );

                    if ( empty( $terms ) || empty( $current_terms ) ) {
                        continue;
                    }

                    $count           = 0;

                    $linked_products = [];

                    $data = array();

                    foreach ( $terms as $term ) {
                        if ( in_array( $term->term_id, $current_terms ) ) {
                            $data[] = self::get_product_link($term, $product_id);
                            $count ++;
                        } else {
                            $tax_query = [ 'relation' => 'AND' ];

                            $tax_query_ori = [
                                'taxonomy' => $term->taxonomy,
                                'field'    => 'id',
                                'terms'    => $term->term_id
                            ];

                            foreach ( $product_attributes as $product_attribute_key => $product_attribute ) {
                                $product_attribute_id = wc_attribute_taxonomy_id_by_name( $product_attribute_key );

                                if ( ! in_array( $product_attribute_id, $link_attributes_ids ) ) {
                                    continue;
                                }

                                if ( $term->taxonomy != $product_attribute_key ) {
                                    $tax_query[] = [
                                        'taxonomy' => $product_attribute_key,
                                        'field'    => 'id',
                                        'terms'    => $product_attribute
                                    ];
                                }
                            }

                            array_push( $tax_query, $tax_query_ori );

                            $linked_id = self::get_linked_product_id( $tax_query, $link_products, $linked_products );

                            if ( $linked_id ) {
                                $linked_products[] = $linked_id;

                                if ( ! $limit || $count < $limit ) {
                                    $data[] = self::get_product_link($term, $linked_id);
                                }

                                $count ++;
                            } else {
                                $linked_id = apply_filters( 'wpclv_get_imperfect_product', true ) ? self::get_linked_product_id( [ $tax_query_ori ], $link_products, $linked_products ) : 0;

                                if ( $linked_id ) {
                                    $linked_products[] = $linked_id;

                                    if ( ! $limit || $count < $limit ) {
                                        $data[] = self::get_product_link($term, $linked_id);
                                    }

                                    $count ++;
                                } elseif ( self::get_setting( 'hide_empty', 'yes' ) === 'no' ) {
                                    if ( ! $limit || $count < $limit ) {
                                        $data[] = self::get_product_link($term, 0);
                                    }

                                    $count ++;
                                }
                            }
                        }
                    }

                    $data_linked[] = array(
                        'attribute' => $attribute,
                        'terms'     => $data,
                    );
                }
        }

        return $data_linked;
    }

    /**
     * Schema callback.
     */
    public function schema_callback()
    {
        return array(
            'linked-variations' => array(
                'description' => __('Linked variation', 'appbuilder'),
                'type'        => 'array',
                'readonly'    => true,
            ),
        );
    }

    public static function get_product_link($term, $product_id) {
        $permalink = get_permalink($product_id);
        return array(
            'product_id' => $product_id,
            'permalink' => $permalink,
            'slug' => basename($permalink),
            'term' => $term,
        );
    }

    public static function get_linked_data( $product_id ) {
        $links = get_posts( [
            'post_type'      => 'wpclv',
            'post_status'    => 'publish',
            'posts_per_page' => - 1, // get all linked
            'fields'         => 'ids'
        ] );

        if ( ! empty( $links ) ) {
            foreach ( $links as $link_id ) {
                $link = get_post_meta( $link_id, 'wpclv_link', true );

                if ( ! empty( $link ) ) {
                    $link_source = $link['source'] ?? 'products';

                    if ( ( $link_source === 'products' ) && ! empty( $link['products'] ) ) {
                        $product_ids = explode( ',', $link['products'] );

                        if ( in_array( $product_id, $product_ids ) ) {
                            return $link;
                        }
                    }

                    if ( ( $link_source === 'categories' ) && ! empty( $link['categories'] ) ) {
                        $categories = array_map( 'trim', explode( ',', $link['categories'] ) );

                        if ( has_term( $categories, 'product_cat', $product_id ) ) {
                            return $link;
                        }
                    }

                    if ( ( $link_source === 'tags' ) && ! empty( $link['tags'] ) ) {
                        $tags = array_map( 'trim', explode( ',', $link['tags'] ) );

                        if ( has_term( $tags, 'product_tag', $product_id ) ) {
                            return $link;
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function get_settings() {
        return apply_filters( 'wpclv_get_settings', self::$settings );
    }

    public static function get_setting( $name, $default = false ) {
        if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
            $setting = self::$settings[ $name ];
        } else {
            $setting = get_option( 'wpclv_' . $name, $default );
        }

        return apply_filters( 'wpclv_get_setting', $setting, $name, $default );
    }

    // return post id
    public static function get_linked_product_id( $tax_query, $link_products = [], $linked_products = [] ) {
        if ( apply_filters( 'wpclv_exclude_linked_products', false ) ) {
            $post_in = array_diff( $link_products, $linked_products );
        } else {
            $post_in = $link_products;
        }

        if ( ! empty( $post_in ) ) {
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => 1,
                'order'          => 'ASC',
                'fields'         => 'ids',
                'post__in'       => $post_in,
                'tax_query'      => $tax_query
            ];

            if ( $filter_product = get_posts( apply_filters( 'wpclv_get_linked_product_id_args', $args, $link_products, $linked_products ) ) ) {
                return $filter_product[0];
            }
        }

        return false;
    }
}
