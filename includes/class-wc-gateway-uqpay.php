<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use uqpay\payment\model\PaymentOrder;
use uqpay\payment\PayMethodHelper;
use uqpay\payment\UqpayException;

/**
 * Class WC_Gateway_UQPAY
 * this gateway just only use to handler the general configurations of UQPAY Service
 * @extends WC_Payment_Gateway
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class WC_Gateway_UQPAY extends WC_Payment_Gateway {

	/**
	 * WC_Gateway_UQPAY constructor.
	 */
	public function __construct() {
		$this->id                 = 'uqpay';
		$this->method_title       = __( 'UQPAY Payment Gateway', 'uqpay-payment-gateway' );
		$this->title = __( 'UQPAY Payment Gateway', 'uqpay-payment-gateway' );
		/* translators: 1) merchant register url 2) merchant dashboard url */
		$this->method_description = sprintf( __( 'UQPAY Payment. <a href="%1$s" target="_blank">Sign up</a> for a UQPAY merchant, and <a href="%2$s" target="_blank">get your UQPAY account keys</a>.', 'uqpay-payment-gateway' ), 'https://merchant.uqpay.com/#/register', 'https://merchant.uqpay.cn' );
		$this->supports           = array();
		$this->form_fields = require( WC_UQPAY_PLUGIN_PATH . '/includes/admin/uqpay-settings.php' );
		$this->init_settings();
		$this->enabled              = $this->get_option( 'enabled' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	}

	public function payment_scripts() {
		wp_register_style( 'uqpay_payment_styles', plugins_url( 'assets/css/uqpay-payment-styles.css', WC_UQPAY_MAIN_FILE ), array(), WC_UQPAY_VERSION );
		wp_enqueue_style( 'uqpay_payment_styles' );
	}
}