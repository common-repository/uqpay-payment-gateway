<?php

use uqpay\payment\config\ConfigOfAPI;
use uqpay\payment\config\security\SecurityUqpayException;
use uqpay\payment\Constants;
use uqpay\payment\Gateway;
use uqpay\payment\model\OrderQuery;
use uqpay\payment\model\OrderRefund;
use uqpay\payment\model\PaymentOrder;
use uqpay\payment\model\PaymentResult;
use uqpay\payment\model\PostRedirectData;
use uqpay\payment\PayMethodHelper;
use uqpay\payment\UqpayException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Files.FileName

abstract class WC_UQPAY_Payment_Gateway extends WC_Payment_Gateway_CC {
	public $method_id = '';
	/**
	 * @var Gateway
	 */
	protected $uqpay_API;
	protected $test_mode;
	protected $main_settings;

	/**
	 * WC_UQPAY_Payment_Gateway constructor.
	 *
	 */
	public function __construct() {
		/* translators: 1) merchant register url 2) merchant dashboard url */
		$this->method_description = sprintf( __( 'All other general UQPAY settings can be adjusted <a href="%s">here</a>.', 'uqpay-payment-gateway' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=uqpay' ) );
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_form_fields();
		$this->init_uqpay_api();
		$this->init_settings();
	}


	public function init_uqpay_api() {
		$this->main_settings   = get_option( 'woocommerce_uqpay_settings' );
		$this->test_mode = 'yes' === $this->main_settings['test_mode'];
		$merchant_id     = $this->main_settings['merchant_id'];
		$prv_key         = $this->test_mode ? $this->main_settings['test_private_key'] : $this->main_settings['private_key'];
		$prv_key_type    = $this->test_mode ? $this->main_settings['test_private_key_type'] : $this->main_settings['private_key_type'];
		$pub_key         = $this->test_mode ? $this->main_settings['test_publishable_key'] : $this->main_settings['publishable_key'];

		$api_config      = ConfigOfAPI::builder(
			$prv_key,
			$prv_key_type,
			$pub_key,
			$merchant_id,
			$this->test_mode
		);
		$this->uqpay_API = new Gateway( $api_config );
		$this->uqpay_API->setHttpClient( WC_UQPAY_API::get_instance() );
	}

	public function card_scheme_icons() {
		return array(
			WC_UQPAY_Constants::CARD_SCHEME_AMEX       => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/amex.svg" class="uqpay-scheme-icon uqpay-icon" alt="American Express" />',
			WC_UQPAY_Constants::CARD_SCHEME_VISA       => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/visa.svg" class="uqpay-scheme-icon uqpay-icon" alt="Visa" />',
			WC_UQPAY_Constants::CARD_SCHEME_DISCOVER   => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/discover.svg" class="uqpay-scheme-icon uqpay-icon" alt="Discover" />',
			WC_UQPAY_Constants::CARD_SCHEME_JCB        => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/jcb.svg" class="uqpay-scheme-icon uqpay-icon" alt="JCB" />',
			WC_UQPAY_Constants::CARD_SCHEME_MASTERCARD => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/mastercard.svg" class="uqpay-scheme-icon uqpay-icon" alt="Mastercard" />',
			WC_UQPAY_Constants::CARD_SCHEME_UNION      => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/union.svg" class="uqpay-scheme-icon uqpay-icon" alt="Union Pay" />',
		);
	}

	public function payment_icons() {
		return array(
			PayMethodHelper::UNION_SECURE_PAY => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/union.svg" class="uqpay-payment-icon uqpay-icon" alt="UnionPay" />',
			PayMethodHelper::UNION_PAY_ONLINE_QR => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/union.svg" class="uqpay-payment-icon uqpay-icon" alt="UnionPay" />',
			PayMethodHelper::ALIPAY_ONLINE_QR => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/alipay.svg" class="uqpay-payment-icon uqpay-icon" alt="Alipay" />',
			PayMethodHelper::WECHAT_ONLINE_QR => '<img src="' . WC_UQPAY_PLUGIN_URL . '/assets/images/wechat.svg" class="uqpay-payment-icon uqpay-icon" alt="Wechat" />',
		);
	}

	/**
	 * Check if we need to make gateways available.
	 *
	 * @since 1.0.0
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled && !empty($this->main_settings) && 'yes' === $this->main_settings['enabled'] && ! empty( $this->uqpay_API ) && $this->uqpay_API->isAvailable() ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the Payment Order URL linked to UQPAY Merchant dashboard
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_transaction_url( $order ) {
		if ( $this->test_mode ) {
			$this->view_transaction_url = 'https://merchant.uqpay.net/#/transactions/order/detail/%s';
		} else {
			$this->view_transaction_url = 'https://merchant.uqpay.com/#/transactions/order/detail/%s';
		}

		return parent::get_transaction_url( $order );
	}


	/**
	 * Gets the return URL from redirects
	 *
	 * @param null $order
	 * @param null $id
	 * @param PostRedirectData $client_post
	 *
	 * @param array $query_args
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_uqpay_return_url( $order = null, $id = null, PostRedirectData $client_post = null, $query_args = array() ) {
		if ( is_object( $order ) ) {
			if ( empty( $id ) ) {
				$id = uniqid();
			}

			$order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
			$args     = array_merge(array(
				'order_id' => $order_id,
			), $query_args);
			if ( empty( $client_post ) ) {
				$args['utm_nooverride'] = '1';
				$args['uqpay_result']   = '1';
			} else {
				$args['client_post_url']  = $client_post->url;
				$args['client_post_body'] = base64_encode( json_encode( $client_post->body ) );
			}


			return esc_url_raw( add_query_arg( $args, $this->get_return_url( $order ) ) );
		}

		return esc_url_raw( add_query_arg( array( 'utm_nooverride' => '1' ), $this->get_return_url() ) );
	}

	/**
	 * generate the request body for the payment
	 *
	 * @param $order
	 *
	 * @return PaymentOrder
	 * @throws UqpayException
	 * @version 1.0.0
	 * @since 1.0.0
	 */
	public function generate_payment_order( WC_Order $order ) {
		// This will throw exception if not valid.
		$this->validate_minimum_order_amount($order);

		$payment_order = new PaymentOrder();
		$payment_order->method_id = $this->method_id;
		$payment_order->order_id = $this->parse_payment_order_id($order);
		/* translators: 1) website name 2) order number */
		$payment_order->trans_name = sprintf( __( '%1$s - Order %2$s', 'uqpay-payment-gateway' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() );
		$payment_order->amount = $order->get_total();
		$payment_order->currency = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->get_order_currency() : $order->get_currency();
		$payment_order->date = time();
		$payment_order->client_ip = $order->get_customer_ip_address();
		$payment_order->quantity = $order->get_item_count();
		return $payment_order;
	}

	/**
	 * @param WC_Order|null $order
	 * @param string|null $payment_order_id
	 *
	 * @param bool $read_only
	 *
	 * @return mixed
	 */
	public function parse_payment_order_id(WC_Order $order = null, $payment_order_id = null, $read_only = false) {
		if ($payment_order_id != null) {
			return preg_replace('/\[(.+?)]/', '', $payment_order_id);
		}
		$order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
		$payment_times = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $order_id, '_uqpay_pay_times', true ) : $order->get_meta( '_uqpay_pay_times', true );
		if ($read_only) {
			return $payment_times ? $order_id.'['.$payment_times.']':$order_id;
		}
		if (empty($payment_times)) {
			$payment_times = 1;
		} else {
			$payment_times += 1;
		}
		WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_uqpay_pay_times', $payment_times ) : $order->update_meta_data( '_uqpay_pay_times', $payment_times );
		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}
		return $order_id.'['.$payment_times.']';
	}

	public function handlerUqpayException(WC_Order $order, $order_id, UqpayException $ex) {
		$message = empty($ex->getLocalizedMessage()) ? $ex->getMessage() : $ex->getLocalizedMessage();
		wc_add_notice( $message, 'error' );
		WC_UQPAY_Logger::log( 'Error: ' . $ex->getMessage() );
		$statuses = array( 'pending', 'failed' );

		if ( $order->has_status( $statuses ) ) {
			$this->send_failed_order_email( $order_id );
		}

		return array(
			'result'   => 'fail',
			'redirect' => '',
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @throws UqpayException
	 */
	public function validate_minimum_order_amount( WC_Order $order ) {
		if ( $order->get_total() * 100 < 1 ) {
			/* translators: 1) dollar amount */
			throw new UqpayException( 'Did not meet minimum amount', sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'uqpay-payment-gateway' ), wc_price( 1 / 100 ) ) );
		}
	}

	/**
	 * refund an order
	 *
	 * @param int $order_id
	 * @param null $amount
	 * @param string $reason
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		$refunded = WC_UQPAY_Helper::is_wc_lt( '3.0' )
			? get_post_meta( $order_id, '_uqpay_refund_id', true )
			: $order->get_meta( '_uqpay_refund_id', true );

		if ( ! empty( $refunded ) ) {
			WC_UQPAY_Logger::log( 'The order ID: ' . $order_id . 'already has an refund authorized' );

			return false;
		}

		$orderRefund                 = new OrderRefund();
		$orderRefund->uqpay_order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $order->id, '_transaction_id', true ) : $order->get_transaction_id();
		$orderRefund->order_id       = time() . '_refund';
		$orderRefund->amount         = empty( $amount ) ? $order->get_total() : $amount;
		$orderRefund->callback_url   = WC_UQPAY_Helper::get_async_callback_url();
		$orderRefund->date           = time();
		$orderRefund->extend_info    = array(
			'origin_order_id' => WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id(),
			'reason'          => $reason
		);

		if ( empty( $this->uqpay_API ) || ! $this->uqpay_API->isAvailable() ) {
			$this->init_uqpay_api();
		}
		try {
			$refund_result = $this->uqpay_API->refund( $orderRefund );
			if ( Constants::ORDER_STATE_SUCCESS == $refund_result->state
			     || Constants::ORDER_STATE_SYNC_SUCCESS == $refund_result->state
			     || Constants::ORDER_STATE_REFUNDING ) {
				WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_uqpay_refund_id', $refund_result->uqpay_order_id ) : $order->update_meta_data( '_uqpay_refund_id', $refund_result->uqpay_order_id );

				/* translators: 1) dollar amount 2) transaction id 3) refund message */
				$refund_message = sprintf( __( 'Refund authorized (Refunded %1$s - UQPAY Refund ID: %2$s - Reason: %3$s), The async notification from UQPAY will final confirm this refund.', 'uqpay-payment-gateway' ), $amount, $refund_result->uqpay_order_id, $reason );

				$order->add_order_note( $refund_message );
				WC_UQPAY_Logger::log( 'Success: ' . html_entity_decode( wp_strip_all_tags( $refund_message ) ) );
				if ( is_callable( array( $order, 'save' ) ) ) {
					$order->save();
				}

				return true;
			}
			WC_Stripe_Logger::log( 'Error: ' . $refund_result->code . ' ' . $refund_result->message );

