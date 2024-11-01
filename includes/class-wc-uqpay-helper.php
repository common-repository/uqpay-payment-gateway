<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_UQPAY_Helper {

	/**
	 * Get the async notice address for UQPAY Notification Server.
	 * when the payment status update, UQPAY Notification Server will send an message to this address
	 *
	 * @return string
	 * @version 1.0.0
	 *
	 * @since 1.0.0
	 */
	public static function get_async_callback_url() {
		return add_query_arg( 'wc-api', WC_UQPAY_Constants::CALLBACK_ASYNC_API_KEY, trailingslashit( get_home_url() ) );
	}

	public static function get_uqpay_query_order_url() {
		return add_query_arg( 'wc-api', 'wc_uqpay_query_order', trailingslashit( get_home_url() ) );
	}

	/**
	 * Get the sync notice address for UQPAY Redirect Payment
	 *
	 * Notes: for this moment we did't use this func, instead of we use {@see WC_UQPAY_Payment_Gateway::get_uqpay_return_url()}
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public static function get_sync_callback_url() {
		return add_query_arg( 'wc-api', WC_UQPAY_Constants::CALLBACK_SYNC_API_KEY, trailingslashit( get_home_url() ) );
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @param string $version Version to check against.
	 *
	 * @return bool
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public static function is_wc_lt( $version ) {
		return version_compare( WC_VERSION, $version, '<' );
	}
}