<?php
/**
 * Anthropic Claude provider adapter.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes agents via the Anthropic Messages API.
 */
class RWF_Provider_Anthropic implements RWF_Provider_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'anthropic';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_executable() {
		return '' !== RWF_Settings::get( 'rwf_anthropic_api_key' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $agent ) {
		$api_key = RWF_Settings::get( 'rwf_anthropic_api_key' );
		if ( '' === $api_key ) {
			$agent['status'] = RWF_Agent::STATUS_FAILED;
			$agent['error']  = __( 'Add an Anthropic API key before running this agent.', 'reactwoo-flow' );

			return new WP_Error( 'rwf_missing_provider_api_key', $agent['error'], $agent );
		}

		$agent['started_at'] = current_time( 'mysql' );
		$payload             = array(
			'model'      => $agent['model'],
			'max_tokens' => 4096,
			'system'     => $agent['prompt'],
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => wp_json_encode( $agent['input_context'], JSON_PRETTY_PRINT ),
				),
			),
		);

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => $agent['timeout'],
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
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
				__( 'Anthropic request failed with status %d.', 'reactwoo-flow' ),
				$status_code
			);
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_error', $agent['error'], $agent );
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || empty( $decoded['content'][0]['text'] ) ) {
			$agent['status']       = RWF_Agent::STATUS_FAILED;
			$agent['error']        = __( 'Anthropic returned an unexpected response.', 'reactwoo-flow' );
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_invalid_response', $agent['error'], $agent );
		}

		$agent['status']       = RWF_Agent::STATUS_SUCCEEDED;
		$agent['output']       = (string) $decoded['content'][0]['text'];
		$agent['raw_response'] = $body;
		$agent['completed_at'] = current_time( 'mysql' );

		return $agent;
	}
}
