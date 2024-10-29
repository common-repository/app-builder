<?php

/**
 * SmartCouponIntegrations
 *
 * @link       https://appcheap.io
 * @since      4.2.0
 * @author     ngocdt
 * @package    AppBuilder
 */

namespace AppBuilder\Di\Service\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * RazorpayIntegration Class.
 */
class RazorpayIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Payment ID.
	 *
	 * @var string $payment_id Payment ID.
	 */
	private $payment_id = 'razorpay';

	/**
	 * App IDs.
	 *
	 * @var array $app_ids App IDs.
	 */
	private $app_ids = array( 'cirilla' );

	/**
	 * Integrations infomation.
	 *
	 * @var string $identifier infomation.
	 */
	public static $infomation = array(
		'identifier'    => 'RazorpayforWooCommerce',
		'title'         => 'Razorpay for WooCommerce',
		'description'   => 'Online Payments India: Start Accepting Payments Instantly with Razorpay\'s Payment suite, which Supports Netbanking, Credit card & Debit Cards, UPI, etc.',
		'icon'          => 'https://ps.w.org/woo-razorpay/assets/icon-128x128.png',
		'url'           => 'https://wordpress.org/plugins/woo-razorpay/',
		'author'        => 'Razorpay',
		'documentation' => 'https://appcheap.io/docs/cirilla-payment-gateway-addons/payments/razorpay/',
		'category'      => 'Payment Gateway',
	);

	/**
	 * Register hooks.
	 */
	public function register_hooks() {
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'process_payment' ), 11, 2 );
	}

	/**
	 * Process payment
	 *
	 * @param  \PaymentContext $context The payment context.
	 * @param \PaymentResult  $result  The payment result.
	 *
	 * @return void
	 */
	public function process_payment( $context, &$result ) {
		$app_id = isset( $context->payment_data['app'] ) ? $context->payment_data['app'] : '';

		if ( ! in_array( $app_id, $this->app_ids, true ) ) {
			return;
		}

		if ( $this->payment_id === $context->payment_method ) {
			$gateway  = $context->get_payment_method_instance();
			$order    = $context->order;
			$order_id = $order->get_id();

			$check_razorpay_response = isset( $context->payment_data['check_razorpay_response'] ) ? $context->payment_data['check_razorpay_response'] : false;

			if ( $check_razorpay_response ) {
				// Initiate the data for the razorpay response check.
				$_GET['order_key']                = $order->get_order_key();
				$_POST['razorpay_payment_id']     = $context->payment_data['razorpay_payment_id'];
				$_POST['razorpay_signature']      = $context->payment_data['razorpay_signature'];
				$_POST['razorpay_wc_form_submit'] = $context->payment_data['razorpay_wc_form_submit'];
				// Call the check_razorpay_response method to check the response.
				$gateway->check_razorpay_response();
			} else {
				$razo_order_id = $gateway->createOrGetRazorpayOrderId( $order, $order_id );

				$query = array(
					'wc-api'    => $gateway->id,
					'order_key' => $order->get_order_key(),
				);

				$redirect_url = add_query_arg( $query, trailingslashit( get_home_url() ) );

				if ( $razo_order_id ) {
					$result->set_payment_details(
						array(
							'razorpayOrderId' => $razo_order_id,
							'redirectUrl'     => $redirect_url,
						)
					);
				}
			}
		}
	}
}
