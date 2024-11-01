<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use uqpay\payment\Constants;

return array(
	'activation'  => array(
		'description' => __( 'Here is the general configuration of UQPAY service. Before enabling the payment methods provided by UQPAY, please be sure to complete the configuration', 'uqpay-payment-gateway' ),
		'type'        => 'title',
	),
	'enabled'                       => array(
		'title'       => __( 'Enable/Disable', 'uqpay-payment-gateway' ),
		'label'       => __( 'Enable UQPAY Service', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'merchant_id'                   => array(
		'title'       => __( 'Merchant ID', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'Set your merchant ID.', 'uqpay-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'test_mode'                      => array(
		'title'       => __( 'Test mode', 'uqpay-payment-gateway' ),
		'label'       => __( 'Enable Test Mode', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => __( 'Place the payment gateway in test mode using test API credentials.', 'uqpay-payment-gateway' ),
		'default'     => 'yes',
		'desc_tip'    => true,
	),
	'test_publishable_key'          => array(
		'title'       => __( 'Test Publishable Key', 'uqpay-payment-gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Download API credentials from UQPAY dashboard, copy the content of UQPAY_pub.pem here.', 'uqpay-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'test_private_key_type'   => array(
		'title'       => __( 'Test Private Key Type', 'uqpay-payment-gateway' ),
		'label'       => __( 'Key Type', 'uqpay-payment-gateway' ),
		'type'        => 'select',
		'description' => __( 'Select the private key type you would like to use.', 'uqpay-payment-gateway' ),
		'default'     => Constants::SIGN_TYPE_RSA,
		'desc_tip'    => true,
		'options'     => array(
			Constants::SIGN_TYPE_RSA => __( 'SIGN_TYPE_RSA', 'uqpay-payment-gateway' ),
			Constants::SIGN_TYPE_MD5     => __( 'SIGN_TYPE_MD5', 'uqpay-payment-gateway' ),
		),
	),
	'test_private_key'               => array(
		'title'       => __( 'Test Private Key', 'uqpay-payment-gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Download API credentials from UQPAY dashboard, copy the content of id_prv.pem (for rsa) or  md5Key.txt (for md5).', 'uqpay-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'publishable_key'          => array(
		'title'       => __( 'Publishable Key', 'uqpay-payment-gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Download API credentials from UQPAY dashboard, copy the content of UQPAY_pub.pem here.', 'uqpay-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'private_key_type'   => array(
		'title'       => __( 'Private Key Type', 'uqpay-payment-gateway' ),
		'label'       => __( 'Key Type', 'uqpay-payment-gateway' ),
		'type'        => 'select',
		'description' => __( 'Select the private key type you would like to use.', 'uqpay-payment-gateway' ),
		'default'     => Constants::SIGN_TYPE_RSA,
		'desc_tip'    => true,
		'options'     => array(
			Constants::SIGN_TYPE_RSA => __( 'SIGN_TYPE_RSA', 'uqpay-payment-gateway' ),
			Constants::SIGN_TYPE_MD5     => __( 'SIGN_TYPE_MD5', 'uqpay-payment-gateway' ),
		),
	),
	'private_key'               => array(
		'title'       => __( 'Private Key', 'uqpay-payment-gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Download API credentials from UQPAY dashboard, copy the content of id_prv.pem (for rsa) or  md5Key.txt (for md5).', 'uqpay-payment-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'logging'                       => array(
		'title'       => __( 'Logging', 'uqpay-payment-gateway' ),
		'label'       => __( 'Log debug messages', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'uqpay-payment-gateway' ),
		'default'     => 'no',
		'desc_tip'    => true,
	),
	'check_login'                       => array(
		'title'       => __( 'Check login', 'uqpay-payment-gateway' ),
		'label'       => __( 'Check login when request plugin api', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => __( 'Check login when request plugin api.', 'uqpay-payment-gateway' ),
		'default'     => 'no',
		'desc_tip'    => true,
	),
);