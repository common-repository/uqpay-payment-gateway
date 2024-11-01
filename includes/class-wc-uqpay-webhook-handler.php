<?php

use uqpay\payment\config\security\SecurityUqpayException;
use uqpay\payment\Constants;
use uqpay\payment\model\PaymentResult;
use uqpay\payment\model\RefundResult;
use uqpay\payment\ModelHelper;
use uqpay\payment\UqpayException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_UQPAY_Webhook_Handler.
 *
 * Handles async notification from UQPAY Server
 * @since 1.0.0
 */
class WC_UQPAY_Webhook_Handler extends WC_UQPAY_Payment_Gateway {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function __construct() {
		$this->init_uqpay_api();
		add_action( 'woocommerce_api_wc_uqpay_async', array( $this, 'check_for_webhook' ) );
	}

	/**
	 * Check incoming requests for UQPAY notification data and process them.
	 *
	 * @throws ReflectionException
	 * @throws SecurityUqpayException
	 * @throws UqpayException
	 * @throws WC_Data_Exception
	 */
	public function check_for_webhook() {
		if ( ( 'POST' !== $_SERVER['REQUEST_METHOD'] )
		     || ! isset( $_GET['wc-api'] )
		     || ( 'wc_uqpay_async' !== $_GET['wc-api'] )
		) {
			return;
		}
		$request_body = $_POST;
		if ( isset( $_POST[ Constants::PAY_ORDER_EXTEND_INFO ] ) ) {
			$request_body[ Constants::PAY_ORDER_EXTEND_INFO ] = str_replace( '\\', '', $_POST[ Constants::PAY_ORDER_EXTEND_INFO ] );
		}
		if ( isset( $_POST[ Constants::PAY_ORDER_CHANNEL_INFO ] ) ) {
			$request_body[ Constants::PAY_ORDER_CHANNEL_INFO ] = str_replace( '\\', '', $_POST[ Constants::PAY_ORDER_CHANNEL_INFO ] );
		}

		// Validate it to make sure it is legit.
		if ( $this->is_valid_request( $request_body ) ) {
			$this->process_webhook( $request_body );
			status_header( 200 );
			exit;
		} else {
			WC_UQPAY_Logger::log( 'Incoming webhook failed validation: ' . print_r( $request_body, true ) );
			status_header( 200 );
			exit;
		}
	}

	/**
	 * @param null $request_body
	 *
	 * @return bool
	 * @throws SecurityUqpayException
	 */
	public function is_valid_request( $request_body = null ) {
		return ModelHelper::verifyPaymentResult( $request_body, $this->uqpay_API->getConfig()->getSecurity() );
	}

	/**
	 * @param $notification
	 *
	 * @throws ReflectionException
	 * @throws WC_Data_Exception
	 */
	public function process_webhook_payment( $notification ) {
		/** @var PaymentResult $payment_result */
		$payment_result = ModelHelper::parseResultData( $notification, PaymentResult::class );
		$order          = wc_get_order( $this->parse_payment_order_id(null, $payment_result->order_id) );

		if ( ! $order ) {
			WC_UQPAY_Logger::log( 'Could not find order via order ID: ' . $payment_result->order_id . ' UQPAY order ID: ' . $payment_result->uqpay_order_id );

			return;
		}

		try {
			if ( 'processing' === $order->get_status() || 'completed' === $order->get_status() ) {
				return;
			}

			$this->process_payment_result( $payment_result, $order, false );

		} catch ( UqpayException $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );

			$statuses = array( 'pending', 'failed' );

			if ( $order->has_status( $statuses ) ) {
				$this->send_failed_order_email( $order->get_id() );
			}
		}
	}

	/**
	 * @param $notification
	 *
	 * @throws Exception
	 */
	public function process_webhook_refund( $notification ) {
		/** @var RefundResult $refund_result */
		$refund_result = ModelHelper::parseResultData( $notification, RefundResult::class );
		$order_id      = $refund_result->extend_info['origin_order_id'];
		$order         = wc_get_order( $order_id );
		if ( ! $order ) {
			WC_UQPAY_Logger::log( 'Could not find order via order ID: ' . $order_id . ' UQPAY order ID: ' . $refund_result->uqpay_order_id );

			return;
		}

		$refund_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $order_id, '_uqpay_refund_id', true ) : $order->get_meta( '_uqpay_refund_id', true );
		if ( $refund_id !== $refund_result->uqpay_order_id ) {
			WC_UQPAY_Logger::log( 'Not the same origin order, noticed refund id: ' . $refund_result->uqpay_order_id . ' the refund id get from order: ' . $refund_id );

			return;
		}

		if ( Constants::ORDER_STATE_SUCCESS == $refund_result->state ) {
			$reason = $refund_result->extend_info['reason'];
			WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_uqpay_refund_state', 'success' ) : $order->update_meta_data( '_uqpay_refund_state', 'success' );
			/* translators: 1) dollar amount 2) transaction id 3) refund message */
			$refund_message = sprintf( __( 'Refunded %1$s - UQPAY Refund ID: %2$s - Reason: %3$s.', 'uqpay-payment-gateway' ), $refund_result->amount, $refund_result->uqpay_order_id, $reason );

			$order->add_order_note( $refund_message );
			WC_UQPAY_Logger::log( 'Success: ' . html_entity_decode( wp_strip_all_tags( $refund_message ) ) );
		}
		if ( Constants::ORDER_STATE_FAILED == $refund_result->state ) {
			WC_Stripe_Logger::log( 'Error: ' . $refund_result->code . ' ' . $refund_result->message . print_r( $refund_result ) );

			WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_uqpay_refund_state', 'failed' ) : $order->update_meta_data( '_uqpay_refund_state', 'failed' );

			/* translators: 1) dollar amount 2) transaction id 3) refund failed reason */
			$refund_message = sprintf( __( 'Failed Refunded %1$s - UQPAY Refund ID: %2$s - Reason: %3$s.', 'uqpay-payment-gateway' ), $refund_result->amount, $refund_result->uqpay_order_id, $refund_result->message );
			$order->add_order_note( $refund_message );
		}
		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}

		return;

	}

	/**
	 * @param $request_body
	 *
	 * @throws ReflectionException
	 * @throws WC_Data_Exception
	 * @throws Exception
	 */
	public function process_webhook( $request_body ) {

		switch ( $request_body[ Constants::PAY_OPTIONS_TRADE_TYPE ] ) {
			case Constants::TRADE_TYPE_PAY:
				$this->process_webhook_payment( $request_body );
				break;
			case Constants::TRADE_TYPE_REFUND:
				$this->process_webhook_refund( $request_body );
				break;
		}
	}
}

new WC_UQPAY_Webhook_Handler();
