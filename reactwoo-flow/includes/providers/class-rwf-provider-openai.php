<?php
/**
 * OpenAI / GPT-compatible provider adapter.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes planning agents via OpenAI chat completions.
 */
class RWF_Provider_OpenAI implements RWF_Provider_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'openai';
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
		$api_key = RWF_Settings::get( 'rwf_openai_api_key' );
		if ( '' === $api_key ) {
			$agent['status'] = RWF_Agent::STATUS_FAILED;
			$agent['error']  = __( 'Add an API key for the OpenAI / GPT-compatible provider before running this agent.', 'reactwoo-flow' );

			return new WP_Error( 'rwf_missing_provider_api_key', $agent['error'], $agent );
		}

		$agent['started_at'] = current_time( 'mysql' );
		$payload             = array(
			'model'       => $agent['model'],
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

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
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

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$agent['status']       = RWF_Agent::STATUS_FAILED;
			$agent['error']        = sprintf(
				/* translators: %d: HTTP status code. */
				__( 'Provider request failed with status %d.', 'reactwoo-flow' ),
				$status_code
			);
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_error', $agent['error'], $agent );
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || empty( $decoded['choices'][0]['message']['content'] ) ) {
			$agent['status']       = RWF_Agent::STATUS_FAILED;
			$agent['error']        = __( 'Provider returned an unexpected response.', 'reactwoo-flow' );
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
