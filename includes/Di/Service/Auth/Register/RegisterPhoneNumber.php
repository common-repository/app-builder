<?php

namespace AppBuilder\Di\Service\Auth\Register;

use WP_REST_Request;
use Wp_User;
use WP_Error;
use AppBuilder\Di\App\Http\HttpClientInterface;
use AppBuilder\Classs\RawQuery;
use AppBuilder\Classs\PhoneNumber;
use Exception;

/**
 * RegisterPhoneNumber class.
 *
 * @link       https://appcheap.io
 * @since      5.0.0
 * @author     ngocdt
 */
class RegisterPhoneNumber implements RegisterInterface {
	/**
	 * Http client.
	 *
	 * @var HttpClientInterface
	 */
	protected $http_client;

	/**
	 * Constructor.
	 *
	 * @param HttpClientInterface $http_client Http client.
	 */
	public function __construct( HttpClientInterface $http_client ) {
		$this->http_client = $http_client;
	}

	/**
	 * Login action.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return Wp_User|WP_Error Response object.
	 */
	public function register( WP_REST_Request $request ) {
		$phone = $request->get_param( 'phone' );
		$role  = $request->get_param( 'role' );

		try {
			$phone_util = new PhoneNumber( $phone );

			// Parse phone number.
			$is_valid = $phone_util->is_valid();
			if ( ! $is_valid ) {
				return new WP_Error(
					'app_builder_register_error',
					__( 'Your phone number not validate', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			}

			// Check phone number in database.
			$digits_phone    = $phone_util->format_e164();
			$digits_phone_no = $phone_util->format_national();

			/**
			 * Get user ids by phone number
			 */
			$user_ids = RawQuery::get_user_ids_by_meta( 'digits_phone', $digits_phone );

			/**
			 * Return login data if user already exist in the database
			 */
			if ( count( $user_ids ) > 0 ) {
				return new WP_Error(
					'app_builder_register_error',
					__( 'Your phone number already exist', 'app-builder' ),
					array(
						'status' => 403,
					)
				);
			} else {
				// Delete cache save empty user_ids.
				wp_cache_delete( 'user_ids_' . md5( 'digits_phone_' . $digits_phone ), 'app_builder_user_ids' );
			}

			$user_login = str_replace( '+', '', $digits_phone );

			if ( username_exists( $user_login ) ) {
				$user_login = wp_generate_uuid4();
			}

			$userdata = array(
				'user_login' => $user_login,
				'role'       => $role,
				'user_pass'  => wp_generate_password(),
			);

			$user_id = wp_insert_user( $userdata );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			// Update phone number for user.
			add_user_meta( $user_id, 'digt_countrycode', $phone_util->contry_code(), true );
			add_user_meta( $user_id, 'digits_phone_no', str_replace( ' ', '', $digits_phone_no ), true );
			add_user_meta( $user_id, 'digits_phone', $digits_phone, true );

			do_action( 'app_builder_after_insert_user', $user_id, $request );

			$user = get_user_by( 'id', $user_id );
			return $user;

		} catch ( Exception $e ) {
			return new WP_Error(
				'app_builder_register_error',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}
	}
}
