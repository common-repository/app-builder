<?php
/**
 * The TranslatepressIntegration class.
 *
 * @link       https://appcheap.io
 * @since      4.2.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

use WP_Rewrite;

/**
 * TranslatepressIntegration Class.
 */
class TranslatepressIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Integrations infomation.
	 *
	 * @var string $identifier infomation.
	 */
	public static $infomation = array(
		'identifier'    => 'TranslatepressMultilingual',
		'title'         => 'Translate Multilingual sites - TranslatePress',
		'description'   => 'Experience a better way to translate your WordPress site and go multilingual, directly from the front-end using a visual translation interface.',
		'icon'          => 'https://ps.w.org/translatepress-multilingual/assets/icon.svg',
		'url'           => 'https://wordpress.org/plugins/translatepress-multilingual/',
		'author'        => 'Cozmoslabs, Razvan Mocanu, Madalin Ungureanu, Cristophor Hurduban',
		'documentation' => 'https://appcheap.io/docs/cirilla-developers-docs/integrations/translate-multilingual-sites-translatepress/',
		'category'      => 'Translate, Multilingual',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'trp_get_lang_from_url_string', array( $this, 'trp_gp_get_lang_from_get' ), 10, 2 );
	}

	/**
	 * Rest API Init (Any filter or action that needs to be added to the REST API should be added here)
	 */
	public function rest_api_init() {
		// The TranslatePress plugin is not active, do nothing.
		if ( ! $this->trp_gp_is_tp_active() ) {
			return;
		}
		/**
		 * Translate product name
		 */
		add_filter( 'woocommerce_product_get_name', array( $this, 'woocommerce_product_get_name' ), 10, 2 );

		/**
		 * Prepare settings data - Add languages to the app builder settings api
		 */
		add_filter( 'app_builder_prepare_settings_data', array( $this, 'app_builder_prepare_settings_data' ), 100, 1 );

		/**
		 * Prepare product object
		 */
		add_filter( 'app_builder_prepare_product_object', array( $this, 'trp_prepare_product_object' ), 100, 3 );

		/**
		 * Prepare product category name
		 */
		add_filter( 'app_builder_prepare_product_category_name', array( $this, 'trp_prepare_product_category_name' ), 100, 1 );

		/**
		 * Product variable and attribute.
		 */
		add_filter( 'app_builder_prepare_product_option_object', array( $this, 'trp_prepare_product_option_object' ), 100, 1 );
		add_filter( 'app_builder_prepare_product_attribute_object', array( $this, 'trp_prepare_product_attribute_object' ), 100, 1 );
		add_filter( 'trp_prepare_product_attribute_text', array( $this, 'trp_prepare_product_attribute_text' ), 100, 1 );
		add_filter( 'wpml_translate_single_string', array( $this, 'trp_prepare_product_attribute_text' ), 100, 1 );

		/**
		 * Prepare post object
		 */
		add_filter( 'app_builder_prepare_post_object', 'trp_prepare_post_object', 100, 1 );
	}

	/**
	 * Prepare product category name
	 *
	 * @param string $name Category name.
	 *
	 * @return string Translated category name
	 */
	public function trp_prepare_product_category_name( $name ) {
		return trp_translate( $name, null, false );
	}

	/**
	 * Prepare product option object
	 *
	 * @param object $term Term object.
	 *
	 * @return object Term object
	 */
	public function trp_prepare_product_option_object( $term ) {
		$term->name = trp_translate( $term->name, null, false );
		return $term;
	}

	/**
	 * Prepare product attribute object
	 *
	 * @param object $response Response object.
	 *
	 * @return object Response object
	 */
	public function trp_prepare_product_attribute_object( $response ) {
		$response->data['name'] = trp_translate( $response->data['name'], null, false );
		return $response;
	}

	/**
	 * Prepare product attribute text
	 *
	 * @param string $text Attribute text.
	 *
	 * @return string Translated attribute text
	 */
	public function trp_prepare_product_attribute_text( $text ) {
		return trp_translate( $text, null, false );
	}

	/**
	 * Prepare post object
	 *
	 * @param array $data Post data.
	 *
	 * @return array Post data
	 */
	public function trp_prepare_post_object( $data ) {
		$data['post_title'] = trp_translate( $data['post_title'], null, false );

		$categories = array();
		if ( isset( $data['post_categories'] ) ) {
			foreach ( $data['post_categories'] as $value ) {
				if ( isset( $value ) ) {
					$value['name'] = trp_translate( $value['name'], null, false );
				}

				$categories[] = $value;
			}
		}
		$data['post_categories'] = $categories;

		$tags = array();
		if ( isset( $data['post_tags'] ) ) {
			foreach ( $data['post_tags'] as $value ) {
				if ( isset( $value ) ) {
					$value['name'] = trp_translate( $value['name'], null, false );
				}

				$tags[] = $value;
			}
		}
		$data['post_tags'] = $tags;

		return $data;
	}

	/**
	 * Prepare product object
	 *
	 * @param array  $data Product data.
	 * @param object $post Product post.
	 * @param object $request Request object.
	 *
	 * @return array Product data
	 */
	public function trp_prepare_product_object( $data, $post, $request ) {
		$data['name']        = trp_translate( $data['name'], null, false );
		$data['sku']         = trp_translate( $data['sku'], null, false );
		$data['button_text'] = trp_translate( $data['button_text'], null, false );

		$categories = array();
		if ( isset( $data['categories'] ) ) {
			foreach ( $data['categories'] as $value ) {
				if ( isset( $value ) ) {
					$value['name'] = trp_translate( $value['name'], null, false );
				}

				$categories[] = $value;
			}
		}
		$data['categories'] = $categories;

		$tags = array();
		if ( isset( $data['tags'] ) ) {
			foreach ( $data['tags'] as $value ) {
				if ( isset( $value ) ) {
					$value['name'] = trp_translate( $value['name'], null, false );
				}

				$tags[] = $value;
			}
		}
		$data['tags'] = $tags;

		$attributes = array();
		if ( isset( $data['attributes'] ) ) {
			foreach ( $data['attributes'] as $value ) {
				if ( isset( $value ) ) {
					$value['name'] = trp_translate( $value['name'], null, false );

					$options = array();
					if ( isset( $value['options'] ) && count( $value['options'] ) > 0 ) {
						foreach ( $value['options'] as $option ) {
							$options[] = trp_translate( $option, null, false );
						}
					}
					$value['options'] = $options;
				}

				$attributes[] = $value;
			}
		}
		$data['attributes'] = $attributes;

		if ( isset( $data['meta_data'] ) ) {
			$index_attributes = array_search( '_wcp_linked_variations_attributes', array_column( $data['meta_data'], 'key' ) );
			if ( is_numeric( $index_attributes ) ) {
				$meta_linked_variations_attributes = $data['meta_data'][ $index_attributes ];
				$linked_variations_attributes      = $meta_linked_variations_attributes['value'];

				$new_attributes = array();
				foreach ( $linked_variations_attributes as $value ) {
					if ( isset( $value ) ) {
						$value->name = trp_translate( $value->name, null, false );

						$terms = array();
						if ( isset( $value->terms ) && count( $value->terms ) > 0 ) {
							foreach ( $value->terms as $term ) {
								$term->name = trp_translate( $term->name, null, false );

								$terms[] = $term;
							}
						}
						$value->terms = $terms;
					}

					$new_attributes[] = $value;
				}

				$meta_linked_variations_attributes['value'] = $new_attributes;
				$data['meta_data'][ $index_attributes ]     = $meta_linked_variations_attributes;
			}
		}
		return $data;
	}

	/**
	 * Prepare settings data - Add languages to the app builder settings api
	 *
	 * @param array $data Settings data.
	 *
	 * @return array Settings data
	 */
	public function app_builder_prepare_settings_data( $data ) {

		$trp_obj      = \TRP_Translate_Press::get_trp_instance();
		$settings_obj = $trp_obj->get_component( 'settings' );
		$lang_obj     = $trp_obj->get_component( 'languages' );

		$published_lang = $settings_obj->get_setting( 'publish-languages' );
		$default_lang   = $settings_obj->get_setting( 'default-language' );

		$slugs = $settings_obj->get_setting( 'url-slugs' );

		$iso_lang       = $lang_obj->get_iso_codes( $published_lang );
		$name_translate = $lang_obj->get_language_names( $published_lang, 'english_name' );
		$name_native    = $lang_obj->get_language_names( $published_lang, 'native_name' );

		$languages = array();
		if ( isset( $published_lang ) ) {
			foreach ( $published_lang as $value ) {
				$key               = $slugs[ $value ];
				$languages[ $key ] = array(
					'code'            => $key,
					'native_name'     => $name_native[ $value ],
					'default_locale'  => $value,
					'translated_name' => $name_translate[ $value ],
					'language_code'   => $iso_lang[ $value ],
				);
				if ( $value == $default_lang ) {
					$default_lang = $key;
				}
			}
		}

		$data['languages'] = $languages;
		$data['language']  = $default_lang;

		return $data;
	}

	/**
	 * Translate product name
	 *
	 * @param string $title Product title.
	 * @param object $_product Product object.
	 *
	 * @return string Translated product title
	 */
	public function woocommerce_product_get_name( $title, $_product ) {
		$custom_title = trp_translate( $title, null, false );
		return $custom_title;
	}

	/**
	 * Get language from GET parameter
	 *
	 * @param string $lang Language code.
	 * @param string $url URL.
	 *
	 * @return string Language code
	 */
	public function trp_gp_get_lang_from_get( $lang, $url ) {
		if ( $this->is_rest() ) {
			$lang_parameter = 'lang';
			$query_string   = wp_parse_url( $url, PHP_URL_QUERY );

			if ( null !== $query_string ) {
				parse_str( $query_string, $get );
				if ( isset( $get[ $lang_parameter ] ) && '' !== $get[ $lang_parameter ] ) {
					$lang = sanitize_text_field( trim( $get[ $lang_parameter ], '/' ) );
				} else {
					$lang = null;
				}
			} else {
				$lang = null;
			}

			return $lang;
		}

		return $lang;
	}

	/**
	 * Check if the request is a REST request
	 *
	 * @return bool True if the request is a REST request, false otherwise
	 */
	public function is_rest() {
		if (
			defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
			|| isset( $_GET['rest_route'] ) // (#2)
				&& strpos( $_GET['rest_route'], '/', 0 ) === 0
		) {
			return true;
		}

		// (#3)
		global $wp_rewrite;
		if ( $wp_rewrite === null ) {
			$wp_rewrite = new WP_Rewrite();
		}

		// (#4)
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( array() ) );
		return strpos( $current_url['path'] ?? '/', $rest_url['path'], 0 ) === 0;
	}

	/**
	 * Check if TranslatePress is active
	 *
	 * @return bool True if TranslatePress is active, false otherwise
	 */
	public function trp_gp_is_tp_active() {
		// If TP is not active, do nothing.
		if ( class_exists( 'TRP_Translate_Press' ) ) {
			return true;
		} else {
			return false;
		}
	}
}
