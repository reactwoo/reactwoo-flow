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

		register_setting(
			self::OPTION_GROUP,
			'rwf_github_product_map',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_github_product_map' ),
				'default'           => array(),
			)
		);
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
				'default' => 'openai',
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
				'default' => 'openai',
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
			'rwf_auto_create_jira_on_triage' => array(
				'label'       => __( 'Auto-create Jira Issue After Triage', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Creates a Jira issue when triage succeeds and no issue is linked yet.', 'reactwoo-flow' ),
			),
			'rwf_auto_advance_ready_for_development' => array(
				'label'       => __( 'Auto-advance to Ready for Development', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Moves items from Ready for Specification to Ready for Development after a spec is generated.', 'reactwoo-flow' ),
			),
			'rwf_auto_publish_confluence_on_spec' => array(
				'label'       => __( 'Auto-publish Confluence After Specification', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Publishes the specification to Confluence when generation succeeds and no page is linked yet.', 'reactwoo-flow' ),
			),
			'rwf_auto_send_cursor_on_handoff' => array(
				'label'       => __( 'Auto-send Cursor MCP After Handoff', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'POSTs the development handoff to the configured Cursor MCP endpoint after handoff preparation.', 'reactwoo-flow' ),
			),
			'rwf_auto_sync_github_on_handoff' => array(
				'label'       => __( 'Auto-sync GitHub PR After Handoff', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Refreshes GitHub pull request metadata when handoff is prepared and a PR URL or branch is set.', 'reactwoo-flow' ),
			),
			'rwf_auto_apply_suggested_branch' => array(
				'label'       => __( 'Auto-apply Suggested GitHub Branch', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Copies the triage suggested branch to github_branch when empty.', 'reactwoo-flow' ),
			),
			'rwf_auto_apply_default_epic' => array(
				'label'       => __( 'Auto-apply Default Epic Key', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'automation',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'Copies the Jira default epic key to jira_epic_key when empty after triage.', 'reactwoo-flow' ),
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
			'rwf_jira_default_epic_key' => array(
				'label'       => __( 'Default Epic Issue Key', 'reactwoo-flow' ),
				'type'        => 'text',
				'section'     => 'jira',
				'description' => __( 'Optional. Used when creating Jira issues if the item has no epic key set.', 'reactwoo-flow' ),
			),
			'rwf_jira_epic_link_field' => array(
				'label'       => __( 'Epic Link Custom Field', 'reactwoo-flow' ),
				'type'        => 'text',
				'section'     => 'jira',
				'description' => __( 'Optional. Jira custom field id for epic links (e.g. customfield_10014). Leave empty to use parent linking.', 'reactwoo-flow' ),
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
			'rwf_github_token'         => array(
				'label'             => __( 'GitHub Personal Access Token', 'reactwoo-flow' ),
				'type'              => 'password',
				'section'           => 'github',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
				'description'       => __( 'Token with repo read access. Saves your product repository list below.', 'reactwoo-flow' ),
			),
			'rwf_github_webhook_enabled' => array(
				'label'       => __( 'Enable GitHub Webhook', 'reactwoo-flow' ),
				'type'        => 'select',
				'section'     => 'github',
				'options'     => array(
					''    => __( 'No', 'reactwoo-flow' ),
					'yes' => __( 'Yes', 'reactwoo-flow' ),
				),
				'default'     => '',
				'description' => __( 'When enabled, GitHub can POST pull_request and status events to the callback URL. Each mapped repository uses its own webhook secret.', 'reactwoo-flow' ),
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

	/**
	 * Whether a yes/no setting is enabled.
	 *
	 * @param string $option_key Option key.
	 * @return bool
	 */
	public static function is_yes( $option_key ) {
		return 'yes' === self::get( $option_key );
	}

	/**
	 * Product slug to GitHub repository and webhook secret.
	 *
	 * @return array<string, array{repository: string, webhook_secret: string}>
	 */
	public static function get_github_product_map() {
		self::maybe_migrate_github_product_map();

		$map = get_option( 'rwf_github_product_map', array() );

		return is_array( $map ) ? $map : array();
	}

	/**
	 * Parse product slug to owner/repo mappings from settings.
	 *
	 * @return array<string, string>
	 */
	public static function get_github_product_repositories() {
		$repos = array();

		foreach ( self::get_github_product_map() as $slug => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$repository = isset( $row['repository'] ) ? trim( (string) $row['repository'] ) : '';
			if ( '' !== $repository ) {
				$repos[ sanitize_key( (string) $slug ) ] = $repository;
			}
		}

		return $repos;
	}

	/**
	 * Resolve the GitHub repository for a product slug.
	 *
	 * @param string $product_slug Product key from the item.
	 * @return string owner/repo
	 */
	public static function get_github_repository_for_product( $product_slug ) {
		$map  = self::get_github_product_repositories();
		$slug = sanitize_key( (string) $product_slug );

		return ( '' !== $slug && isset( $map[ $slug ] ) ) ? $map[ $slug ] : '';
	}

	/**
	 * Webhook secret configured for a repository full name.
	 *
	 * @param string $repository_full_name owner/repo.
	 * @return string
	 */
	public static function get_github_webhook_secret_for_repository( $repository_full_name ) {
		$repository_full_name = trim( (string) $repository_full_name );
		if ( '' === $repository_full_name ) {
			return '';
		}

		foreach ( self::get_github_product_map() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$repository = isset( $row['repository'] ) ? trim( (string) $row['repository'] ) : '';
			$secret     = isset( $row['webhook_secret'] ) ? (string) $row['webhook_secret'] : '';
			if ( '' !== $secret && strcasecmp( $repository, $repository_full_name ) === 0 ) {
				return $secret;
			}
		}

		return '';
	}

	/**
	 * All configured GitHub repositories from the product map.
	 *
	 * @return string[]
	 */
	public static function get_all_github_repositories() {
		return array_values( array_unique( array_filter( array_values( self::get_github_product_repositories() ) ) ) );
	}

	/**
	 * Whether at least one repository is configured for GitHub sync.
	 *
	 * @return bool
	 */
	public static function has_github_repository_config() {
		return ! empty( self::get_all_github_repositories() );
	}

	/**
	 * Whether any mapped repository has a webhook secret.
	 *
	 * @return bool
	 */
	public static function has_github_webhook_secrets() {
		foreach ( self::get_github_product_map() as $row ) {
			if ( is_array( $row ) && ! empty( $row['webhook_secret'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize product map rows submitted from Settings.
	 *
	 * @param mixed $value Raw POST value.
	 * @return array<string, array{repository: string, webhook_secret: string}>
	 */
	public static function sanitize_github_product_map( $value ) {
		$existing = self::get_github_product_map();
		$clean    = array();

		if ( ! is_array( $value ) ) {
			return $clean;
		}

		foreach ( RWF_CPT::get_products() as $slug => $label ) {
			$row = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$repository = isset( $row['repository'] ) ? trim( sanitize_text_field( (string) $row['repository'] ) ) : '';
			if ( '' !== $repository && ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repository ) ) {
				$repository = '';
			}

			$secret = isset( $row['webhook_secret'] ) ? sanitize_text_field( (string) $row['webhook_secret'] ) : '';
			if ( '' === $secret && isset( $existing[ $slug ]['webhook_secret'] ) ) {
				$secret = (string) $existing[ $slug ]['webhook_secret'];
			}

			if ( '' === $repository && '' === $secret ) {
				continue;
			}

			$clean[ $slug ] = array(
				'repository'     => $repository,
				'webhook_secret' => $secret,
			);
		}

		return $clean;
	}

	/**
	 * Migrate legacy textarea map and global webhook secret.
	 *
	 * @return void
	 */
	private static function maybe_migrate_github_product_map() {
		$current = get_option( 'rwf_github_product_map', null );
		if ( is_array( $current ) && ! empty( $current ) ) {
			return;
		}

		$legacy_lines  = (string) get_option( 'rwf_github_product_repos', '' );
		$legacy_secret = (string) get_option( 'rwf_github_webhook_secret', '' );
		$legacy_repos  = array();
		$lines         = preg_split( '/\r\n|\r|\n/', $legacy_lines );

		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = trim( (string) $line );
				if ( '' === $line || '#' === $line[0] ) {
					continue;
				}
				if ( preg_match( '#^([a-z0-9_]+)\s*[:=]\s*([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+)#i', $line, $matches ) ) {
					$legacy_repos[ sanitize_key( $matches[1] ) ] = $matches[2];
				} elseif ( preg_match( '#^([a-z0-9_]+)\s+([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+)#i', $line, $matches ) ) {
					$legacy_repos[ sanitize_key( $matches[1] ) ] = $matches[2];
				}
			}
		}

		if ( empty( $legacy_repos ) ) {
			return;
		}

		$migrated = array();
		foreach ( $legacy_repos as $slug => $repository ) {
			$migrated[ $slug ] = array(
				'repository'     => $repository,
				'webhook_secret' => $legacy_secret,
			);
		}

		update_option( 'rwf_github_product_map', $migrated );
	}
}
