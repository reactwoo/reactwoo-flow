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
			'rwf_openai_api_key'       => array(
				'label'             => __( 'OpenAI API Key', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'openai',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
			),
			'rwf_openai_model'         => array(
				'label'   => __( 'OpenAI Model', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'openai',
				'default' => 'gpt-4o-mini',
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
			'rwf_github_repository'    => array(
				'label'   => __( 'GitHub Repository', 'reactwoo-flow' ),
				'type'    => 'text',
				'section' => 'github',
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
	 * Sanitize secret settings without accidentally erasing saved values.
	 *
	 * @param string $value Raw setting value.
	 * @return string
	 */
	public static function sanitize_secret( $value ) {
		return sanitize_text_field( (string) $value );
	}
}
