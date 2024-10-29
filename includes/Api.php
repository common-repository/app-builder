<?php

namespace AppBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Api Class
 */
if ( ! class_exists( 'Api' ) ) {
	class Api {

		/**
		 * All API Classes
		 *
		 * @var array
		 */
		protected array $classes;

		/**
		 * Initialize
		 */
		public function __construct() {
			$this->classes = [
				Api\Cart::class,
				Api\Search::class,
				Admin\Api::class,
				Api\Post::class,
				Api\Product::class,
				Api\User::class,
				Api\Comment::class,
				Api\Review::class,
				Api\Setting::class,
				Api\Customer::class,
				Api\Country::class,
				Api\Delivery::class,
				Api\Captcha::class,
				Api\Checkout::class,
                Api\Delete::class,

				Plugin\WooCommerceBooking::class,
			];

			if ( class_exists( 'WooCommerce' ) ) {
				$this->classes[] = AdvancedRestApi\Product::class;
			}

			if ( defined( 'WCFMapi_TEXT_DOMAIN' ) ) {
				$this->classes[] = AdvancedRestApi\Order::class;
			}

			add_action( 'rest_api_init', array( $this, 'init_api' ) );
		}

		/**
		 * Register APIs
		 *
		 * @return void
		 */
		public function init_api() {
			foreach ( $this->classes as $class ) {
				$object = new $class();
				$object->register_routes();
			}

			/**
			 * Register Learning API
			 */

			$learning = false;

			/**
			 * Enable support if Master Study active
			 */
			if ( class_exists( 'STM_LMS_Reviews' ) ) {
				$learning = Lms\MasterStudy\Main::class;
			}

			if ( $learning ) {
				new $learning();
			}
		}
	}
}
