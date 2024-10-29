<?php
/**
 * FeatureFactory
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Feature;

use AppBuilder\Di\App\Exception\FeatureException;
use AppBuilder\Di\App\Http\HttpClientInterface;

/**
 * Class FeatureFactory
 */
class FeatureFactory {
	/**
	 * Store the class names of the features.
	 *
	 * @var array $features Features.
	 */
	private $features = array();

	/**
	 * Store the instance of the class.
	 *
	 * @var array $instance Instance.
	 */
	private $instances;

	/**
	 * Http client.
	 *
	 * @var HttpClientInterface $http_client Http client.
	 */
	private $http_client;

	/**
	 * FeatureFactory constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
		$this->features    = array(
			JwtAuthentication::META_KEY        => JwtAuthentication::class,
			LoginFacebook::META_KEY            => LoginFacebook::class,
			LoginFirebasePhoneNumber::META_KEY => LoginFirebasePhoneNumber::class,
			CustomIconFeature::META_KEY        => CustomIconFeature::class,
			UpgraderFeature::META_KEY          => UpgraderFeature::class,
			ShoppingVideo::META_KEY            => ShoppingVideo::class,
			ForgotPassword::META_KEY           => ForgotPassword::class,
			Captcha::META_KEY                  => Captcha::class,
		);
	}

	/**
	 * Register admin hooks.
	 */
	public function init(): void {

		add_action( 'app_builder_features_post', array( $this, 'app_builder_features_post' ), 10, 1 );

		foreach ( array_keys( $this->features ) as $feature ) {
			$instance = $this->get_feature( $feature );

			add_filter( 'app_builder_features', array( $instance, 'register_form_fields' ), 1, 1 );
			add_filter( 'app_builder_features_public_data', array( $instance, 'get_public_data' ), 10, 1 );

			if ( $instance->is_active() ) {
				$instance->activation_hooks();
			}

			$instance->register_hooks();
		}
	}

	/**
	 * App builder features post
	 *
	 * @param array $data Data.
	 */
	public function app_builder_features_post( $data ) {
		foreach ( array_keys( $data ) as $feature ) {

			if ( ! isset( $this->features[ $feature ] ) ) {
				continue;
			}

			$instance = $this->get_feature( $feature );
			$instance->set_data( $data );
		}
	}

	/**
	 * Get feature
	 *
	 * @param string $feature Feature.
	 *
	 * @return FeatureAbstract Feature.
	 * @throws FeatureException FeatureException.
	 */
	public function get_feature( $feature ) {

		// Check if the feature exists.
		if ( ! isset( $this->features[ $feature ] ) ) {
			throw new FeatureException( 'Feature not found' );
		}

		if ( ! isset( $this->instances[ $feature ] ) ) {
			$this->instances[ $feature ] = new $this->features[ $feature ]();
		}

		$class_name = $this->features[ $feature ];

		return new $class_name();
	}

	/**
	 * Get all features
	 *
	 * @return array
	 */
	public function get_all_features() {
		return $this->features;
	}
}
