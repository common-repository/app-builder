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
 * SmartCouponIntegrations Class.
 */
class PaytmIntegration implements IntegrationInterface {
	use IntegrationTraits;

	/**
	 * Payment ID.
	 *
	 * @var string $payment_id Payment ID.
	 */
	private $payment_id = 'paytm';

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
		'identifier'    => 'PaytmPaymentGateway',
		'title'         => 'Paytm Payment Gateway',
		'description'   => 'Paytm Payment Gateway is ideal for Woocommerce and WordPress merchants since it allows them to give their customers a seamless, super-fast checkout experience backed by cutting-edge payments technology that powers India’s largest payments platform. Accept payments from over 100+ payment sources including credit cards, debit cards, netbanking from 50+ banks (including HDFC & SBI), UPI, wallets and Buy-now-pay-later options. Here are a few reasons why Woocommerce merchants should choose Paytm Payment Gateway.',
		'icon'          => 'https://ps.w.org/paytm-payments/assets/icon-128x128.png',
		'url'           => 'https://wordpress.org/plugins/paytm-payments/',
		'author'        => 'Paytm',
		'documentation' => 'https://appcheap.io/docs/cirilla-payment-gateway-addons/payments/paytm-gateway/',
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

			$checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
			$checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
			$callback_url     = get_site_url() . '/?page_id=' . $checkout_page_id . '&wc-api=WC_Paytm';

			$gateway = $context->get_payment_method_instance();

			$order    = $context->order;
			$order_id = $order->get_id();

			$order_id = \PaytmHelper::getPaytmOrderId( $order_id );

			$get_order_info = $gateway->getOrderInfo( $order );

			if ( ! empty( $get_order_info['email'] ) ) {
				$cust_id = $get_order_info['email'];
			} else {
				$cust_id = 'CUST_' . $order_id;
			}
			// get mobile no if there for DC_EMI.
			if ( isset( $get_order_info['contact'] ) && ! empty( $get_order_info['contact'] ) ) {
				$cust_mob_no = $get_order_info['contact'];
			} else {
				$cust_mob_no = '';
			}
			$settings = get_option( 'woocommerce_paytm_settings' );
			if ( ! isset( $settings['merchant_id'] ) || empty( $settings['merchant_id'] ) ) {
				return;
			}
			$checkout_url = str_replace( 'MID', $settings['merchant_id'], \PaytmHelper::getPaytmURL( \PaytmConstants::CHECKOUT_JS_URL, $settings['environment'] ) );

			$param_data = array(
				'amount'      => $get_order_info['amount'],
				'order_id'    => $order_id,
				'cust_id'     => $cust_id,
				'cust_mob_no' => $cust_mob_no,
			);
			$data       = $gateway->blinkCheckoutSend( $param_data );

			if ( isset( $data['txnToken'] ) && ! empty( $data['txnToken'] ) ) {
				$result->set_payment_details(
					array(
						'txnToken'     => $data['txnToken'],
						'order_id'     => $order_id,
						'callback_url' => $callback_url,
					)
				);
			}
		}
	}
}
