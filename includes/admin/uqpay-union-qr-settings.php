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
		'label'       => __( 'Enable UnionPay Online QR By UQPAY', 'uqpay-payment-gateway' ),
		'type'        => 'checkbox',
		'description' => '',
		'default'     => 'no',
	),
	'title'                         => array(
		'title'       => __( 'Title', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'uqpay-payment-gateway' ),
		'default'     => __( 'UnionPay Online QR (UQPAY)', 'uqpay-payment-gateway' ),
		'desc_tip'    => true,
	),
	'description'                   => array(
		'title'       => __( 'Description', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout.', 'uqpay-payment-gateway' ),
		'default'     => __( 'You can pay by scan the UnionPay QR through the UnionPay APP', 'uqpay-payment-gateway' ),
		'desc_tip'    => true,
	),
	'checkout_message'                   => array(
		'title'       => __( 'Order Pay message', 'uqpay-payment-gateway' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout on order pay page.', 'uqpay-payment-gateway' ),
		'default'     => __( 'Please scan the QR code below to complete the payment', 'uqpay-payment-gateway' ),
		'desc_tip'    => true,
	),
);