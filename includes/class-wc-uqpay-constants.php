<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_UQPAY_Constants {
	const CALLBACK_ASYNC_API_KEY = 'wc_uqpay_async';
	const CALLBACK_SYNC_API_KEY = 'wc_uqpay_sync';

	const CARD_SCHEME_VISA = 'visa';
	const CARD_SCHEME_MASTERCARD = 'mastercard';
	const CARD_SCHEME_DISCOVER = 'discover';
	const CARD_SCHEME_AMEX = 'amex';
	const CARD_SCHEME_JCB = 'jcb';
	const CARD_SCHEME_UNION = 'union';

	const PAYMENT_NAME_UNION = 'union';
}