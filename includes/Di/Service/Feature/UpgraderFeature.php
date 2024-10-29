<?php

/**
 * Admin
 *
 * @link       https://appcheap.io
 * @since      1.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

use AppBuilder\Traits\Permission;
use AppBuilder\Classs\ItunesSearchApi;
use AppBuilder\Classs\GooglePlayInfo;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Upgrader
 */
class UpgraderFeature extends FeatureAbstract {
	use Permission;

	/**
	 * Post type key.
	 *
	 * @var string
	 */
	public string $post_type_key = 'app_builder_upgrader';

	/**
	 * Meta key.
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_upgrader_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'                  => false,
			'application_id'          => '',
			'bundle_id'               => '',
			'enable_auto_update'      => false,
			'auto_update_interval'    => 60,
			'auto_update_interval_by' => 'minutes',
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'sync_version' ), 10 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_gutenberg_editor' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'delete_post' ), 10, 2 );
		add_action( 'wp_trash_post', array( $this, 'trash_post' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'sync_app_version', array( $this, 'sync_app_version' ), 10, 2 );
	}

	/**
	 * Get form fields
	 */
	public function get_form_fields() {
		$meta_value = $this->get_data();
		return array(
			array(
				'title'      => 'Add Upgrader',
				'page'       => 'form',
				'show_panel' => false,
				'fields'     => array(
					array(
						'name'        => 'form_upgrader',
						'label'       => 'Add Upgrader',
						'placeholder' => '',
						'hint'        => '',
						'type'        => 'form_upgrader',
						'value'       => '',
					),
				),
			),
			array(
				'title'      => 'List Upgrader',
				'page'       => 'index',
				'show_panel' => false,
				'fields'     => array(
					array(
						'name'        => 'list_upgrader',
						'label'       => 'List Upgrader',
						'placeholder' => '',
						'hint'        => '',
						'type'        => 'list_upgrader',
						'value'       => '',
					),
				),
			),
			array(
				'title'      => 'Enable/Disable',
				'page'       => 'settings',
				'show_panel' => false,
				'fields'     => array(
					array(
						'name'  => 'status',
						'label' => 'Enable/Disable',
						'type'  => 'switch',
						'value' => isset( $meta_value['status'] ) ? $meta_value['status'] : false,
					),
				),
			),
			array(
				'title'  => 'App Info',
				'page'   => 'settings',
				'fields' => array(
					array(
						'name'        => 'application_id',
						'label'       => 'Application ID',
						'placeholder' => 'io.rnlab.cirilla',
						'hint'        => 'Google play application id',
						'type'        => 'text',
						'value'       => isset( $meta_value['application_id'] ) ? $meta_value['application_id'] : '',
					),
					array(
						'name'        => 'bundle_id',
						'label'       => 'Bundle ID',
						'placeholder' => 'io.rnlab.cirilla',
						'hint'        => 'IOS bundle id',
						'type'        => 'text',
						'value'       => isset( $meta_value['bundle_id'] ) ? $meta_value['bundle_id'] : '',
					),
				),
			),
			array(
				'title'  => 'Sync Version From Google Play and App Store',
				'page'   => 'settings',
				'fields' => array(
					array(
						'name'  => 'enable_auto_update',
						'label' => 'Enable Auto Update',
						'hint'  => 'Auto get update from Google Play and App Store',
						'type'  => 'switch',
						'value' => isset( $meta_value['enable_auto_update'] ) ? $meta_value['enable_auto_update'] : false,
					),
					array(
						'name'  => 'auto_update_contry',
						'label' => 'Country',
						'hint'  => 'Which country to get update from Google Play and App Store',
						'type'  => 'text',
						'value' => isset( $meta_value['auto_update_contry'] ) ? $meta_value['auto_update_contry'] : 'US',
					),
					array(
						'name'  => 'auto_update_interval',
						'label' => 'Auto Update Interval',
						'hint'  => 'Auto update interval in minutes',
						'type'  => 'number',
						'value' => isset( $meta_value['auto_update_interval'] ) ? $meta_value['auto_update_interval'] : 60,
					),
					array(
						'name'    => 'auto_update_interval_by',
						'label'   => 'Auto Update Interval By',
						'hint'    => 'Auto update interval by minutes, hours, days, weeks, months',
						'type'    => 'select',
						'options' => array(
							array(
								'label' => 'Minutes',
								'value' => 'minutes',
							),
							array(
								'label' => 'Hours',
								'value' => 'hours',
							),
							array(
								'label' => 'Days',
								'value' => 'days',
							),
							array(
								'label' => 'Weeks',
								'value' => 'weeks',
							),
							array(
								'label' => 'Months',
								'value' => 'months',
							),
						),
						'value'   => isset( $meta_value['auto_update_interval_by'] ) ? $meta_value['auto_update_interval_by'] : 'minutes',
					),
				),
			),
		);
	}