			return false;
		} catch ( ReflectionException $e ) {
			WC_UQPAY_Logger::log( 'UQPAY Refund error: ' . $e->getMessage() );
		} catch ( SecurityUqpayException $e ) {
			WC_UQPAY_Logger::log( 'UQPAY Refund error: ' . $e->getMessage() );
		} catch ( UqpayException $e ) {
			WC_UQPAY_Logger::log( 'UQPAY Refund error: ' . $e->getMessage() . print_r( $orderRefund ) );
		}

		return false;
	}

	/**
	 * @param PaymentResult $payment_result
	 * @param WC_Order $order
	 * @param bool $sync
	 *
	 * @return PaymentResult
	 * @throws UqpayException
	 * @throws WC_Data_Exception
	 */
	public function process_payment_result( PaymentResult $payment_result, $order, $sync = true ) {
		WC_UQPAY_Logger::log( 'Processing response: ' . print_r( $payment_result, true ) );
		$order_id = WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->id : $order->get_id();
		if ( $sync ) {
			if ( $payment_result->state == Constants::ORDER_STATE_FAILED || $payment_result->state == Constants::ORDER_STATE_SYNC_FAILED ) {
				/* translators: error code */
				throw new UqpayException( 'UQPAY payment failed: ' . $payment_result->message, sprintf( __( 'Payment error code: %s.', 'uqpay-payment-gateway' ), $payment_result->code ) );
			}
			if ($payment_result->state == Constants::ORDER_STATE_CLOSED) {
				WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_transaction_id', $payment_result->uqpay_order_id ) : $order->set_transaction_id( $payment_result->uqpay_order_id );
				/* translators: uqpay order id */
				$order->update_status( 'failed', sprintf( __( 'UQPAY payment authorized (Payment ID: %s). But the payment complete timeout.', 'uqpay-payment-gateway' ), $payment_result->uqpay_order_id ) );
			}
			if ($payment_result->state == Constants::ORDER_STATE_PAYING) {
				WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_transaction_id', $payment_result->uqpay_order_id ) : $order->set_transaction_id( $payment_result->uqpay_order_id );

				/* translators: uqpay order id */
				$order->add_order_note(sprintf( __( 'UQPAY payment authorized (Payment ID: %s). The QR Code was generated, and waiting for consumer to scan the code.', 'uqpay-payment-gateway' ), $payment_result->uqpay_order_id));

				WC()->cart->empty_cart();
			}
			if ( $payment_result->state == Constants::ORDER_STATE_SUCCESS
			     || $payment_result->state == Constants::ORDER_STATE_SYNC_SUCCESS
			) {
				WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_transaction_id', $payment_result->uqpay_order_id ) : $order->set_transaction_id( $payment_result->uqpay_order_id );

				if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
					WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order_id );
				}

				/* translators: uqpay order id */
				$order->update_status( 'on-hold', sprintf( __( 'UQPAY payment authorized (Payment ID: %s). The async notification from UQPAY will final confirm this trade.', 'uqpay-payment-gateway' ), $payment_result->uqpay_order_id ) );
			}
		} else {
			// Store charge data.
			WC_UQPAY_Helper::is_wc_lt( '3.0' ) ? update_post_meta( $order_id, '_uqpay_trans_confirmed_date', $payment_result->date ) : $order->update_meta_data( '_uqpay_trans_confirmed_date', $payment_result->date );
			if ( Constants::ORDER_STATE_FAILED == $payment_result->state ) {
				$localized_message = sprintf( __( 'Payment error code: %s.', 'uqpay-payment-gateway' ), $payment_result->code );
				$order->update_status( 'failed', $localized_message );
				throw new UqpayException( print_r( $payment_result, true ), $localized_message );
			}

			if ( Constants::ORDER_STATE_SUCCESS == $payment_result->state ) {
				$order->payment_complete( $payment_result->uqpay_order_id );
				/* translators: uqpay order id */
				$message = sprintf( __( 'UQPAY payment complete (UQPAY Order ID: %s)', 'uqpay-payment-gateway' ), $payment_result->uqpay_order_id );
				$order->add_order_note( $message );
			}
		}

		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}

		return $payment_result;
	}

	/**
	 * Sends the failed order email to admin.
	 *
	 * @param int $order_id
	 *
	 * @return null
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function send_failed_order_email( $order_id ) {
		$emails = WC()->mailer()->get_emails();
		if ( ! empty( $emails ) && ! empty( $order_id ) ) {
			$emails['WC_Email_Failed_Order']->trigger( $order_id );
		}
	}

	public function enqueue_js_script($js_name, $params = null) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$js_key = 'woocommerce_'.$js_name;
		wp_register_script( $js_key, plugins_url( 'assets/js/'. $js_name . $suffix . '.js', WC_UQPAY_MAIN_FILE ), array(), WC_UQPAY_VERSION );
		if (!empty($params)) {
			foreach ($params as $key => $value) {
				wp_localize_script( $js_key, 'wc_'.$key, apply_filters( 'wc_'.$key, $value ) );
			}
		}
		wp_enqueue_script( $js_key );
	}
}