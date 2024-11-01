<?php

/**
 * Plugin Name: Uqpay Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/uqpay-payment-gateway/
 * Description: Let your woocommerce support mainstream payment channels by UQPAY.
 * Version: 1.2.1
 * Requires at least: 4.4
 * Tested up to: 5.2.2
 * WC requires at least: 2.6
 * WC tested up to: 3.6.5
 * Author: UQPAY
 * Author URI: https://www.uqpay.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: uqpay-payment-gateway
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce fallback notice.
 *
 * @return string
 * @since 1.0.0
 */
function woocommerce_uqpay_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'UQPAY requires WooCommerce to be installed and active. You can download %s here.', 'uqpay-payment-gateway' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_uqpay_init' );

function woocommerce_gateway_uqpay_init() {
	load_plugin_textdomain( 'uqpay-payment-gateway', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_uqpay_missing_wc_notice' );

		return;
	}

	if ( ! class_exists( 'WC_UQPAY' ) ):
		/**
		 * Required minimums and constants
		 */
		define( 'WC_UQPAY_VERSION', '1.2.1' );
		define( 'WC_UQPAY_MIN_PHP_VER', '5.6.0' );
		define( 'WC_UQPAY_MIN_WC_VER', '3.0.0' );
		define( 'WC_UQPAY_MAIN_FILE', __FILE__ );
		define( 'WC_UQPAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_UQPAY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

		class WC_UQPAY {
			private static $instance;

			public static function get_instance() {
				if ( null == self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			private function __clone() {
				// TODO: Implement __clone() method.
			}

			private function __wakeup() {
				// TODO: Implement __wakeup() method.
			}

			private function __construct() {
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();

			}

			/**
			 * Init the plugin
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function init() {
				require_once __DIR__ . '/vendor/autoload.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-constants.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-api.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-helper.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-logger.php';
				require_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-uqpay-payment-gateway.php';
				require_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-gateway-uqpay-online-qr.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-order-handler.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-uqpay-webhook-handler.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-uqpay.php';
				require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-uqpay-union-online.php';
				require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-uqpay-union-qr.php';
				require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-uqpay-alipay-qr.php';
				require_once dirname( __FILE__ ) . '/includes/payment-methods/class-wc-gateway-uqpay-wechat-qr.php';


				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
					$this,
					'plugin_action_links'
				) );
				if ( version_compare( WC_VERSION, '3.4', '<' ) ) {
					add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
				}
			}

			public function update_plugin_version() {
				delete_option( 'wc_uqpay_version' );
				update_option( 'wc_uqpay_version', WC_UQPAY_VERSION );
			}

			/**
			 * Handles upgrade routines
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}
				if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_UQPAY_VERSION !== get_option( 'wc_uqpay_version' ) ) ) {
					do_action( 'woocommerce_uqpay_updated' );

					if ( ! defined( 'WC_UQPAY_INSTALLING' ) ) {
						define( 'WC_UQPAY_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}

			/**
			 * Add the gateways to TooCommerce.
			 * Group of UQPAY supported payment method
			 *
			 * @param $methods
			 *
			 * @return array
			 * @version 1.0.0
			 * @since 1.0.0
			 */
			public function add_gateways( $methods ) {
				$methods[] = 'WC_Gateway_UQPAY';
				$methods[] = 'WC_Gateway_UQPAY_UNION_Online';
				$methods[] = 'WC_Gateway_UQPAY_UNION_QR';
				$methods[] = 'WC_Gateway_UQPAY_ALIPAY_QR';
				$methods[] = 'WC_Gateway_UQPAY_WECHAT_QR';

				return $methods;
			}

			/**
			 * Modifies the order of the gateways displayed in admin.
			 *
			 * @param $sections
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function filter_gateway_order_admin( $sections ) {
				unset( $sections['uqpay'] );
				unset( $sections['uqpay_union_online'] );
				unset( $sections['uqpay_union_online_qr'] );
				unset( $sections['uqpay_alipay_online_qr'] );
				unset( $sections['uqpay_wechat_online_qr'] );

				$sections['uqpay'] = 'UQPAY';
				$sections['uqpay_union_online'] = __( 'UnionPay Online (UQPAY)', 'uqpay-payment-gateway' );
				$sections['uqpay_union_online_qr'] = __( 'UnionPay Online QR (UQPAY)', 'uqpay-payment-gateway' );
				$sections['uqpay_alipay_online_qr'] = __( 'Alipay Online QR (UQPAY)', 'uqpay-payment-gateway' );
				$sections['uqpay_wechat_online_qr'] = __( 'Wechat Online QR (UQPAY)', 'uqpay-payment-gateway' );
			}

			/**
			 * Add the plugin action links
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="admin.php?page=wc-settings&tab=checkout&section=uqpay">' . esc_html__( 'Settings', 'uqpay-payment-gateway' ) . '</a>',
					'<a href="https://developer.uqpay.com/api/#/">' . esc_html__( 'Docs', 'uqpay-payment-gateway' ) . '</a>',
				);

				return array_merge( $plugin_links, $links );
			}

		}

		WC_UQPAY::get_instance();
	endif;
}