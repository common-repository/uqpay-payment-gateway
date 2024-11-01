<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use uqpay\payment\model\PaymentOrder;
use uqpay\payment\PayMethodHelper;
use uqpay\payment\UqpayException;

/**
 * Class WC_Gateway_UQPAY_UNION_Online
 *
 * @extends WC_Payment_Gateway
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class WC_Gateway_UQPAY_UNION_Online extends WC_UQPAY_Payment_Gateway {

	/**
	 * WC_Gateway_UQPAY_UNION_Online constructor.
	 */
	public function __construct() {
		$this->id           = 'uqpay_union_online';
		$this->method_title = __( 'UnionPay Online (UQPAY)', 'uqpay-payment-gateway' );
		$this->method_id    = PayMethodHelper::UNION_SECURE_PAY;
		parent::__construct();
		// Get setting values.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
	}

	public function init_form_fields() {
		$this->form_fields = require( WC_UQPAY_PLUGIN_PATH . '/includes/admin/uqpay-union-online-settings.php' );
	}

	/**
	 * Checks to see if all criteria is met before showing payment method.
	 * @return bool
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function is_available() {
		return parent::is_available();
	}

	/**
	 * Get_icon function.
	 *
	 * @return string
	 * @version 4.0.0
	 * @since 1.0.0
	 */
	public function get_icon() {
		$icons = $this->payment_icons();

		$icons_str = '';

		$icons_str .= isset( $icons[ $this->method_id ] ) ? $icons[ $this->method_id ] : '';

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}

	public function payment_fields() {
		echo wpautop( wp_kses_post( $this->get_description() ) );
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		try {
			$payment_order               = $this->generate_payment_order( $order );
			$payment_order->return_url   = $this->get_uqpay_return_url( $order );
			$payment_order->callback_url = WC_UQPAY_Helper::get_async_callback_url();
			$process_request             = $this->uqpay_API->pay( $payment_order );
			WC_UQPAY_Logger::log( 'Info: Redirecting to UnionPay Online...' );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_uqpay_return_url( $order, null, $process_request->redirect ),
			);
		} catch ( UqpayException $ex ) {
			return $this->handlerUqpayException( $order, $order_id, $ex );
		} catch ( ReflectionException $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );
			wc_add_notice( ' Internal Server Error', 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}
}