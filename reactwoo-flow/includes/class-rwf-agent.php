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
			'anthropic'  => __( 'Anthropic Claude', 'reactwoo-flow' ),
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

		$provider = self::get_provider( $agent['provider'] );

		if ( ! $provider ) {
			$agent['status'] = self::STATUS_FAILED;
			$agent['error']  = __( 'The selected provider is not registered in this build.', 'reactwoo-flow' );

			return new WP_Error( 'rwf_provider_not_registered', $agent['error'], $agent );
		}

		if ( ! $provider->is_executable() ) {
			return $provider->execute( $agent );
		}

		$agent['status'] = self::STATUS_RUNNING;

		return $provider->execute( $agent );
	}

	/**
	 * Resolve a provider adapter by identifier.
	 *
	 * @param string $provider_id Provider key.
	 * @return RWF_Provider_Interface|null
	 */
	public static function get_provider( $provider_id ) {
		foreach ( self::get_providers_registry() as $provider ) {
			if ( $provider->get_id() === $provider_id ) {
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Registered provider adapters.
	 *
	 * @return RWF_Provider_Interface[]
	 */
	private static function get_providers_registry() {
		static $providers = null;

		if ( null === $providers ) {
			$providers = array(
				new RWF_Provider_OpenAI(),
				new RWF_Provider_Anthropic(),
				new RWF_Provider_Cursor_MCP(),
			);
		}

		return $providers;
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
			'prompt'          => self::load_prompt_template( $prompt_template ),
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
	 * Read a prompt template.
	 *
	 * @param string $file_name Prompt file name.
	 * @return string
	 */
	public static function load_prompt_template( $file_name ) {
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
