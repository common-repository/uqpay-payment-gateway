<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use uqpay\payment\config\security\SecurityUqpayException;
use uqpay\payment\Constants;
use uqpay\payment\model\OrderQuery;
use uqpay\payment\model\PaymentOrder;
use uqpay\payment\model\PaymentResult;
use uqpay\payment\UqpayException;

/**
 * Class WC_Gateway_UQPAY_UNION_Online
 *
 * @extends WC_Payment_Gateway
 *
 * @since 1.2.0
 * @version 1.2.0
 */
abstract class WC_Gateway_UQPAY_Online_QR extends WC_UQPAY_Payment_Gateway {
	public $messageShowOnPayPage = '';

	/**
	 * WC_Gateway_UQPAY_UNION_Online constructor.
	 */
	public function __construct() {
		parent::__construct();
		// Get setting values.
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->enabled              = $this->get_option( 'enabled' );
		$this->messageShowOnPayPage = $this->get_option( 'checkout_message' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_receipt_'.$this->id, array( $this, 'prepare_qr_pay_page' ) );
		add_action( 'woocommerce_api_wc_uqpay_query_order', array( $this, 'order_query_ajax' ) );
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

		$icons_str .= isset( $icons[$this->method_id] ) ? $icons[ $this->method_id ] : '';

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}

	public function payment_fields() {
		echo wpautop( wp_kses_post( $this->get_description() ) );
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		try {
			$payment_order = $this->generate_payment_order($order);
			$payment_order->callback_url = WC_UQPAY_Helper::get_async_callback_url();
			$payment_order->scan_type = Constants::QR_CODE_SCAN_BY_CONSUMER;
			$payment_result = $this->uqpay_API->pay( $payment_order );
			$this->save_qr_code( $order, $payment_result );
			WC_UQPAY_Logger::log( 'Info: Generating the '.$this->id.'...' );
			$this->process_payment_result($payment_result, $order);
			return array(
				'result'   => 'success',
				'redirect' => $redirect_url = add_query_arg( 'wc-uqpay-online-qr', 1, $order->get_checkout_payment_url( true ) ),
			);
		} catch ( UqpayException $ex ) {
			return $this->handlerUqpayException($order, $order_id, $ex);
		} catch ( ReflectionException $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );
			wc_add_notice( ' Internal Server Error', 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		} catch ( WC_Data_Exception $e ) {
			WC_UQPAY_Logger::log( 'Error: ' . $e->getMessage() );
			wc_add_notice( ' Internal Server Error', 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * @param WC_Order $order
	 * @param PaymentResult $payment_result
	 */
	public function save_qr_code( $order, $payment_result ) {
		$order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
		if ( WC_UQPAY_Helper::is_wc_lt( '3.0' ) ) {
			update_post_meta( $order_id, '_uqpay_qr_payload', $payment_result->qr_payload );
			update_post_meta( $order_id, '_uqpay_qr_url', $payment_result->qr_url );
		} else {
			$order->update_meta_data( '_uqpay_qr_payload', $payment_result->qr_payload );
			$order->update_meta_data( '_uqpay_qr_url', $payment_result->qr_url );
		}
		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}
	}

	public function get_qr_code( $order, $url = true ) {
		$order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
		$meta_key = $url ? '_uqpay_qr_url' : '_uqpay_qr_payload';
		if ( WC_UQPAY_Helper::is_wc_lt( '3.0' ) ) {
			$qr = get_post_meta( $order_id, $meta_key, true );
		} else {
			$qr = $order->get_meta( $meta_key, true );
		}
		if ( ! $qr ) {
			return false;
		}

		return $qr;
	}

	public function prepare_qr_pay_page( $order_id ) {
		if ( ! is_wc_endpoint_url( 'order-pay' ) || ! isset( $_GET['wc-uqpay-online-qr'] ) ) { // wpcs: csrf ok.
			return $order_id;
		}
		$order = wc_get_order( $order_id );
		$qr_url = $this->get_qr_code($order);
		echo
		'<div id="uqpay_qr_container">
			<p class="uqpay_qr_tips">'.$this->messageShowOnPayPage.'</p>
			<img class="uqpay_qr_img" src="'.$qr_url.'" alt="qr image">
		</div>';
		$this->enqueue_js_script("uqpay_qr", array("uqpay_order_query_params" => array(
			"order_id" => $this->parse_payment_order_id($order, null, true),
			"action" => 'wc_uqpay_order_query',
			"query_url" => wp_nonce_url(WC_UQPAY_Helper::get_uqpay_query_order_url(), 'uqpay_order_query_'.$order_id, 'uqpay_order_query_nonce')
		)));
		return array();
	}

	public function order_query_ajax() {
		$check_login = isset($this->main_settings['check_login']) && 'yes' == $this->main_settings['check_login'];
		if (!$check_login || ($check_login && is_user_logged_in())) {
			if ( ( 'POST' !== $_SERVER['REQUEST_METHOD'] )
			     || ! isset( $_GET['wc-api'] )
			     || ( 'wc_uqpay_query_order' !== $_GET['wc-api'] )
			) {
				return;
			}
			$real_order_id = $this->parse_payment_order_id(null,$_POST['order_id']);
			if (!check_ajax_referer('uqpay_order_query_'.$real_order_id, 'uqpay_order_query_nonce')) {
				return;
			}
			$order_id = $_POST['order_id'];
			$order_query = new OrderQuery();
			$order_query->order_id = $order_id;
			$order_query->date = time();
			$result = array(
				"success" => false,
				"message" => '',
				"data" => ''
			);
			try {
				$query_result = $this->uqpay_API->query( $order_query );
				$result['success'] = true;
				$result['data'] = array(
					"state" => $query_result->state
				);
				if ($query_result->state != Constants::ORDER_STATE_PAYING) {
					$payment_result = array(
						"state" => $query_result->state,
						"code" => $query_result->code,
						"message" => $query_result->message,
						Constants::RESULT_UQPAY_ORDER_ID => $query_result->uqpay_order_id,
						"date" => $query_result->date,
						"internal" => true
					);
					$order = wc_get_order($real_order_id);
					$result['data']['redirect'] = $this->get_uqpay_return_url($order, null, null, $payment_result);
				}
			} catch ( ReflectionException $e ) {
				$result['message'] = $e->getMessage();
			} catch ( SecurityUqpayException $e ) {
				$result['message'] = $e->getMessage();
			} catch ( UqpayException $e ) {
				$result['message'] = $e->getMessage();
			}
			echo json_encode($result);
			die();
		}
		wp_die('0', 400);
	}
}