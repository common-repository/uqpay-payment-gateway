<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use uqpay\payment\Constants;

return array(
	'activation'  => array(
		'description' => __( 'Be sure this payment method has be activated, check from your UQPAY Dashboard <a href="https://merchant.uqpay.com" target="_blank">here</a>', 'uqpay-payment-gateway' ),
		'type'        => 'title',
	),
	'enabled'                       => array(
		'title'       => __( 'Enable/Disable', 'uqpay-payment-gateway' ),
		'label'       => __( 'Enable UnionPay Online By UQPAY', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'title'                         => array(
		'title'       => __( 'Title', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'uqpay-payment-gateway' ),
		'default'     => __( 'UnionPay Online (UQPAY)', 'uqpay-payment-gateway' ),
		'desc_tip'    => true,
	),
	'description'                   => array(
		'title'       => __( 'Description', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout.', 'uqpay-payment-gateway' ),
		'default'     => __( 'You will be redirected to UnionPay Online.', 'uqpay-payment-gateway' ),
		'desc_tip'    => true,
	)
);