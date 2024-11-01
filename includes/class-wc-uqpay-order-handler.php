<?php

use uqpay\payment\config\security\SecurityUqpayException;
use uqpay\payment\model\PaymentResult;
use uqpay\payment\ModelHelper;
use uqpay\payment\UqpayException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles and process orders from asyncronous flows.
 *
 * @since 1.0.0
 */
class WC_UQPAY_Order_Handler extends WC_UQPAY_Payment_Gateway {
	private static $_this;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {
		self::$_this = $this;
		add_action( 'wp', array( $this, 'maybe_process_redirect_order' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_payment' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'refund_payment' ) );
	}

	/**
	 * Public access to instance object.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_instance() {
		return self::$_this;
	}

	/**
	 * @param $order_id
	 * @param null $post_client
	 * @param null $payment_result
	 *
	 * @throws WC_Data_Exception
	 */
	public function process_redirect_payment( $order_id, $post_client = null, $payment_result = null ) {
		try {
			if ( empty( $order_id ) ) {
				return;
			}

			$order = wc_get_order( $order_id );

			if ( ! is_object( $order ) ) {
				return;
			}

			if ( 'processing' === $order->get_status() || 'completed' === $order->get_status() || 'on-hold' === $order->get_status() ) {
				return;
			}
			if ( ! empty( $post_client ) ) {
				$js_params = array("uqpay_redirect_params" => $post_client);
				$this->enqueue_js_script("uqpay_redirect", $js_params);
			}

			if ( ! empty( $payment_result ) ) {
				$this->init_uqpay_api();
				if ( $payment_result['internal'] ) {
					$verify = true;
				} else {
					$verify = ModelHelper::verifyPaymentResult( $payment_result, $this->uqpay_API->getConfig()->getSecurity() );
				}
				if ( $verify ) {
					$result = ModelHelper::parseResultData( $payment_result, PaymentResult::class );
					/** @var PaymentResult $result */
					$this->process_payment_result( $result, $order );
				} else {
					WC_UQPAY_Logger::log( 'Error: not a valid uqpay result, verify signature failed. ' . print_r( $payment_result ) );

					return;
				}
			}
		} catch ( UqpayException $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );

			/* translators: error message */
			$order->update_status( 'failed', sprintf( __( 'UQPAY payment failed: %s', 'uqpay-payment-gateway' ), $e->getLocalizedMessage() ) );

			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		} catch ( ReflectionException $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );
			wc_add_notice( ' Internal Server Error', 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	}

	/**
	 * Processses the orders that are redirected.
	 *
	 * @throws WC_Data_Exception
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function maybe_process_redirect_order() {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ( empty( $_GET['client_post_url'] ) || empty( $_GET['client_post_body'] ) ) && ( empty( $_GET['uqpay_result'] ) ) ) {
			return;
		}

		$order_id = wc_clean( $_GET['order_id'] );

		if ( ! empty( $_GET['client_post_url'] ) ) {
			$post_client = array(
				"api"  => wc_clean( $_GET['client_post_url'] ),
				"body" => json_decode( base64_decode( $_GET['client_post_body'] ) ),
			);
			$this->process_redirect_payment( $order_id, $post_client );
		}
		if ( ! empty( $_GET['uqpay_result'] ) ) {
			$payment_result = $_GET;
			unset( $payment_result['page_id'] );
			unset( $payment_result['order-received'] );
			unset( $payment_result['key'] );
			unset( $payment_result['order_id'] );
			unset( $payment_result['utm_nooverride'] );
			unset( $payment_result['uqpay_result'] );
			$this->process_redirect_payment( $order_id, null, $payment_result );
		}
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing.
	 *
	 * @param int $order_id
	 *
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function capture_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		// TODO
	}

	public function cancel_payment( $order_id ) {

	}

	/**
	 * Cancel payment on refund/cancellation.
	 *
	 * @param int $order_id
	 *
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function refund_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$refunded = WC_UQPAY_Helper::is_wc_lt( '3.0' )
			? get_post_meta( $order_id, '_uqpay_refund_id', true )
			: $order->get_meta( '_uqpay_refund_id', true );
		$payment_method = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->payment_method : $order->get_payment_method();
		if ( empty( $refunded )
		     && $this->is_uqpay_payment($payment_method) ) {
			$this->process_refund( $order_id );
		}
	}

	public function is_uqpay_payment($payment_method) {
		return 'uqpay_union_online' === $payment_method
			|| 'uqpay_union_online_qr' === $payment_method
			|| 'uqpay_alipay_online_qr' == $payment_method
			|| 'uqpay_wechat_online_qr' == $payment_method;
	}
}

new WC_UQPAY_Order_Handler();
