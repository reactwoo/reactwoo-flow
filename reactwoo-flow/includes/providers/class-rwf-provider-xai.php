<?php
/**
 * xAI (Grok) provider adapter.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes agents via the xAI chat completions API (OpenAI-compatible).
 *
 * xAI returns the exact charge for each request as usage.cost_in_usd_ticks
 * (1 USD = 10,000,000,000 ticks). Flow stores that cost with the agent run so
 * AI time can be billed against the customer's support minutes.
 */
class RWF_Provider_XAI implements RWF_Provider_Interface {
	const ENDPOINT      = 'https://api.x.ai/v1/chat/completions';
	const DEFAULT_MODEL = 'grok-4.3';

	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'xai';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_executable() {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $agent ) {
		$api_key = RWF_Settings::get( 'rwf_xai_api_key' );
		if ( '' === $api_key ) {
			$agent['status'] = RWF_Agent::STATUS_FAILED;
			$agent['error']  = __( 'Add an xAI API key before running this agent.', 'reactwoo-flow' );

			return new WP_Error( 'rwf_missing_provider_api_key', $agent['error'], $agent );
		}

		$agent['started_at'] = current_time( 'mysql' );
		$payload             = self::build_payload( $agent );

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => $agent['timeout'],
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$agent['status'] = RWF_Agent::STATUS_FAILED;
			$agent['error']  = $response->get_error_message();

			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $agent );
		}

		return self::apply_response(
			$agent,
			(int) wp_remote_retrieve_response_code( $response ),
			(string) wp_remote_retrieve_body( $response )
		);
	}

	/**
	 * Build the request body.
	 *
	 * @param array $agent Prepared agent record.
	 * @return array
	 */
	public static function build_payload( array $agent ) {
		$model = '' !== (string) $agent['model'] ? (string) $agent['model'] : self::DEFAULT_MODEL;

		$payload = array(
			'model'       => $model,
			'temperature' => $agent['temperature'],
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $agent['prompt'],
				),
				array(
					'role'    => 'user',
					'content' => wp_json_encode( $agent['input_context'], JSON_PRETTY_PRINT ),
				),
			),
		);

		if ( ! empty( $agent['response_format'] ) ) {
			$payload['response_format'] = $agent['response_format'];
		}

		return $payload;
	}

	/**
	 * Apply an HTTP response to the agent record. Separated for testing.
	 *
	 * @param array  $agent       Agent record.
	 * @param int    $status_code HTTP status.
	 * @param string $body        Response body.
	 * @return array|WP_Error
	 */
	public static function apply_response( array $agent, $status_code, $body ) {
		$decoded = json_decode( $body, true );

		// A failed request can still be charged, so keep usage when present.
		if ( is_array( $decoded ) ) {
			$agent['usage'] = RWF_Agent::usage_from_response( $decoded );
		}

		if ( $status_code < 200 || $status_code >= 300 ) {
			$agent['status']       = RWF_Agent::STATUS_FAILED;
			$agent['error']        = sprintf(
				/* translators: %d: HTTP status code. */
				__( 'xAI request failed with status %d.', 'reactwoo-flow' ),
				$status_code
			);
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_error', $agent['error'], $agent );
		}

		if ( ! is_array( $decoded ) || empty( $decoded['choices'][0]['message']['content'] ) ) {
			$agent['status']       = RWF_Agent::STATUS_FAILED;
			$agent['error']        = __( 'xAI returned an unexpected response.', 'reactwoo-flow' );
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_invalid_response', $agent['error'], $agent );
		}

		$agent['status']       = RWF_Agent::STATUS_SUCCEEDED;
		$agent['output']       = (string) $decoded['choices'][0]['message']['content'];
		$agent['raw_response'] = $body;
		$agent['completed_at'] = current_time( 'mysql' );

		return $agent;
	}
}
