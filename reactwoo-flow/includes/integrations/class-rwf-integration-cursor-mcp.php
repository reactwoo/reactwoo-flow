<?php
/**
 * Cursor MCP bridge integration.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends development handoff payloads to a configured Cursor MCP endpoint.
 */
class RWF_Integration_Cursor_MCP {
	/**
	 * Whether a Cursor MCP endpoint is configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::get_endpoint();
	}

	/**
	 * Verify the Cursor MCP endpoint responds.
	 *
	 * @return true|WP_Error
	 */
	public static function test_connection() {
		$endpoint = self::get_endpoint();
		if ( '' === $endpoint ) {
			return new WP_Error( 'rwf_cursor_mcp_not_configured', __( 'Cursor MCP endpoint is not configured.', 'reactwoo-flow' ) );
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'source'  => 'reactwoo-flow',
						'ping'    => true,
						'sent_at' => current_time( 'c' ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'rwf_cursor_mcp_http_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Cursor MCP bridge returned HTTP %d.', 'reactwoo-flow' ),
					$code
				)
			);
		}

		return true;
	}

	/**
	 * POST the development handoff payload to the configured bridge.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function send_handoff( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$endpoint = self::get_endpoint();
		if ( '' === $endpoint ) {
			return new WP_Error( 'rwf_cursor_mcp_not_configured', __( 'Cursor MCP endpoint is not configured in Settings.', 'reactwoo-flow' ) );
		}

		$payload = RWF_AI::build_development_handoff_context( $post_id );
		$body    = array(
			'source'    => 'reactwoo-flow',
			'item_id'   => $post_id,
			'sent_at'   => current_time( 'c' ),
			'handoff'   => $payload,
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'rwf_cursor_mcp_http_error',
				__( 'Cursor MCP bridge rejected the handoff payload.', 'reactwoo-flow' ),
				array(
					'http_code' => $code,
					'body'      => $data,
				)
			);
		}

		RWF_CPT::update_meta( $post_id, 'cursor_handoff_sent_at', current_time( 'mysql' ) );

		return array(
			'sent'      => true,
			'endpoint'  => $endpoint,
			'http_code' => $code,
			'response'  => is_array( $data ) ? $data : $raw,
		);
	}

	/**
	 * @return string
	 */
	private static function get_endpoint() {
		return esc_url_raw( RWF_Settings::get( 'rwf_cursor_mcp_endpoint' ) );
	}
}
