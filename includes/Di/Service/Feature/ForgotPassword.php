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

/**
 * JwtLogin Class.
 */
class ForgotPassword extends FeatureAbstract {

	/**
	 * Meta key
	 *
	 * @var string
	 */
	public const META_KEY = 'app_builder_forgot_password_settings';

	/**
	 * Post_Types constructor.
	 */
	public function __construct() {
		$this->meta_key         = self::META_KEY;
		$this->default_settings = array(
			'status'                          => false,
			'otp_expiration_time'             => 1,
			'otp_attempt_limit'               => 3,
			'otp_verification_block_duration' => 15,
		);
	}

	/**
	 * Register feature activation hooks.
	 *
	 * @return void
	 */
	public function activation_hooks() {}

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
						'value' => isset( $data['status'] ) ? $data['status'] : true,
					),
				),
			),
			array(
				'title'  => 'Settings',
				'page'   => 'index',
				'fields' => array(
					array(
						'name'  => 'otp_expiration_time',
						'label' => 'OTP Expiration Time(minutes)',
						'hint'  => 'Specify the time duration (in minutes) after which the OTP will expire and become invalid.',
						'type'  => 'number',
						'value' => isset( $data['otp_expiration_time'] ) ? $data['otp_expiration_time'] : 1,
					),
					array(
						'name'  => 'otp_attempt_limit',
						'label' => 'OTP Attempt Limit',
						'hint'  => 'Specify the number of attempts allowed to verify the OTP.',
						'type'  => 'number',
						'value' => isset( $data['otp_attempt_limit'] ) ? $data['otp_attempt_limit'] : 3,
					),
					array(
						'name'  => 'otp_verification_block_duration',
						'label' => 'OTP Verification Block Duration (minutes)',
						'hint'  => 'Specify the time duration (in minutes) for which the user will be blocked from verifying the OTP after reaching the maximum number of attempts.',
						'type'  => 'number',
						'value' => isset( $data['otp_verification_block_duration'] ) ? $data['otp_verification_block_duration'] : 15,
					),
				),
			),
		);
	}
}
