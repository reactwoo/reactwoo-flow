<?php
/**
 * Custom post type and item metadata.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the ReactWoo Flow item post type and field schema.
 */
class RWF_CPT {
	const POST_TYPE = 'rwf_item';
	const META_PREFIX = '_rwf_';

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register CPT and meta fields.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'ReactWoo Flow Items', 'reactwoo-flow' ),
					'singular_name'      => __( 'ReactWoo Flow Item', 'reactwoo-flow' ),
					'add_new_item'       => __( 'Add New Flow Item', 'reactwoo-flow' ),
					'edit_item'          => __( 'Edit Flow Item', 'reactwoo-flow' ),
					'new_item'           => __( 'New Flow Item', 'reactwoo-flow' ),
					'view_item'          => __( 'View Flow Item', 'reactwoo-flow' ),
					'search_items'       => __( 'Search Flow Items', 'reactwoo-flow' ),
					'not_found'          => __( 'No flow items found.', 'reactwoo-flow' ),
					'not_found_in_trash' => __( 'No flow items found in Trash.', 'reactwoo-flow' ),
				),
				'public'          => false,
				'show_ui'         => false,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor', 'author' ),
				'rewrite'         => false,
			)
		);

		foreach ( self::get_all_field_keys() as $field_key ) {
			register_post_meta(
				self::POST_TYPE,
				self::meta_key( $field_key ),
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_meta_value' ),
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Get item type options.
	 *
	 * @return array
	 */
	public static function get_item_types() {
		return array(
			'idea'                  => __( 'Idea', 'reactwoo-flow' ),
			'feature_request'       => __( 'Feature Request', 'reactwoo-flow' ),
			'bug_report'            => __( 'Bug Report', 'reactwoo-flow' ),
			'support_ticket'        => __( 'Support Ticket', 'reactwoo-flow' ),
			'ux_ui_issue'           => __( 'UX/UI Issue', 'reactwoo-flow' ),
			'technical_debt'        => __( 'Technical Debt', 'reactwoo-flow' ),
			'security_issue'        => __( 'Security Issue', 'reactwoo-flow' ),
			'documentation_request' => __( 'Documentation Request', 'reactwoo-flow' ),
			'research_spike'        => __( 'Research Spike', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get product options.
	 *
	 * @return array
	 */
	public static function get_products() {
		return array(
			'reactwoo_core'           => __( 'ReactWoo Core', 'reactwoo-flow' ),
			'wooaliai'                => __( 'WooAliAI', 'reactwoo-flow' ),
			'geocore_pro'             => __( 'GeoCore Pro', 'reactwoo-flow' ),
			'google_reviews'          => __( 'Google Reviews', 'reactwoo-flow' ),
			'whmcs_bridge'            => __( 'WHMCS Bridge', 'reactwoo-flow' ),
			'licensing_platform'      => __( 'Licensing Platform', 'reactwoo-flow' ),
			'api_platform'            => __( 'API Platform', 'reactwoo-flow' ),
			'customer_portal'         => __( 'Customer Portal', 'reactwoo-flow' ),
			'reactwoo_website'        => __( 'ReactWoo Website', 'reactwoo-flow' ),
			'internal_infrastructure' => __( 'Internal Infrastructure', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get workflow statuses.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			'new'                     => __( 'New', 'reactwoo-flow' ),
			'needs_triage'            => __( 'Needs Triage', 'reactwoo-flow' ),
			'awaiting_information'    => __( 'Awaiting Information', 'reactwoo-flow' ),
			'confirmed'               => __( 'Confirmed', 'reactwoo-flow' ),
			'ready_for_specification' => __( 'Ready for Specification', 'reactwoo-flow' ),
			'ready_for_development'   => __( 'Ready for Development', 'reactwoo-flow' ),
			'in_development'          => __( 'In Development', 'reactwoo-flow' ),
			'ready_for_qa'            => __( 'Ready for QA', 'reactwoo-flow' ),
			'failed_qa'               => __( 'Failed QA', 'reactwoo-flow' ),
			'ready_for_release'       => __( 'Ready for Release', 'reactwoo-flow' ),
			'released'                => __( 'Released', 'reactwoo-flow' ),
			'closed'                  => __( 'Closed', 'reactwoo-flow' ),
			'duplicate'               => __( 'Duplicate', 'reactwoo-flow' ),
			'wont_fix'                => __( "Won't Fix", 'reactwoo-flow' ),
		);
	}

	/**
	 * Get allowed workflow transitions.
	 *
	 * @return array
	 */
	public static function get_status_transition_map() {
		return array(
			'new'                     => array( 'needs_triage', 'awaiting_information', 'confirmed', 'closed', 'duplicate', 'wont_fix' ),
			'needs_triage'            => array( 'awaiting_information', 'confirmed', 'ready_for_specification', 'closed', 'duplicate', 'wont_fix' ),
			'awaiting_information'    => array( 'needs_triage', 'confirmed', 'closed', 'duplicate', 'wont_fix' ),
			'confirmed'               => array( 'ready_for_specification', 'ready_for_development', 'closed', 'duplicate', 'wont_fix' ),
			'ready_for_specification' => array( 'ready_for_development', 'closed', 'duplicate', 'wont_fix' ),
			'ready_for_development'   => array( 'in_development', 'closed', 'duplicate', 'wont_fix' ),
			'in_development'          => array( 'ready_for_qa', 'closed' ),
			'ready_for_qa'            => array( 'failed_qa', 'ready_for_release', 'closed' ),
			'failed_qa'               => array( 'in_development', 'ready_for_qa', 'closed' ),
			'ready_for_release'       => array( 'released', 'closed' ),
			'released'                => array( 'closed' ),
			'closed'                  => array( 'needs_triage' ),
			'duplicate'               => array( 'needs_triage', 'closed' ),
			'wont_fix'                => array( 'needs_triage', 'closed' ),
		);
	}

	/**
	 * Get allowed next statuses for an item.
	 *
	 * @param string $current_status Current status key.
	 * @return array
	 */
	public static function get_available_status_transitions( $current_status ) {
		$statuses = self::get_statuses();
		$map      = self::get_status_transition_map();
		$current  = $current_status ? $current_status : 'new';
		$next     = isset( $map[ $current ] ) ? $map[ $current ] : array();
		$options  = array();

		foreach ( $next as $status_key ) {
			if ( isset( $statuses[ $status_key ] ) ) {
				$options[ $status_key ] = $statuses[ $status_key ];
			}
		}

		return $options;
	}

	/**
	 * Get priority options.
	 *
	 * @return array
	 */
	public static function get_priorities() {
		return array(
			'critical' => __( 'Critical', 'reactwoo-flow' ),
			'high'     => __( 'High', 'reactwoo-flow' ),
			'medium'   => __( 'Medium', 'reactwoo-flow' ),
			'low'      => __( 'Low', 'reactwoo-flow' ),
			'backlog'  => __( 'Backlog', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get severity options.
	 *
	 * @return array
	 */
	public static function get_severities() {
		return array(
			'critical' => __( 'Critical', 'reactwoo-flow' ),
			'major'    => __( 'Major', 'reactwoo-flow' ),
			'minor'    => __( 'Minor', 'reactwoo-flow' ),
			'cosmetic' => __( 'Cosmetic', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get intake source options.
	 *
	 * @return array
	 */
	public static function get_sources() {
		return array(
			'internal_idea'  => __( 'Internal Idea', 'reactwoo-flow' ),
			'support_email'  => __( 'Support Email', 'reactwoo-flow' ),
			'website_form'   => __( 'Website Form', 'reactwoo-flow' ),
			'customer_call'  => __( 'Customer Call', 'reactwoo-flow' ),
			'developer_note' => __( 'Developer Note', 'reactwoo-flow' ),
			'other'          => __( 'Other', 'reactwoo-flow' ),
		);
	}

	/**
	 * Get field definitions grouped by section.
	 *
	 * @return array
	 */
	public static function get_field_groups() {
		return array(
			'request'      => array(
				'title'  => __( 'Request', 'reactwoo-flow' ),
				'fields' => array(
					'product'        => array(
						'label'   => __( 'Product', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_products(),
					),
					'item_type'      => array(
						'label'   => __( 'Item Type', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_item_types(),
					),
					'priority'       => array(
						'label'   => __( 'Priority', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_priorities(),
					),
					'status'         => array(
						'label'   => __( 'Status', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_statuses(),
					),
					'reporter'       => array(
						'label' => __( 'Reporter', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'customer_email' => array(
						'label' => __( 'Customer Email', 'reactwoo-flow' ),
						'type'  => 'email',
					),
					'source'         => array(
						'label'   => __( 'Source', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_sources(),
					),
					'internal_notes' => array(
						'label' => __( 'Internal Notes', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
				),
			),
			'environment'  => array(
				'title'  => __( 'Environment', 'reactwoo-flow' ),
				'fields' => array(
					'severity'            => array(
						'label'   => __( 'Severity', 'reactwoo-flow' ),
						'type'    => 'select',
						'options' => self::get_severities(),
					),
					'plugin_version'      => array(
						'label' => __( 'Plugin Version', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'wordpress_version'   => array(
						'label' => __( 'WordPress Version', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'woocommerce_version' => array(
						'label' => __( 'WooCommerce Version', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'php_version'         => array(
						'label' => __( 'PHP Version', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'theme'               => array(
						'label' => __( 'Theme', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'browser'             => array(
						'label' => __( 'Browser', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'device'              => array(
						'label' => __( 'Device', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'site_url'            => array(
						'label' => __( 'Site URL', 'reactwoo-flow' ),
						'type'  => 'url',
					),
					'license_key'         => array(
						'label' => __( 'License Key', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'error_message'       => array(
						'label' => __( 'Error Message', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'steps_to_reproduce'  => array(
						'label' => __( 'Steps to Reproduce', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'expected_behaviour'  => array(
						'label' => __( 'Expected Behaviour', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'actual_behaviour'    => array(
						'label' => __( 'Actual Behaviour', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
				),
			),
			'attachments'  => array(
				'title'  => __( 'Attachments', 'reactwoo-flow' ),
				'fields' => array(
					'screenshots' => array(
						'label'       => __( 'Screenshots', 'reactwoo-flow' ),
						'type'        => 'textarea',
						'description' => __( 'Add media URLs, one per line.', 'reactwoo-flow' ),
					),
					'log_files'    => array(
						'label'       => __( 'Log Files', 'reactwoo-flow' ),
						'type'        => 'textarea',
						'description' => __( 'Add log URLs or pasted log excerpts.', 'reactwoo-flow' ),
					),
				),
			),
			'agent_execution' => array(
				'title'  => __( 'Agent Execution', 'reactwoo-flow' ),
				'fields' => array(
					'triage_agent_name' => array(
						'label' => __( 'Triage Agent Name', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_type' => array(
						'label' => __( 'Triage Agent Type', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_provider' => array(
						'label' => __( 'Triage Provider', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_model' => array(
						'label' => __( 'Triage Model', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_prompt_template' => array(
						'label' => __( 'Triage Prompt Template', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_status' => array(
						'label' => __( 'Triage Execution Status', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'triage_agent_context' => array(
						'label' => __( 'Triage Context Payload', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'triage_agent_output' => array(
						'label' => __( 'Triage Output', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'triage_agent_error' => array(
						'label' => __( 'Triage Error', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'specification_agent_name' => array(
						'label' => __( 'Specification Agent Name', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_type' => array(
						'label' => __( 'Specification Agent Type', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_provider' => array(
						'label' => __( 'Specification Provider', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_model' => array(
						'label' => __( 'Specification Model', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_prompt_template' => array(
						'label' => __( 'Specification Prompt Template', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_status' => array(
						'label' => __( 'Specification Execution Status', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'specification_agent_context' => array(
						'label' => __( 'Specification Context Payload', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'specification_agent_output' => array(
						'label' => __( 'Specification Output', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'specification_agent_error' => array(
						'label' => __( 'Specification Error', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'development_agent_name' => array(
						'label' => __( 'Development Agent Name', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_type' => array(
						'label' => __( 'Development Agent Type', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_provider' => array(
						'label' => __( 'Development Provider', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_model' => array(
						'label' => __( 'Development Model', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_prompt_template' => array(
						'label' => __( 'Development Prompt Template', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_status' => array(
						'label' => __( 'Development Handoff Status', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'development_agent_context' => array(
						'label' => __( 'Development Context Payload', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'development_agent_output' => array(
						'label' => __( 'Development Output', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
					'development_agent_error' => array(
						'label' => __( 'Development Error', 'reactwoo-flow' ),
						'type'  => 'textarea',
					),
				),
			),
			'ai_analysis'  => array(
				'title'  => __( 'Agent Analysis Output', 'reactwoo-flow' ),
				'fields' => self::get_ai_fields(),
			),
			'specification' => array(
				'title'  => __( 'Specification', 'reactwoo-flow' ),
				'fields' => array(
					'specification_markdown' => array(
						'label'       => __( 'Specification Markdown', 'reactwoo-flow' ),
						'type'        => 'textarea',
						'description' => __( 'Generated from the item context and saved agent triage output. You can edit it before exporting.', 'reactwoo-flow' ),
					),
					'specification_generated_at' => array(
						'label' => __( 'Specification Generated At', 'reactwoo-flow' ),
						'type'  => 'text',
					),
				),
			),
			'integrations' => array(
				'title'  => __( 'Future Integrations', 'reactwoo-flow' ),
				'fields' => array(
					'jira_id'         => array(
						'label' => __( 'Jira ID', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'github_branch'   => array(
						'label' => __( 'GitHub Branch', 'reactwoo-flow' ),
						'type'  => 'text',
					),
					'pr_url'          => array(
						'label' => __( 'PR URL', 'reactwoo-flow' ),
						'type'  => 'url',
					),
					'release_version' => array(
						'label' => __( 'Release Version', 'reactwoo-flow' ),
						'type'  => 'text',
					),
				),
			),
		);
	}

	/**
	 * Get AI field definitions.
	 *
	 * @return array
	 */
	public static function get_ai_fields() {
		return array(
			'ai_summary'               => array(
				'label' => __( 'AI Summary', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'problem_statement'        => array(
				'label' => __( 'Problem Statement', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'user_impact'              => array(
				'label' => __( 'User Impact', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'suggested_solution'       => array(
				'label' => __( 'Suggested Solution', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'acceptance_criteria'      => array(
				'label' => __( 'Acceptance Criteria', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'ux_considerations'        => array(
				'label' => __( 'UX Considerations', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'technical_considerations' => array(
				'label' => __( 'Technical Considerations', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'risks'                    => array(
				'label' => __( 'Risks', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'suggested_priority'       => array(
				'label' => __( 'Suggested Priority', 'reactwoo-flow' ),
				'type'  => 'text',
			),
			'suggested_severity'       => array(
				'label' => __( 'Suggested Severity', 'reactwoo-flow' ),
				'type'  => 'text',
			),
			'suggested_epic'           => array(
				'label' => __( 'Suggested Epic', 'reactwoo-flow' ),
				'type'  => 'text',
			),
			'suggested_stories'        => array(
				'label' => __( 'Suggested Stories', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'suggested_github_branch'  => array(
				'label' => __( 'Suggested GitHub Branch', 'reactwoo-flow' ),
				'type'  => 'text',
			),
			'suggested_qa_checklist'   => array(
				'label' => __( 'Suggested QA Checklist', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'possible_root_cause'      => array(
				'label' => __( 'Possible Root Cause', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'customer_response_draft'  => array(
				'label' => __( 'Customer Response Draft', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
			'developer_notes'          => array(
				'label' => __( 'Developer Notes', 'reactwoo-flow' ),
				'type'  => 'textarea',
			),
		);
	}

	/**
	 * Get all editable field keys.
	 *
	 * @return array
	 */
	public static function get_all_field_keys() {
		$keys = array( 'ai_analyzed', 'ai_analyzed_at', 'ai_raw_response', 'specification_generated', 'specification_raw_response', 'triage_agent_execution', 'specification_agent_execution', 'development_agent_execution', 'development_handoff_prepared', 'development_handoff_prepared_at', 'agent_runs', 'status_changed_at', 'status_history' );

		foreach ( self::get_field_groups() as $group ) {
			foreach ( array_keys( $group['fields'] ) as $field_key ) {
				$keys[] = $field_key;
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Build a namespaced post meta key.
	 *
	 * @param string $field_key Field key.
	 * @return string
	 */
	public static function meta_key( $field_key ) {
		return self::META_PREFIX . $field_key;
	}

	/**
	 * Read an item meta field.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $field_key Field key.
	 * @return string
	 */
	public static function get_meta( $post_id, $field_key ) {
		return (string) get_post_meta( $post_id, self::meta_key( $field_key ), true );
	}

	/**
	 * Update an item meta field.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $field_key Field key.
	 * @param string $value     Field value.
	 */
	public static function update_meta( $post_id, $field_key, $value ) {
		update_post_meta( $post_id, self::meta_key( $field_key ), self::sanitize_meta_value( $value ) );
	}

	/**
	 * Determine whether an item has AI output saved.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_ai_analyzed( $post_id ) {
		return 'yes' === self::get_meta( $post_id, 'ai_analyzed' );
	}

	/**
	 * Determine whether an item has a generated specification saved.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_specification_generated( $post_id ) {
		return 'yes' === self::get_meta( $post_id, 'specification_generated' );
	}

	/**
	 * Determine whether an item has a prepared development handoff.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_development_handoff_prepared( $post_id ) {
		return 'yes' === self::get_meta( $post_id, 'development_handoff_prepared' );
	}

	/**
	 * Get historical agent run records for an item.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_agent_runs( $post_id ) {
		$raw  = self::get_meta( $post_id, 'agent_runs' );
		$runs = json_decode( $raw, true );

		return is_array( $runs ) ? $runs : array();
	}

	/**
	 * Get historical status changes for an item.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_status_history( $post_id ) {
		$raw     = self::get_meta( $post_id, 'status_history' );
		$history = json_decode( $raw, true );

		return is_array( $history ) ? $history : array();
	}

	/**
	 * Transition an item status and record history.
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $new_status New status key.
	 * @param string $note       Optional transition note.
	 * @return true|WP_Error
	 */
	public static function transition_status( $post_id, $new_status, $note = '' ) {
		$statuses       = self::get_statuses();
		$current_status = self::get_meta( $post_id, 'status' );
		$current_status = $current_status ? $current_status : 'new';
		$next_statuses  = self::get_available_status_transitions( $current_status );

		if ( ! isset( $statuses[ $new_status ] ) ) {
			return new WP_Error( 'rwf_invalid_status', __( 'Invalid workflow status.', 'reactwoo-flow' ) );
		}

		if ( $new_status === $current_status ) {
			return true;
		}

		if ( ! isset( $next_statuses[ $new_status ] ) ) {
			return new WP_Error( 'rwf_invalid_status_transition', __( 'That workflow transition is not available from the current status.', 'reactwoo-flow' ) );
		}

		self::update_meta( $post_id, 'status', $new_status );
		self::update_meta( $post_id, 'status_changed_at', current_time( 'mysql' ) );

		$user    = wp_get_current_user();
		$history = self::get_status_history( $post_id );
		$history[] = array(
			'from'        => $current_status,
			'to'          => $new_status,
			'note'        => sanitize_textarea_field( $note ),
			'user_id'     => $user ? (int) $user->ID : 0,
			'user_name'   => $user ? $user->display_name : '',
			'recorded_at' => current_time( 'mysql' ),
		);

		if ( count( $history ) > 100 ) {
			$history = array_slice( $history, -100 );
		}

		self::update_meta( $post_id, 'status_history', wp_json_encode( $history, JSON_PRETTY_PRINT ) );

		return true;
	}

	/**
	 * Sanitize meta values.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_meta_value( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( "\n", array_map( 'sanitize_text_field', $value ) );
		}

		return sanitize_textarea_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Return a label for an option value.
	 *
	 * @param array  $options Option map.
	 * @param string $value   Stored value.
	 * @return string
	 */
	public static function option_label( $options, $value ) {
		return isset( $options[ $value ] ) ? $options[ $value ] : $value;
	}
}
