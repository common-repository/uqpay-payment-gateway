<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use uqpay\payment\Constants;
use uqpay\payment\model\PaymentOrder;
use uqpay\payment\model\PaymentResult;
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
class WC_Gateway_UQPAY_WECHAT_QR extends WC_Gateway_UQPAY_Online_QR {


	/**
	 * WC_Gateway_UQPAY_UNION_Online constructor.
	 */
	public function __construct() {
		$this->id           = 'uqpay_wechat_online_qr';
		$this->method_id = PayMethodHelper::WECHAT_ONLINE_QR;
		$this->method_title = __( 'Wechat Online QR (UQPAY)', 'uqpay-payment-gateway' );
		parent::__construct();
	}

	public function init_form_fields() {
		$this->form_fields = require( WC_UQPAY_PLUGIN_PATH . '/includes/admin/uqpay-wechat-qr-settings.php' );
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
}