	/**
	 * Save settings
	 *
	 * @param array $data the data to save.
	 *
	 * @return void
	 */
	public function custom_set_data( $data ) {
		if ( isset( $data[ $this->meta_key ] ) ) {
			if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'sync_app_version' ) ) {
				// Cancel old schedule.
				$options = get_option( $this->meta_key, array() );
				as_unschedule_action(
					'sync_app_version',
					array(
						$options['application_id'],
						$options['bundle_id'],
					),
					'app_builder'
				);
			} else {
				delete_transient( 'app_builder_upgrader_sync' );
			}
			$this->set_data( $data );
			$this->sync_version();
		}
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'app-builder/v1',
			'/upgrader',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_upgraders' ),
					'permission_callback' => array( $this, 'public_permissions_callback' ),
				),
			)
		);
	}

	/**
	 * Sync version.
	 *
	 * @return void
	 */
	public function sync_version() {

		$options = get_option( $this->meta_key, array() );
		if ( ! isset( $options['enable_auto_update'] ) || ! $options['enable_auto_update'] ) {
			// Disable auto update.
			return;
		}

		$interval_in_seconds = 0;

		if ( isset( $options['auto_update_interval'] ) ) {
			$interval_in_seconds = intval( $options['auto_update_interval'] );
		}

		if ( isset( $options['auto_update_interval_by'] ) ) {
			switch ( $options['auto_update_interval_by'] ) {
				case 'minutes':
					$interval_in_seconds = $interval_in_seconds * MINUTE_IN_SECONDS;
					break;
				case 'hours':
					$interval_in_seconds = $interval_in_seconds * HOUR_IN_SECONDS;
					break;
				case 'days':
					$interval_in_seconds = $interval_in_seconds * DAY_IN_SECONDS;
					break;
				case 'weeks':
					$interval_in_seconds = $interval_in_seconds * WEEK_IN_SECONDS;
					break;
				case 'months':
					$interval_in_seconds = $interval_in_seconds * MONTH_IN_SECONDS;
					break;
			}
		}

		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( false === as_has_scheduled_action( 'sync_app_version' ) ) {
				as_schedule_recurring_action(
					time(),
					$interval_in_seconds,
					'sync_app_version',
					array(
						$options['application_id'],
						$options['bundle_id'],
					),
					'app_builder',
					true
				);
			}
		} else {
			// Get app_builder_upgrader_sync.
			$last_sync = get_transient( 'app_builder_upgrader_sync' );
			if ( $last_sync && ( time() - $last_sync ) < $interval_in_seconds ) {
				return;
			}
			do_action( 'sync_app_version', $options['application_id'], $options['bundle_id'] );
			set_transient( 'app_builder_upgrader_sync', time(), $interval_in_seconds );
		}
	}

	/**
	 * Sync app version
	 *
	 * @param string $package_name package name.
	 * @param string $bundle_id bundle id.
	 *
	 * @return void
	 */
	public function sync_app_version( $package_name, $bundle_id ) {
		$settings = get_option( $this->meta_key, array() );
		$country  = isset( $settings['auto_update_contry'] ) ? $settings['auto_update_contry'] : 'US';
		if ( $package_name ) {
			$google_play_version = GooglePlayInfo::get_google_play_info( $package_name, $country );
			$this->insert_version_to_db( $google_play_version );
		}

		if ( $bundle_id ) {
			$apple_store_version = $this->get_apple_store_info( $bundle_id, $country );
			$this->insert_version_to_db( $apple_store_version );
		}
	}

	/**
	 * Insert version to db.
	 *
	 * @param array $info info.
	 *
	 * @return void
	 */
	public function insert_version_to_db( $info = array() ) {

		if ( empty( $info ) ) {
			return;
		}

		// Check version is empty.
		if ( empty( $info['version'] ) ) {
			return;
		}

		$cache_key   = 'app_builder_upgrader_version_' . $info['version'] . '_' . $info['platform'];
		$has_version = get_transient( $cache_key );

		if ( 'true' === $has_version ) {
			return;
		}

		// Check if version is already exist.
		$args = array(
			'post_type'  => $this->post_type_key,
			'meta_query' => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'app_version',
					'value' => $info['version'],
				),
				array(
					'key'   => 'platform',
					'value' => $info['platform'],
				),
			),
		);

		$query       = new \WP_Query( $args );
		$has_version = $query->have_posts();

		if ( $has_version ) {
			// Increase the cache time if you want persist cache longer. But ensure to clear cache when a raw delete in database.
			set_transient( $cache_key, 'true', DAY_IN_SECONDS );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Version ' . $info['version'],
				'post_content' => $info['changelog'],
				'post_type'    => $this->post_type_key,
				'post_status'  => 'publish',
			)
		);

		if ( $post_id ) {
			update_post_meta( $post_id, 'app_version', $info['version'] );
			update_post_meta( $post_id, 'platform', $info['platform'] );
			update_post_meta( $post_id, 'envoirment', 'production' );
			update_post_meta( $post_id, 'force_upgrade', '0' );
			update_post_meta( $post_id, 'updated_date', $info['updated_date'] );
			update_post_meta( $post_id, 'sync', '1' );
		}
	}

	/**
	 * Get apple store info
	 *
	 * @param string $bundle_id bundle id.
	 * @param string $country country.
	 *
	 * @return array
	 */
	public function get_apple_store_info( $bundle_id, $country = 'US' ) {
		$itunes_search_api = new ItunesSearchApi();
		$lookup_results    = $itunes_search_api->lookupByBundleId( $bundle_id, $country, true );

		// Check data.
		if ( ! isset( $lookup_results['results'][0]['version'] ) ) {
			return array();
		}

		$date         = new \DateTime( $lookup_results['results'][0]['currentVersionReleaseDate'] );
		$updated_date = $date->format( 'M d, Y' );

		return array(
			'version'      => $lookup_results['results'][0]['version'],
			'updated_date' => $updated_date,
			'changelog'    => $lookup_results['results'][0]['releaseNotes'],
			'platform'     => 'ios',
		);
	}

	/**
	 * Get upgraders
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return array | void
	 */
	public function get_upgraders( $request ) {

		$itunes_search_api = new ItunesSearchApi();
		$format            = $request->get_param( 'format' );

		$args = array(
			'post_type'      => $this->post_type_key,
			'posts_per_page' => -1,
		);

		$query     = new \WP_Query( $args );
		$upgraders = array();

		$meta_value = get_option( $this->meta_key, array() );

		$auto_update_interval    = isset( $meta_value['auto_update_interval'] ) ? $meta_value['auto_update_interval'] : 60;
		$auto_update_interval_by = isset( $meta_value['auto_update_interval_by'] ) ? $meta_value['auto_update_interval_by'] : 'minutes';

		$interval_in_seconds = 5 * MINUTE_IN_SECONDS;

		if ( $auto_update_interval > 0 ) {

			switch ( $auto_update_interval_by ) {
				case 'hours':
					$interval_in_seconds = $auto_update_interval * HOUR_IN_SECONDS;
					break;
				case 'days':
					$interval_in_seconds = $auto_update_interval * DAY_IN_SECONDS;
					break;
				case 'weeks':
					$interval_in_seconds = $auto_update_interval * WEEK_IN_SECONDS;
					break;
				case 'months':
					$interval_in_seconds = $auto_update_interval * MONTH_IN_SECONDS;
					break;
				default:
					$interval_in_seconds = $auto_update_interval * MINUTE_IN_SECONDS;
			}
		}

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$meta = get_post_meta( get_the_ID() );

				$sync = isset( $meta['sync'] ) && $meta['sync'][0] == '1' ? '1' : '0';
                
				if ( $sync == '1' ) {
					$url = sprintf( 'https://play.google.com/store/apps/details?id=%s', isset( $meta_value['application_id'] ) ? $meta_value['application_id'] : '' );

					if ( isset( $meta['platform'] ) && 'ios' === $meta['platform'][0] ) {
						$bundle_id = isset( $meta_value['bundle_id'] ) ? $meta_value['bundle_id'] : '';
						$country   = isset( $meta_value['auto_update_contry'] ) ? $meta_value['auto_update_contry'] : '';

						$lookup_results = $itunes_search_api->lookupByBundleId( $bundle_id, $country, true );
						$url            = isset( $lookup_results['results'][0]['trackViewUrl'] ) ? $lookup_results['results'][0]['trackViewUrl'] : '';
					}
				} else {
					$url = isset( $meta['url_download'] ) ? $meta['url_download'][0] : '';
				}

				$data_upgrader = array(
					'id'             => get_the_ID(),
					'title'          => get_the_title(),
					'description'    => get_the_content(),
					'pubDate'        => get_the_date( 'D, d M Y H:i:s O' ),
					'enclosure'      => array(
						'url'     => $url,
						'version' => isset( $meta['app_version'] ) ? $meta['app_version'][0] : '',
						'os'      => isset( $meta['platform'] ) ? $meta['platform'][0] : '',
					),
					'minimumVersion' => isset( $meta['force_upgrade'] ) && $meta['force_upgrade'][0] == '1' ? '1' : '0',
				);

				$upgraders[] = $data_upgrader;

			}
		}

		if ( 'json' === $format ) {
			// Response JSON.
			return wp_send_json( $upgraders );
		}
		// Response XML.
		header( 'Content-type: text/xml' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" version="2.0">';
		echo '<channel>';

		foreach ( $upgraders as $upgrader ) {
			echo '<item>';
			echo '<title>', esc_html( $upgrader['title'] ), '</title>';
			echo '<description>', esc_html( $upgrader['description'] ), '</description>';
			echo '<pubDate>', esc_html( $upgrader['pubDate'] ), '</pubDate>';
			echo '<enclosure url="', esc_html( $upgrader['enclosure']['url'] ), '"',
				' sparkle:version="', esc_html( $upgrader['enclosure']['version'] ), '"',
				' sparkle:os="', esc_html( $upgrader['enclosure']['os'] ), '"',
				' />';
			if ( isset( $interval_in_seconds ) ) {
				echo '<sparkle:phasedRolloutInterval>', esc_html( $interval_in_seconds ), '</sparkle:phasedRolloutInterval>';
			}
			if ( '1' === $upgrader['minimumVersion'] ) {
				echo '<sparkle:shortVersionString>', esc_html( $upgrader['enclosure']['version'] ), '</sparkle:shortVersionString>';
				echo '<sparkle:tags>';
				echo '<sparkle:criticalUpdate/>';
				echo '</sparkle:tags>';
			}

			echo '</item>';
		}

		echo '</channel>';
		echo '</rss>';
		exit;
	}

	/**
	 * Register core post types.
	 */
	public function register_post_types() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Register post type for app upgrade.
		if ( ! post_type_exists( $this->post_type_key ) ) {
			register_post_type(
				$this->post_type_key,
				array(
					'labels'              => array(
						'name'               => esc_html__( 'App Upgrader', 'app-builder' ),
						'singular_name'      => esc_html__( 'App Upgrader', 'app-builder' ),
						'add_new'            => esc_html__( 'Add New Upgrader', 'app-builder' ),
						'add_new_item'       => esc_html__( 'Add New Upgrader', 'app-builder' ),
						'new_item'           => esc_html__( 'New Upgrader', 'app-builder' ),
						'edit_item'          => esc_html__( 'Edit Upgrader', 'app-builder' ),
						'view_item'          => esc_html__( 'View Upgrader', 'app-builder' ),
						'all_items'          => esc_html__( '[App] - Upgraders', 'app-builder' ),
						'search_items'       => esc_html__( 'Search Upgraders', 'app-builder' ),
						'not_found'          => esc_html__( 'No Upgrader found', 'app-builder' ),
						'not_found_in_trash' => esc_html__( 'No Upgrader found in Trash', 'app-builder' ),
					),
					'show_in_rest'        => true,
					'rest_base'           => 'app-builder-upgraders',
					'hierarchical'        => false,
					'public'              => false,
					'show_ui'             => true,
					'show_in_menu'        => 'tools.php',
					'show_in_admin_bar'   => true,
					'show_in_nav_menus'   => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'has_archive'         => false,
					'query_var'           => false,
					'can_export'          => true,
					'rewrite'             => false,
					'supports'            => array(
						'title',
						'editor',
					),
					'capability_type'     => 'post',
				)
			);
		}
	}

	/**
	 * Disable Gutenberg editor for specific post types.
	 *
	 * @param boolean $use_block_editor Whether the post type supports the block editor.
	 * @param string  $post_type         The post type being checked.
	 *
	 * @return boolean
	 */
	public function disable_gutenberg_editor( $use_block_editor, $post_type ) {
		if ( $this->post_type_key === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Register rest fields.
	 */
	public function register_rest_fields() {
		register_rest_field(
			$this->post_type_key,
			'app_version',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'app_version', true );
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'app_version', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'App version.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'platform',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'platform', true );
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'platform', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Platform.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'envoirment',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'envoirment', true );
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'envoirment', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Envoirment.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'force_upgrade',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'force_upgrade', true ) == '1' ? '1' : '0';
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'force_upgrade', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Force upgrade.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'sync',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'sync', true ) == '1' ? '1' : '0';
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'sync', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Sync.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'url_download',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'url_download', true );
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'url_download', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Url download.',
					'type'        => 'string',
				),
			)
		);

		register_rest_field(
			$this->post_type_key,
			'updated_date',
			array(
				'get_callback'    => function ( $post_arr ) {
					return get_post_meta( $post_arr['id'], 'updated_date', true );
				},
				'update_callback' => function ( $value, $post ) {
					update_post_meta( $post->ID, 'updated_date', sanitize_text_field( $value ) );
				},
				'schema'          => array(
					'description' => 'Update date.',
					'type'        => 'string',
				),
			)
		);
	}

	/**
	 * Add meta boxes to the post editor.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'app_version_meta_box',
			'App Version',
			array( $this, 'render_app_version_meta_box' ),
			$this->post_type_key
		);
	}

	/**
	 * Render the app version meta box.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_app_version_meta_box( $post ) {
		$app_version  = get_post_meta( $post->ID, 'app_version', true );
		$platform     = get_post_meta( $post->ID, 'platform', true );
		$envoirment   = get_post_meta( $post->ID, 'envoirment', true );
		$forceUpgrade = get_post_meta( $post->ID, 'force_upgrade', true );
		$sync         = get_post_meta( $post->ID, 'sync', true );
		$url_download = get_post_meta( $post->ID, 'url_download', true );
		$updated_date = get_post_meta( $post->ID, 'updated_date', true );

		$checkForceUpgrade = '';
		$valueForceUpgrade = '0';

		if ( $forceUpgrade == '1' ) {
			$checkForceUpgrade = 'checked';
			$valueForceUpgrade = '1';
		}

		$checkSync = '';
		$valueSync = '0';

		if ( $sync == '1' ) {
			$checkSync = 'checked';
			$valueSync = '1';
		}

		echo '<table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="blogname">App Version</label></th>
                <td><input name="app_version" type="text" value="' . esc_attr( $app_version ) . '" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="blogname">Platform</label></th>
                <td><input name="platform" type="text" value="' . esc_attr( $platform ) . '" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="blogname">Envoirment</label></th>
                <td><input name="envoirment" type="text" value="' . esc_attr( $envoirment ) . '" class="regular-text"></td>
            </tr>
            <th scope="row"><label for="blogname">Force Upgrade</label></th>
                <td><input type="hidden" value="0" name="force_upgrade"><input name="force_upgrade" type="checkbox" value="1" ' . esc_attr( $checkForceUpgrade ) . ' class="regular-text"></td>
            </tr>
            <th scope="row"><label for="blogname">Sync</label></th>
                <td><input type="hidden" value="0" name="sync"><input name="sync" type="checkbox" value="1" ' . esc_attr( $checkSync ) . ' class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="blogname">Url download</label></th>
                <td><input name="url_download" type="text" value="' . esc_attr( $url_download ) . '" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="blogname">Update Date</label></th>
                <td><input name="updated_date" type="date" value="' . esc_attr( $updated_date ) . '" class="regular-text"></td>
            </tr>
            </tbody>
            </table>';
	}

	/**
	 * Save post meta fields.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function save_post_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $post->post_type !== $this->post_type_key ) {
			return;
		}

		$metas = array(
			'app_version'   => 'string',
			'platform'      => 'string',
			'envoirment'    => 'string',
			'force_upgrade' => 'bool',
			'sync'          => 'bool',
			'url_download'  => 'string',
			'updated_date'  => 'string',
		);

		foreach ( $metas as $key => $type ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( 'string' === $type ) {
					update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				} elseif ( 'bool' === $type ) {
					update_post_meta( $post_id, $key, $_POST[ $key ] == '1' ? '1' : '0' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}
			}
		}
	}

	/**
	 * Delete post
	 *
	 * @param int     $post_id post id.
	 * @param WP_Post $post post object.
	 *
	 * @return void
	 */
	public function delete_post( $post_id, $post ) {
		$this->trash_post( $post_id, 'delete' );
	}

	/**
	 * Trash post
	 *
	 * @param int    $post_id post id.
	 * @param string $status status.
	 *
	 * @return void
	 */
	public function trash_post( $post_id, $status ) {
		$post_type = get_post_type( $post_id );

		if ( $post_type !== $this->post_type_key ) {
			return;
		}
		$meta = get_post_meta( $post_id );
		if ( isset( $meta['app_version'] ) ) {
			$key = 'app_builder_upgrader_version_' . $meta['app_version'][0] . '_' . $meta['platform'][0];
			delete_transient( $key );
		}
	}
}
