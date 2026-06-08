<?php
/**
 * Shared HTTP helpers for external integrations.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base HTTP utilities for integration clients.
 */
class RWF_Integration_Http {
	/**
	 * Perform an HTTP request and decode JSON responses.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $url    Request URL.
	 * @param array<string, mixed> $args   wp_remote_request args.
	 * @return array{code: int, data: mixed, body: string}|WP_Error
	 */
	public static function request_json( $method, $url, $args = array() ) {
		$args = array_merge(
			array(
				'method'  => strtoupper( $method ),
				'timeout' => 30,
				'headers' => array(
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json',
				),
			),
			$args
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = __( 'Integration request failed.', 'reactwoo-flow' );
			if ( is_array( $data ) && ! empty( $data['message'] ) ) {
				$message = (string) $data['message'];
			} elseif ( is_array( $data ) && ! empty( $data['errorMessages'][0] ) ) {
				$message = (string) $data['errorMessages'][0];
			}

			return new WP_Error(
				'rwf_integration_http_error',
				$message,
				array(
					'http_code' => $code,
					'body'      => $data,
				)
			);
		}

		return array(
			'code' => $code,
			'data' => $data,
			'body' => $body,
		);
	}
}
