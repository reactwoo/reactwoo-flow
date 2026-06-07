<?php
/**
 * Agent orchestration layer.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes model-agnostic agent tasks.
 */
class RWF_Agent {
	const STATUS_PENDING = 'pending';
	const STATUS_RUNNING = 'running';
	const STATUS_SUCCEEDED = 'succeeded';
	const STATUS_FAILED = 'failed';

	/**
	 * Get supported provider options.
	 *
	 * @return array
	 */
	public static function get_providers() {
		return array(
			'openai'     => __( 'OpenAI / GPT-compatible', 'reactwoo-flow' ),
			'cursor_mcp' => __( 'Cursor MCP Bridge (future)', 'reactwoo-flow' ),
			'anthropic'  => __( 'Anthropic (future)', 'reactwoo-flow' ),
			'manual'     => __( 'Manual / External Agent', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get known agent types and their intended responsibilities.
	 *
	 * @return array
	 */
	public static function get_agent_types() {
		return array(
			'planning'    => array(
				'label'             => __( 'Planning Agent', 'reactwoo-flow' ),
				'preferred'         => __( 'GPT models', 'reactwoo-flow' ),
				'default_provider'  => 'openai',
				'default_model'     => 'gpt-4o-mini',
				'purpose'           => __( 'Summaries, PRDs, specifications, acceptance criteria, and story generation.', 'reactwoo-flow' ),
			),
			'development' => array(
				'label'             => __( 'Development Agent', 'reactwoo-flow' ),
				'preferred'         => __( 'Cursor with Claude, Composer, or future Cursor models', 'reactwoo-flow' ),
				'default_provider'  => 'cursor_mcp',
				'default_model'     => 'cursor-default',
				'purpose'           => __( 'Code implementation, bug fixes, refactoring, test creation, and implementation assistance.', 'reactwoo-flow' ),
			),
			'qa'          => array(
				'label'             => __( 'QA Agent', 'reactwoo-flow' ),
				'preferred'         => __( 'Vision-capable model', 'reactwoo-flow' ),
				'default_provider'  => 'manual',
				'default_model'     => 'vision-capable',
				'purpose'           => __( 'Visual regression, accessibility checks, screenshot review, and responsive validation.', 'reactwoo-flow' ),
			),
			'ux'          => array(
				'label'             => __( 'UX Agent', 'reactwoo-flow' ),
				'preferred'         => __( 'Cursor with Claude', 'reactwoo-flow' ),
				'default_provider'  => 'cursor_mcp',
				'default_model'     => 'claude',
				'purpose'           => __( 'Journey validation, information hierarchy, CTA evaluation, and empty state analysis.', 'reactwoo-flow' ),
			),
			'release'     => array(
				'label'             => __( 'Release Agent', 'reactwoo-flow' ),
				'preferred'         => __( 'GPT models', 'reactwoo-flow' ),
				'default_provider'  => 'openai',
				'default_model'     => 'gpt-4o-mini',
				'purpose'           => __( 'Changelog generation, release notes, and customer communications.', 'reactwoo-flow' ),
			),
		);
	}

	/**
	 * Execute an agent task.
	 *
	 * @param array $args Agent arguments.
	 * @return array|WP_Error
	 */
	public static function execute( $args ) {
		$agent = self::prepare_agent( $args );

		if ( is_wp_error( $agent ) ) {
			return $agent;
		}

		$agent['status'] = self::STATUS_RUNNING;

		if ( 'openai' === $agent['provider'] ) {
			return self::execute_openai( $agent );
		}

		if ( 'cursor_mcp' === $agent['provider'] ) {
			$agent['status'] = self::STATUS_FAILED;
			$agent['error']  = __( 'Cursor MCP execution is planned for a future bridge. ReactWoo Flow can prepare the context payload now, but cannot send it to Cursor yet.', 'reactwoo-flow' );

			return new WP_Error( 'rwf_cursor_mcp_not_connected', $agent['error'], $agent );
		}

		$agent['status'] = self::STATUS_FAILED;
		$agent['error']  = __( 'The selected provider is not executable in this MVP.', 'reactwoo-flow' );

		return new WP_Error( 'rwf_provider_not_executable', $agent['error'], $agent );
	}

	/**
	 * Build an agent execution record without running it.
	 *
	 * @param array $args Agent arguments.
	 * @return array|WP_Error
	 */
	public static function prepare_agent( $args ) {
		$agent_type      = isset( $args['agent_type'] ) ? sanitize_key( $args['agent_type'] ) : 'planning';
		$agent_types     = self::get_agent_types();
		$type_definition = isset( $agent_types[ $agent_type ] ) ? $agent_types[ $agent_type ] : $agent_types['planning'];
		$provider        = isset( $args['provider'] ) && '' !== $args['provider'] ? sanitize_key( $args['provider'] ) : RWF_Settings::get_agent_provider( $agent_type );
		$model           = isset( $args['model'] ) && '' !== $args['model'] ? sanitize_text_field( $args['model'] ) : RWF_Settings::get_agent_model( $agent_type );
		$prompt_template = isset( $args['prompt_template'] ) ? sanitize_file_name( $args['prompt_template'] ) : '';

		if ( '' === $provider ) {
			$provider = $type_definition['default_provider'];
		}

		if ( '' === $model ) {
			$model = $type_definition['default_model'];
		}

		return array(
			'name'            => isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : $type_definition['label'],
			'agent_type'      => $agent_type,
			'provider'        => $provider,
			'model'           => $model,
			'prompt_template' => $prompt_template,
			'prompt'          => self::get_prompt( $prompt_template ),
			'input_context'   => isset( $args['input_context'] ) && is_array( $args['input_context'] ) ? $args['input_context'] : array(),
			'response_format' => isset( $args['response_format'] ) && is_array( $args['response_format'] ) ? $args['response_format'] : array(),
			'temperature'     => isset( $args['temperature'] ) ? (float) $args['temperature'] : 0.2,
			'timeout'         => isset( $args['timeout'] ) ? absint( $args['timeout'] ) : 60,
			'output'          => '',
			'status'          => self::STATUS_PENDING,
			'error'           => '',
			'started_at'      => '',
			'completed_at'    => '',
		);
	}

	/**
	 * Execute an agent via OpenAI chat completions.
	 *
	 * @param array $agent Prepared agent.
	 * @return array|WP_Error
	 */
	private static function execute_openai( $agent ) {
		$api_key = RWF_Settings::get( 'rwf_openai_api_key' );
		if ( '' === $api_key ) {
			$agent['status'] = self::STATUS_FAILED;
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
			$agent['status'] = self::STATUS_FAILED;
			$agent['error']  = $response->get_error_message();

			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $agent );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			$agent['status'] = self::STATUS_FAILED;
			$agent['error']  = sprintf(
				/* translators: %d: HTTP status code. */
				__( 'Provider request failed with status %d.', 'reactwoo-flow' ),
				$status_code
			);
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_error', $agent['error'], $agent );
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || empty( $decoded['choices'][0]['message']['content'] ) ) {
			$agent['status']       = self::STATUS_FAILED;
			$agent['error']        = __( 'Provider returned an unexpected response.', 'reactwoo-flow' );
			$agent['raw_response'] = $body;

			return new WP_Error( 'rwf_provider_invalid_response', $agent['error'], $agent );
		}

		$agent['status']       = self::STATUS_SUCCEEDED;
		$agent['output']       = (string) $decoded['choices'][0]['message']['content'];
		$agent['raw_response'] = $body;
		$agent['completed_at'] = current_time( 'mysql' );

		return $agent;
	}

	/**
	 * Read a prompt template.
	 *
	 * @param string $file_name Prompt file name.
	 * @return string
	 */
	private static function get_prompt( $file_name ) {
		if ( '' !== $file_name ) {
			$prompt_file = RWF_PLUGIN_DIR . 'prompts/' . sanitize_file_name( $file_name );

			if ( file_exists( $prompt_file ) ) {
				$prompt = file_get_contents( $prompt_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				if ( false !== $prompt ) {
					return $prompt;
				}
			}
		}

		return __( 'You are a ReactWoo Flow agent. Use the supplied context and return the requested output.', 'reactwoo-flow' );
	}
}
