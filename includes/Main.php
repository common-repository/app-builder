<?php
/**
 * The main plugin class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 *
 * @package    AppBuilder
 */

namespace AppBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Main AppBuilder Class.
 *
 * @class AppBuilder
 *
 * @since 1.0.0
 */
final class Main {
	/**
	 * AppBuilder version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $version = '5.3.9';

	/**
	 * AppBuilder JS version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $js_version = '5.3.9';

	/**
	 * Support Cirilla stable version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $cirilla_version = '4.2.0';

	/**
	 * Support Mdelicious stable version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $mdelicious_version = '1.0.0';

	/**
	 * The plugin url.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin path.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $plugin_path;

	/**
	 * AppCheap Schema version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $db_version = '1';

	/**
	 * The single instance of the class.
	 *
	 * @var AppBuilder
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of AppCheap is loaded or can be loaded.
	 *
	 * @return Main - Main instance.
	 * @since 1.0.0
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * AppBuilder Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 4.0.0
	 */
	public function init() {
		/**
		 * Define AppBuilder Constants.
		 */
		$this->define_constants();

		/**
		 * Load init hooks.
		 */
		$this->init_hooks();

		/**
		 * Register hooks for features
		 */
		app_builder()->get( 'features' )->init();

		/**
		 * Register hooks for integrations
		 */
		app_builder()->get( 'integrations' )->init();

		/**
		 * Register RES API hooks.
		 */
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		/**
		 * Register hooks for auth
		 */
		app_builder()->get( 'auth' )->init();
	}

	/**
	 * Define AppBuilder Constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'APP_BUILDER_NAME', 'app_builder' );
		$this->define( 'APP_BUILDER_DOMAIN', 'app-builder' );
		$this->define( 'APP_BUILDER_ABSPATH', dirname( APP_BUILDER_PLUGIN_FILE ) . DIRECTORY_SEPARATOR );
		$this->define( 'APP_BUILDER_PLUGIN_BASENAME', plugin_basename( APP_BUILDER_PLUGIN_FILE ) );
		$this->define( 'APP_BUILDER_ASSETS', plugin_dir_url( APP_BUILDER_PLUGIN_FILE ) . 'assets' );
		$this->define( 'APP_BUILDER_PREVIEW_DIR', $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'app-builder' );
		$this->define( 'APP_BUILDER_VERSION', $this->version );
		$this->define( 'APP_BUILDER_CIRILLA_VERSION', $this->cirilla_version );
		$this->define( 'APP_BUILDER_JS_VERSION', $this->js_version );
		$this->define( 'APP_BUILDER_CIRILLA_VERSION', $this->cirilla_version );
		$this->define( 'APP_BUILDER_CDN_JS', 'https://cdnjs.appcheap.io/' );
		$this->define( 'APP_BUILDER_API', 'https://us-central1-app-builder-82388.cloudfunctions.net/' );
		$this->define( 'APP_BUILDER_TOKEN_PARAM_NAME', 'app-builder-token' );
		$this->define( 'APP_BUILDER_DECODE', 'app-builder-decode' );
		$this->define( 'APP_BUILDER_CHECKOUT_BODY_CLASS', 'app-builder-checkout-body-class' );
		$this->define( 'APP_BUILDER_REST_BASE', 'app-builder' );
		$this->define( 'APP_BUILDER_SHOW_UI', false );
		$this->define( 'APP_BUILDER_CART_TABLE', 'app_builder_cart' );
		$this->define( 'APP_BUILDER_CAPABILITY', 'manage_options' );
		$this->define( 'FONTAWESOME_API_URL', 'https://api.fontawesome.com' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name Constant name.
	 * @param string|bool $value Constant value.
	 *
	 * @since 1.0.0
	 */
	private function define( string $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		register_activation_hook(
			APP_BUILDER_PLUGIN_FILE,
			function () {
				app_builder()->get( 'activator' )->activate();
			}
		);
		register_deactivation_hook(
			APP_BUILDER_PLUGIN_FILE,
			function () {
				app_builder()->get( 'deactivator' )->deactivate();
			}
		);
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), - 1 );
		add_action( 'init', array( $this, 'create_gutenberg_blocks' ) );
	}

	/**
	 * Create gutenberg blocks
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function create_gutenberg_blocks() {
		register_block_type( APP_BUILDER_ABSPATH . 'blocks/build' );
	}

	/**
	 * The plugin loaded
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		if ( is_admin() ) {
			app_builder()->get( 'admin' )->init();
		} else {
			app_builder()->get( 'frontend' )->init();
		}
		new PostTypes();
		new Api();
		new I18n();
		new Hook();
	}

	/**
	 * Register REST API hooks.
	 *
	 * @return void
	 * @since 4.0.0
	 */
	public function rest_api_init() {
		// Init Vendor API.
		$vendor_name = Utils::vendor_active();
		$vendor      = app_builder()->get( 'vendor' )->create( $vendor_name );
		if ( $vendor ) {
			$vendor->register_routes();
		}

		// Init Store API.
		$store = app_builder()->get( 'store' )->create( $vendor_name );
		if ( $store ) {
			$store->register_routes();
		}

		// Init Auth API.
		$auth = app_builder()->get( 'auth' )->api_init();
	}
}
