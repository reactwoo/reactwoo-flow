<?php
/**
 * Plugin settings.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers ReactWoo Flow settings.
 */
class RWF_Settings {
	const OPTION_GROUP = 'rwf_settings';

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		foreach ( self::get_settings_schema() as $option_key => $definition ) {
			register_setting(
				self::OPTION_GROUP,
				$option_key,
				array(
					'type'              => 'string',
					'sanitize_callback' => isset( $definition['sanitize_callback'] ) ? $definition['sanitize_callback'] : 'sanitize_text_field',
					'default'           => isset( $definition['default'] ) ? $definition['default'] : '',
				)
			);
		}
	}

	/**
	 * Get settings schema.
	 *
	 * @return array
	 */
	public static function get_settings_schema() {
		return array(
			'rwf_planning_agent_provider' => array(
				'label'   => __( 'Planning Agent Provider', 'reactwoo-flow' ),
				'type'    => 'select',
				'section' => 'agents',
				'options' => RWF_Agent::get_providers(),
				'default' => 'openai',
			),
			'rwf_planning_agent_model'    => array(
				'label'   => __( 'Planning Agent Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'agents',
				'default' => 'gpt-4o-mini',
			),
			'rwf_development_agent_provider' => array(
				'label'   => __( 'Development Agent Provider', 'reactwoo-flow' ),
				'type'    => 'select',
				'section' => 'agents',
				'options' => RWF_Agent::get_providers(),
				'default' => 'cursor_mcp',
			),
			'rwf_development_agent_model' => array(
				'label'   => __( 'Development Agent Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'agents',
				'default' => 'cursor-default',
			),
			'rwf_qa_agent_provider'       => array(
				'label'   => __( 'QA Agent Provider', 'reactwoo-flow' ),
				'type'    => 'select',
				'section' => 'agents',
				'options' => RWF_Agent::get_providers(),
				'default' => 'manual',
			),
			'rwf_qa_agent_model'          => array(
				'label'   => __( 'QA Agent Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'agents',
				'default' => 'vision-capable',
			),
			'rwf_ux_agent_provider'       => array(
				'label'   => __( 'UX Agent Provider', 'reactwoo-flow' ),
				'type'    => 'select',
				'section' => 'agents',
				'options' => RWF_Agent::get_providers(),
				'default' => 'cursor_mcp',
			),
			'rwf_ux_agent_model'          => array(
				'label'   => __( 'UX Agent Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'agents',
				'default' => 'claude',
			),
			'rwf_release_agent_provider'  => array(
				'label'   => __( 'Release Agent Provider', 'reactwoo-flow' ),
				'type'    => 'select',
				'section' => 'agents',
				'options' => RWF_Agent::get_providers(),
				'default' => 'openai',
			),
			'rwf_release_agent_model'     => array(
				'label'   => __( 'Release Agent Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'agents',
				'default' => 'gpt-4o-mini',
			),
			'rwf_openai_api_key'       => array(
				'label'             => __( 'OpenAI / GPT-compatible API Key', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'providers',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
			),
			'rwf_anthropic_api_key'    => array(
				'label'             => __( 'Anthropic API Key', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'providers',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
			),
			'rwf_cursor_mcp_endpoint'  => array(
				'label'       => __( 'Cursor MCP Bridge Endpoint', 'reactwoo-flow' ),
				'type'        => 'url',
				'section'     => 'providers',
				'description' => __( 'HTTP endpoint that accepts development handoff JSON from ReactWoo Flow.', 'reactwoo-flow' ),
			),
			'rwf_intake_notification_email' => array(
				'label'       => __( 'Intake Notification Email', 'reactwoo-flow' ),
				'type'        => 'email',
				'section'     => 'intake',
				'description' => __( 'Optional. New website intake submissions will send a notification to this address.', 'reactwoo-flow' ),
			),
			'rwf_jira_url'             => array(
				'label'   => __( 'Jira URL', 'reactwoo-flow' ),
				'type'    => 'url',
				'section' => 'jira',
			),
			'rwf_jira_email'           => array(
				'label'   => __( 'Jira Email', 'reactwoo-flow' ),
				'type'    => 'email',
				'section' => 'jira',
			),
			'rwf_jira_api_token'       => array(
				'label'             => __( 'Jira API Token', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'jira',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
			),
			'rwf_jira_project_key'     => array(
				'label'   => __( 'Jira Project Key', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'jira',
			),
			'rwf_confluence_space_key' => array(
				'label'   => __( 'Confluence Space Key', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'confluence',
			),
			'rwf_confluence_parent_page_id' => array(
				'label'       => __( 'Confluence Parent Page ID', 'reactwoo-flow' ),
				'type'        => 'text',
				'section'     => 'confluence',
				'description' => __( 'Optional. New specification pages are created under this parent page.', 'reactwoo-flow' ),
			),
			'rwf_github_repository'    => array(
				'label'       => __( 'GitHub Repository', 'reactwoo-flow' ),
				'type'        => 'text',
				'section'     => 'github',
				'description' => __( 'Format: owner/repo', 'reactwoo-flow' ),
			),
			'rwf_github_token'         => array(
				'label'             => __( 'GitHub Personal Access Token', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'github',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
			),
		);
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $option_key Option key.
	 * @return string
	 */
	public static function get( $option_key ) {
		$schema  = self::get_settings_schema();
		$default = isset( $schema[ $option_key ]['default'] ) ? $schema[ $option_key ]['default'] : '';

		return (string) get_option( $option_key, $default );
	}

	/**
	 * Get the configured provider for an agent type.
	 *
	 * @param string $agent_type Agent type key.
	 * @return string
	 */
	public static function get_agent_provider( $agent_type ) {
		$option_key = 'rwf_' . sanitize_key( $agent_type ) . '_agent_provider';

		return self::get( $option_key );
	}

	/**
	 * Get the configured model for an agent type.
	 *
	 * @param string $agent_type Agent type key.
	 * @return string
	 */
	public static function get_agent_model( $agent_type ) {
		$option_key = 'rwf_' . sanitize_key( $agent_type ) . '_agent_model';
		$model      = self::get( $option_key );

		if ( 'planning' === $agent_type && '' === get_option( $option_key, '' ) ) {
			$legacy_model = get_option( 'rwf_openai_model', '' );
			if ( '' !== $legacy_model ) {
				return (string) $legacy_model;
			}
		}

		return $model;
	}

	/**
	 * Sanitize secret settings without accidentally erasing saved values.
	 *
	 * @param string $value Raw setting value.
	 * @return string
	 */
	public static function sanitize_secret( $value ) {
		return sanitize_text_field( (string) $value );
	}
}
