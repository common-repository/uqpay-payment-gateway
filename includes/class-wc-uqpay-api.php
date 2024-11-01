<?php


use uqpay\payment\model\HttpClientInterface;
use uqpay\payment\UqpayException;

class WC_UQPAY_API implements HttpClientInterface {

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array $headers
	 * @param string $body
	 * @param string $url
	 *
	 * @return string
	 * @throws UqpayException
	 */
	public function post( array $headers, $body, $url ) {
		$response = wp_remote_post( $url, array(
			'headers' => $headers,
			'body'    => $body,
			'timeout' => 70,
		) );
		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			WC_UQPAY_Logger::log(
				'Error Response: ' . print_r( $response, true ) . PHP_EOL . PHP_EOL . 'Failed request: ' . print_r(
					array(
						'api'     => $url,
						'request' => $body,
					),
					true
				)
			);

			throw new UqpayException( print_r( $response, true ), __( 'There was a problem connecting to the UQPAY API endpoint.', 'uqpay-payment-gateway' ) );
		}

		return $response['body'];
	}
}