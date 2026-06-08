<?php
/**
 * Agent-powered workflow helpers.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles agent-driven analysis and specification workflows for flow items.
 */
class RWF_AI {
	/**
	 * Analyze an item and persist generated fields.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function analyse_and_save( $post_id, $overrides = array() ) {
		$analysis = self::analyse_item( $post_id, $overrides );

		if ( is_wp_error( $analysis ) ) {
			return $analysis;
		}

		self::save_analysis( $post_id, $analysis );

		return $analysis;
	}

	/**
	 * Generate a specification and persist it to the item.
	 *
	 * @param int $post_id Item post ID.
	 * @return string|WP_Error
	 */
	public static function generate_specification_and_save( $post_id, $overrides = array() ) {
		$specification = self::generate_specification( $post_id, $overrides );

		if ( is_wp_error( $specification ) ) {
			return $specification;
		}

		self::save_specification( $post_id, $specification );

		return $specification;
	}

	/**
	 * Generate release notes and persist them to the item.
	 *
	 * @param int $post_id Item post ID.
	 * @return string|WP_Error
	 */
	public static function generate_release_notes_and_save( $post_id, $overrides = array() ) {
		$release_notes = self::generate_release_notes( $post_id, $overrides );

		if ( is_wp_error( $release_notes ) ) {
			return $release_notes;
		}

		self::save_release_notes( $post_id, $release_notes );

		return $release_notes;
	}

	/**
	 * Generate a QA review and persist it to the item.
	 *
	 * @param int                  $post_id   Item post ID.
	 * @param array<string, mixed> $overrides Optional provider/model overrides.
	 * @return string|WP_Error
	 */
	public static function generate_qa_review_and_save( $post_id, $overrides = array() ) {
		$review = self::generate_qa_review( $post_id, $overrides );

		if ( is_wp_error( $review ) ) {
			return $review;
		}

		self::save_qa_review( $post_id, $review );

		return $review;
	}

	/**
	 * Generate a UX review and persist it to the item.
	 *
	 * @param int                  $post_id   Item post ID.
	 * @param array<string, mixed> $overrides Optional provider/model overrides.
	 * @return string|WP_Error
	 */
	public static function generate_ux_review_and_save( $post_id, $overrides = array() ) {
		$review = self::generate_ux_review( $post_id, $overrides );

		if ( is_wp_error( $review ) ) {
			return $review;
		}

		self::save_ux_review( $post_id, $review );

		return $review;
	}

	/**
	 * Prepare a development-agent handoff package and persist it to the item.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function prepare_development_handoff_and_save( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::prepare_agent(
			self::merge_agent_args(
				'development',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'Cursor Development Handoff', 'reactwoo-flow' ),
					'agent_type'      => 'development',
					'prompt_template' => 'cursor-development-handoff.md',
					'input_context'   => self::build_development_handoff_context( $post_id ),
					'timeout'         => 0,
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			return $agent;
		}

		$agent['status']       = RWF_Agent::STATUS_PENDING;
		$agent['output']       = __( 'Prepared for Cursor MCP handoff. Execution is external to ReactWoo Flow.', 'reactwoo-flow' );
		$agent['completed_at'] = current_time( 'mysql' );

		self::save_agent_execution( $post_id, 'development', $agent );
		RWF_CPT::update_meta( $post_id, 'development_handoff_prepared', 'yes' );
		RWF_CPT::update_meta( $post_id, 'development_handoff_prepared_at', current_time( 'mysql' ) );

		return $agent;
	}

	/**
	 * Analyze an item through the planning agent.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function analyse_item( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			self::merge_agent_args(
				'planning',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'Planning Triage', 'reactwoo-flow' ),
					'agent_type'      => 'planning',
					'prompt_template' => 'analyse-item.md',
					'input_context'   => self::build_item_context( $post_id ),
					'timeout'         => 45,
					'temperature'     => 0.2,
					'response_format' => array( 'type' => 'json_object' ),
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			self::save_agent_execution( $post_id, 'triage', $agent->get_error_data() );
			return $agent;
		}

		self::save_agent_execution( $post_id, 'triage', $agent );
		$content = (string) $agent['output'];
		$data    = json_decode( $content, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'rwf_agent_invalid_json', __( 'The selected planning agent did not return valid JSON.', 'reactwoo-flow' ), array( 'content' => $content ) );
		}

		$data['ai_raw_response'] = $content;

		return self::normalise_analysis( $data );
	}

	/**
	 * Generate a Markdown specification through the planning agent.
	 *
	 * @param int $post_id Item post ID.
	 * @return string|WP_Error
	 */
	public static function generate_specification( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			self::merge_agent_args(
				'planning',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'Specification Generator', 'reactwoo-flow' ),
					'agent_type'      => 'planning',
					'prompt_template' => 'generate-spec.md',
					'input_context'   => self::build_specification_context( $post_id ),
					'timeout'         => 60,
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			self::save_agent_execution( $post_id, 'specification', $agent->get_error_data() );
			return $agent;
		}

		self::save_agent_execution( $post_id, 'specification', $agent );

		return trim( (string) $agent['output'] );
	}

	/**
	 * Generate Markdown release notes through the release agent.
	 *
	 * @param int $post_id Item post ID.
	 * @return string|WP_Error
	 */
	public static function generate_release_notes( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			self::merge_agent_args(
				'release',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'Release Notes Generator', 'reactwoo-flow' ),
					'agent_type'      => 'release',
					'prompt_template' => 'generate-release-notes.md',
					'input_context'   => self::build_release_context( $post_id ),
					'timeout'         => 60,
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			self::save_agent_execution( $post_id, 'release', $agent->get_error_data() );
			return $agent;
		}

		self::save_agent_execution( $post_id, 'release', $agent );

		return trim( (string) $agent['output'] );
	}

	/**
	 * Generate a QA review through the QA agent.
	 *
	 * @param int                  $post_id   Item post ID.
	 * @param array<string, mixed> $overrides Optional provider/model overrides.
	 * @return string|WP_Error
	 */
	public static function generate_qa_review( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			self::merge_agent_args(
				'qa',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'QA Review', 'reactwoo-flow' ),
					'agent_type'      => 'qa',
					'prompt_template' => 'qa-review.md',
					'input_context'   => self::build_qa_review_context( $post_id ),
					'timeout'         => 60,
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			self::save_agent_execution( $post_id, 'qa', $agent->get_error_data() );
			return $agent;
		}

		self::save_agent_execution( $post_id, 'qa', $agent );

		return trim( (string) $agent['output'] );
	}

	/**
	 * Generate a UX review through the UX agent.
	 *
	 * @param int                  $post_id   Item post ID.
	 * @param array<string, mixed> $overrides Optional provider/model overrides.
	 * @return string|WP_Error
	 */
	public static function generate_ux_review( $post_id, $overrides = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			self::merge_agent_args(
				'ux',
				$post_id,
				$overrides,
				array(
					'name'            => __( 'UX Review', 'reactwoo-flow' ),
					'agent_type'      => 'ux',
					'prompt_template' => 'ux-review.md',
					'input_context'   => self::build_ux_review_context( $post_id ),
					'timeout'         => 60,
				)
			)
		);

		if ( is_wp_error( $agent ) ) {
			self::save_agent_execution( $post_id, 'ux', $agent->get_error_data() );
			return $agent;
		}

		self::save_agent_execution( $post_id, 'ux', $agent );

		return trim( (string) $agent['output'] );
	}

	/**
	 * Build context for QA review prompts.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_qa_review_context( $post_id ) {
		$context = self::build_specification_context( $post_id );
		$context['suggested_qa_checklist'] = RWF_CPT::get_meta( $post_id, 'suggested_qa_checklist' );
		$context['acceptance_criteria']    = RWF_CPT::get_meta( $post_id, 'acceptance_criteria' );

		return $context;
	}

	/**
	 * Build context for UX review prompts.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_ux_review_context( $post_id ) {
		$context = self::build_specification_context( $post_id );
		$context['ux_considerations'] = RWF_CPT::get_meta( $post_id, 'ux_considerations' );
		$context['screenshots']       = RWF_CPT::get_meta( $post_id, 'screenshots' );

		return $context;
	}

	/**
	 * Build the item context sent to an agent.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_item_context( $post_id ) {
		$post = get_post( $post_id );

		$context = array(
			'id'          => $post_id,
			'title'       => $post ? get_the_title( $post ) : '',
			'description' => $post ? wp_strip_all_tags( $post->post_content ) : '',
			'fields'      => array(),
		);

		foreach ( RWF_CPT::get_field_groups() as $group_key => $group ) {
			if ( in_array( $group_key, array( 'agent_overrides', 'agent_execution', 'ai_analysis', 'specification', 'release_notes', 'qa_review', 'ux_review', 'integrations' ), true ) ) {
				continue;
			}

			foreach ( $group['fields'] as $field_key => $definition ) {
				$value = RWF_CPT::get_meta( $post_id, $field_key );

				if ( '' === $value ) {
					continue;
				}

				if ( ! empty( $definition['options'] ) ) {
					$value = RWF_CPT::option_label( $definition['options'], $value );
				}

				$context['fields'][ $field_key ] = array(
					'label' => $definition['label'],
					'value' => $value,
				);
			}
		}

		return $context;
	}

	/**
	 * Build context sent to the specification prompt.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_specification_context( $post_id ) {
		$post    = get_post( $post_id );
		$context = self::build_item_context( $post_id );

		$context['ai_analysis'] = array();
		foreach ( RWF_CPT::get_ai_fields() as $field_key => $definition ) {
			$value = RWF_CPT::get_meta( $post_id, $field_key );

			if ( '' === $value ) {
				continue;
			}

			$context['ai_analysis'][ $field_key ] = array(
				'label' => $definition['label'],
				'value' => $value,
			);
		}

		$context['metadata'] = array(
			'wordpress_post_id' => $post_id,
			'created_at'        => $post ? get_post_time( 'c', true, $post ) : '',
			'ai_analyzed'       => RWF_CPT::is_ai_analyzed( $post_id ) ? 'yes' : 'no',
		);

		return $context;
	}

	/**
	 * Build context sent to the release notes prompt.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_release_context( $post_id ) {
		$post    = get_post( $post_id );
		$context = self::build_specification_context( $post_id );

		$context['specification_markdown'] = RWF_CPT::get_meta( $post_id, 'specification_markdown' );
		$context['release_metadata']      = array(
			'release_version' => RWF_CPT::get_meta( $post_id, 'release_version' ),
			'github_branch'   => RWF_CPT::get_meta( $post_id, 'github_branch' ),
			'pr_url'          => RWF_CPT::get_meta( $post_id, 'pr_url' ),
			'jira_id'         => RWF_CPT::get_meta( $post_id, 'jira_id' ),
			'product_label'   => RWF_CPT::option_label( RWF_CPT::get_products(), RWF_CPT::get_meta( $post_id, 'product' ) ),
			'item_type_label' => RWF_CPT::option_label( RWF_CPT::get_item_types(), RWF_CPT::get_meta( $post_id, 'item_type' ) ),
			'created_at'      => $post ? get_post_time( 'c', true, $post ) : '',
		);

		return $context;
	}

	/**
	 * Build the structured payload intended for Cursor development handoff.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_development_handoff_context( $post_id ) {
		$post         = get_post( $post_id );
		$item_context = self::build_item_context( $post_id );
		$specification = RWF_CPT::get_meta( $post_id, 'specification_markdown' );

		$payload = array(
			'item'                 => $item_context,
			'specification'        => $specification,
			'agent_analysis'       => array(),
			'delivery_intent'      => array(
				'role_boundary' => __( 'ReactWoo Flow prepares context. Cursor performs development work. Jira tracks delivery. GitHub stores source code.', 'reactwoo-flow' ),
				'expected_use'  => __( 'Use this payload as Cursor/MCP context for planning, implementation, bug fixing, refactoring, and test generation.', 'reactwoo-flow' ),
			),
			'suggested_execution'  => array(
				'branch'       => RWF_CPT::get_meta( $post_id, 'suggested_github_branch' ),
				'qa_checklist' => RWF_CPT::get_meta( $post_id, 'suggested_qa_checklist' ),
				'developer_notes' => RWF_CPT::get_meta( $post_id, 'developer_notes' ),
			),
			'future_integrations'  => array(
				'jira_id'         => RWF_CPT::get_meta( $post_id, 'jira_id' ),
				'github_branch'   => RWF_CPT::get_meta( $post_id, 'github_branch' ),
				'pr_url'          => RWF_CPT::get_meta( $post_id, 'pr_url' ),
				'release_version' => RWF_CPT::get_meta( $post_id, 'release_version' ),
			),
			'metadata'             => array(
				'wordpress_post_id'            => $post_id,
				'created_at'                   => $post ? get_post_time( 'c', true, $post ) : '',
				'triage_agent_status'          => RWF_CPT::get_meta( $post_id, 'triage_agent_status' ),
				'specification_agent_status'   => RWF_CPT::get_meta( $post_id, 'specification_agent_status' ),
				'specification_generated'      => RWF_CPT::is_specification_generated( $post_id ) ? 'yes' : 'no',
			),
		);

		foreach ( RWF_CPT::get_ai_fields() as $field_key => $definition ) {
			$value = RWF_CPT::get_meta( $post_id, $field_key );

			if ( '' === $value ) {
				continue;
			}

			$payload['agent_analysis'][ $field_key ] = array(
				'label' => $definition['label'],
				'value' => $value,
			);
		}

		return $payload;
	}

	/**
	 * Build a read-only context package for Cursor/MCP consumers.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	public static function build_cursor_context( $post_id ) {
		$post           = get_post( $post_id );
		$current_status = RWF_CPT::get_meta( $post_id, 'status' );
		$current_status = $current_status ? $current_status : 'new';

		return array(
			'item'                 => self::build_item_context( $post_id ),
			'workflow'             => array(
				'current_status'        => $current_status,
				'current_status_label'  => RWF_CPT::option_label( RWF_CPT::get_statuses(), $current_status ),
				'status_changed_at'     => RWF_CPT::get_meta( $post_id, 'status_changed_at' ),
				'available_transitions' => RWF_CPT::get_available_status_transitions( $current_status ),
				'status_history'        => RWF_CPT::get_status_history( $post_id ),
			),
			'agent_analysis'       => self::build_agent_analysis_context( $post_id ),
			'specification'        => array(
				'generated'    => RWF_CPT::is_specification_generated( $post_id ),
				'generated_at' => RWF_CPT::get_meta( $post_id, 'specification_generated_at' ),
				'markdown'     => RWF_CPT::get_meta( $post_id, 'specification_markdown' ),
			),
			'release_notes'        => array(
				'generated'    => RWF_CPT::is_release_notes_generated( $post_id ),
				'generated_at' => RWF_CPT::get_meta( $post_id, 'release_notes_generated_at' ),
				'markdown'     => RWF_CPT::get_meta( $post_id, 'release_notes_markdown' ),
			),
			'development_handoff'  => array(
				'prepared'    => RWF_CPT::is_development_handoff_prepared( $post_id ),
				'prepared_at' => RWF_CPT::get_meta( $post_id, 'development_handoff_prepared_at' ),
				'package'     => json_decode( RWF_CPT::get_meta( $post_id, 'development_agent_execution' ), true ),
			),
			'agent_runs'           => RWF_CPT::get_agent_runs( $post_id ),
			'future_integrations'  => array(
				'jira_id'         => RWF_CPT::get_meta( $post_id, 'jira_id' ),
				'github_branch'   => RWF_CPT::get_meta( $post_id, 'github_branch' ),
				'pr_url'          => RWF_CPT::get_meta( $post_id, 'pr_url' ),
				'release_version' => RWF_CPT::get_meta( $post_id, 'release_version' ),
			),
			'metadata'             => array(
				'wordpress_post_id' => $post_id,
				'title'             => $post ? get_the_title( $post ) : '',
				'created_at'        => $post ? get_post_time( 'c', true, $post ) : '',
				'updated_at'        => $post ? get_post_modified_time( 'c', true, $post ) : '',
				'context_version'   => '1.0',
				'role_boundary'     => __( 'ReactWoo Flow prepares context and orchestration metadata. Cursor performs development work.', 'reactwoo-flow' ),
			),
		);
	}

	/**
	 * Build saved agent analysis fields.
	 *
	 * @param int $post_id Item post ID.
	 * @return array
	 */
	private static function build_agent_analysis_context( $post_id ) {
		$analysis = array();

		foreach ( RWF_CPT::get_ai_fields() as $field_key => $definition ) {
			$value = RWF_CPT::get_meta( $post_id, $field_key );

			if ( '' === $value ) {
				continue;
			}

			$analysis[ $field_key ] = array(
				'label' => $definition['label'],
				'value' => $value,
			);
		}

		return $analysis;
	}

	/**
	 * Save agent analysis fields.
	 *
	 * @param int   $post_id  Item post ID.
	 * @param array $analysis Analysis data.
	 */
	public static function save_analysis( $post_id, $analysis ) {
		foreach ( RWF_CPT::get_ai_fields() as $field_key => $definition ) {
			if ( array_key_exists( $field_key, $analysis ) ) {
				RWF_CPT::update_meta( $post_id, $field_key, self::stringify_value( $analysis[ $field_key ] ) );
			}
		}

		if ( isset( $analysis['ai_raw_response'] ) ) {
			RWF_CPT::update_meta( $post_id, 'ai_raw_response', self::stringify_value( $analysis['ai_raw_response'] ) );
		}

		RWF_CPT::update_meta( $post_id, 'ai_analyzed', 'yes' );
		RWF_CPT::update_meta( $post_id, 'ai_analyzed_at', current_time( 'mysql' ) );

		$current_status = RWF_CPT::get_meta( $post_id, 'status' );
		$current_status = $current_status ? $current_status : 'new';

		if ( in_array( $current_status, array( 'new', 'needs_triage' ), true ) ) {
			RWF_CPT::transition_status(
				$post_id,
				'confirmed',
				__( 'Auto-advanced after successful triage.', 'reactwoo-flow' )
			);
		}

		if ( ! empty( $analysis['suggested_priority'] ) ) {
			$priority_key = self::option_key_from_label( RWF_CPT::get_priorities(), $analysis['suggested_priority'] );
			if ( $priority_key ) {
				RWF_CPT::update_meta( $post_id, 'priority', $priority_key );
			}
		}

		if ( ! empty( $analysis['suggested_severity'] ) ) {
			$severity_key = self::option_key_from_label( RWF_CPT::get_severities(), $analysis['suggested_severity'] );
			if ( $severity_key ) {
				RWF_CPT::update_meta( $post_id, 'severity', $severity_key );
			}
		}

		RWF_Automation::after_triage( $post_id );
	}

	/**
	 * Save generated specification fields.
	 *
	 * @param int    $post_id       Item post ID.
	 * @param string $specification Specification Markdown.
	 */
	public static function save_specification( $post_id, $specification ) {
		RWF_CPT::update_meta( $post_id, 'specification_markdown', $specification );
		RWF_CPT::update_meta( $post_id, 'specification_raw_response', $specification );
		RWF_CPT::update_meta( $post_id, 'specification_generated', 'yes' );
		RWF_CPT::update_meta( $post_id, 'specification_generated_at', current_time( 'mysql' ) );

		RWF_Automation::after_specification( $post_id );
	}

	/**
	 * Save generated release notes fields.
	 *
	 * @param int    $post_id       Item post ID.
	 * @param string $release_notes Release notes Markdown.
	 */
	public static function save_release_notes( $post_id, $release_notes ) {
		RWF_CPT::update_meta( $post_id, 'release_notes_markdown', $release_notes );
		RWF_CPT::update_meta( $post_id, 'release_notes_raw_response', $release_notes );
		RWF_CPT::update_meta( $post_id, 'release_notes_generated', 'yes' );
		RWF_CPT::update_meta( $post_id, 'release_notes_generated_at', current_time( 'mysql' ) );
	}

	/**
	 * Save generated QA review fields.
	 *
	 * @param int    $post_id Item post ID.
	 * @param string $review  QA review Markdown.
	 */
	public static function save_qa_review( $post_id, $review ) {
		RWF_CPT::update_meta( $post_id, 'qa_review_markdown', $review );
		RWF_CPT::update_meta( $post_id, 'qa_review_generated', 'yes' );
		RWF_CPT::update_meta( $post_id, 'qa_review_generated_at', current_time( 'mysql' ) );
	}

	/**
	 * Save generated UX review fields.
	 *
	 * @param int    $post_id Item post ID.
	 * @param string $review  UX review Markdown.
	 */
	public static function save_ux_review( $post_id, $review ) {
		RWF_CPT::update_meta( $post_id, 'ux_review_markdown', $review );
		RWF_CPT::update_meta( $post_id, 'ux_review_generated', 'yes' );
		RWF_CPT::update_meta( $post_id, 'ux_review_generated_at', current_time( 'mysql' ) );
	}

	/**
	 * Merge runtime and per-item provider/model overrides into agent args.
	 *
	 * @param string               $agent_type        Agent type key.
	 * @param int                  $post_id           Item post ID.
	 * @param array<string, mixed> $runtime_overrides Optional one-off overrides.
	 * @param array<string, mixed> $base_args         Base agent arguments.
	 * @return array<string, mixed>
	 */
	private static function merge_agent_args( $agent_type, $post_id, $runtime_overrides, $base_args ) {
		$resolved = self::resolve_agent_overrides( $post_id, $agent_type, $runtime_overrides );

		if ( '' !== $resolved['provider'] ) {
			$base_args['provider'] = $resolved['provider'];
		}
		if ( '' !== $resolved['model'] ) {
			$base_args['model'] = $resolved['model'];
		}

		return $base_args;
	}

	/**
	 * Resolve provider/model for an agent run.
	 *
	 * Precedence: runtime overrides, then per-item meta, then site settings (via RWF_Agent::prepare_agent).
	 *
	 * @param int                  $post_id           Item post ID.
	 * @param string               $agent_type        Agent type key.
	 * @param array<string, mixed> $runtime_overrides Optional one-off overrides.
	 * @return array{provider: string, model: string}
	 */
	public static function resolve_agent_overrides( $post_id, $agent_type, $runtime_overrides = array() ) {
		$agent_type = sanitize_key( $agent_type );
		$resolved   = array(
			'provider' => '',
			'model'    => '',
		);

		if ( is_array( $runtime_overrides ) ) {
			if ( ! empty( $runtime_overrides['provider'] ) ) {
				$resolved['provider'] = sanitize_key( (string) $runtime_overrides['provider'] );
			}
			if ( ! empty( $runtime_overrides['model'] ) ) {
				$resolved['model'] = sanitize_text_field( (string) $runtime_overrides['model'] );
			}
		}

		if ( '' === $resolved['provider'] ) {
			$meta_provider = RWF_CPT::get_meta( $post_id, 'override_' . $agent_type . '_provider' );
			if ( is_string( $meta_provider ) && '' !== $meta_provider ) {
				$resolved['provider'] = sanitize_key( $meta_provider );
			}
		}

		if ( '' === $resolved['model'] ) {
			$meta_model = RWF_CPT::get_meta( $post_id, 'override_' . $agent_type . '_model' );
			if ( is_string( $meta_model ) && '' !== trim( $meta_model ) ) {
				$resolved['model'] = sanitize_text_field( $meta_model );
			}
		}

		return $resolved;
	}

	/**
	 * Save an agent execution record against the item.
	 *
	 * @param int          $post_id Item post ID.
	 * @param string       $scope   Workflow scope.
	 * @param array|string $agent   Agent execution data.
	 */
	private static function save_agent_execution( $post_id, $scope, $agent ) {
		if ( ! is_array( $agent ) ) {
			return;
		}

		RWF_CPT::update_meta( $post_id, $scope . '_agent_name', isset( $agent['name'] ) ? $agent['name'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_type', isset( $agent['agent_type'] ) ? $agent['agent_type'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_provider', isset( $agent['provider'] ) ? $agent['provider'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_model', isset( $agent['model'] ) ? $agent['model'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_prompt_template', isset( $agent['prompt_template'] ) ? $agent['prompt_template'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_status', isset( $agent['status'] ) ? $agent['status'] : RWF_Agent::STATUS_FAILED );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_context', isset( $agent['input_context'] ) ? wp_json_encode( $agent['input_context'], JSON_PRETTY_PRINT ) : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_output', isset( $agent['output'] ) ? $agent['output'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_error', isset( $agent['error'] ) ? $agent['error'] : '' );
		RWF_CPT::update_meta( $post_id, $scope . '_agent_execution', wp_json_encode( $agent, JSON_PRETTY_PRINT ) );
		self::append_agent_run( $post_id, $scope, $agent );
	}

	/**
	 * Append a compact historical agent run record.
	 *
	 * @param int    $post_id Item post ID.
	 * @param string $scope   Workflow scope.
	 * @param array  $agent   Agent execution data.
	 */
	private static function append_agent_run( $post_id, $scope, $agent ) {
		$runs   = RWF_CPT::get_agent_runs( $post_id );
		$runs[] = array(
			'run_id'          => uniqid( 'rwf_run_', true ),
			'scope'           => $scope,
			'name'            => isset( $agent['name'] ) ? $agent['name'] : '',
			'agent_type'      => isset( $agent['agent_type'] ) ? $agent['agent_type'] : '',
			'provider'        => isset( $agent['provider'] ) ? $agent['provider'] : '',
			'model'           => isset( $agent['model'] ) ? $agent['model'] : '',
			'prompt_template' => isset( $agent['prompt_template'] ) ? $agent['prompt_template'] : '',
			'status'          => isset( $agent['status'] ) ? $agent['status'] : '',
			'error'           => isset( $agent['error'] ) ? $agent['error'] : '',
			'started_at'      => isset( $agent['started_at'] ) ? $agent['started_at'] : '',
			'completed_at'    => isset( $agent['completed_at'] ) ? $agent['completed_at'] : '',
			'recorded_at'     => current_time( 'mysql' ),
			'input_context'   => isset( $agent['input_context'] ) ? $agent['input_context'] : array(),
			'output'          => isset( $agent['output'] ) ? $agent['output'] : '',
		);

		if ( count( $runs ) > 50 ) {
			$runs = array_slice( $runs, -50 );
		}

		RWF_CPT::update_meta( $post_id, 'agent_runs', wp_json_encode( $runs, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Normalize AI response keys and values.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	public static function normalise_analysis( $data ) {
		$key_map = array(
			'summary'                      => 'ai_summary',
			'aiSummary'                    => 'ai_summary',
			'problemStatement'             => 'problem_statement',
			'userImpact'                   => 'user_impact',
			'suggestedSolution'            => 'suggested_solution',
			'acceptanceCriteria'           => 'acceptance_criteria',
			'uxConsiderations'             => 'ux_considerations',
			'technicalConsiderations'      => 'technical_considerations',
			'suggestedPriority'            => 'suggested_priority',
			'suggestedSeverity'            => 'suggested_severity',
			'suggestedEpic'                => 'suggested_epic',
			'suggestedStories'             => 'suggested_stories',
			'suggestedGithubBranch'        => 'suggested_github_branch',
			'suggestedGitHubBranch'        => 'suggested_github_branch',
			'suggestedQAChecklist'         => 'suggested_qa_checklist',
			'qaChecklist'                  => 'suggested_qa_checklist',
			'possibleRootCause'            => 'possible_root_cause',
			'rootCauseGuess'               => 'possible_root_cause',
			'customerResponse'             => 'customer_response_draft',
			'customerResponseDraft'        => 'customer_response_draft',
			'developerNotes'               => 'developer_notes',
			'severityAssessment'           => 'suggested_severity',
			'technical_considerations'     => 'technical_considerations',
			'customer_response'            => 'customer_response_draft',
			'root_cause_guess'             => 'possible_root_cause',
			'severity_assessment'          => 'suggested_severity',
			'qa_checklist'                 => 'suggested_qa_checklist',
			'github_branch'                => 'suggested_github_branch',
			'suggested_github_branch_name' => 'suggested_github_branch',
		);

		foreach ( $key_map as $source_key => $target_key ) {
			if ( isset( $data[ $source_key ] ) && ! isset( $data[ $target_key ] ) ) {
				$data[ $target_key ] = $data[ $source_key ];
			}
		}

		foreach ( RWF_CPT::get_ai_fields() as $field_key => $definition ) {
			if ( ! isset( $data[ $field_key ] ) ) {
				$data[ $field_key ] = '';
			}
		}

		return $data;
	}

	/**
	 * Convert scalar or list values into strings for post meta.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function stringify_value( $value ) {
		if ( is_array( $value ) ) {
			$lines = array();

			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					$lines[] = '- ' . wp_json_encode( $item );
				} else {
					$lines[] = '- ' . (string) $item;
				}
			}

			return implode( "\n", $lines );
		}

		return (string) $value;
	}

	/**
	 * Match an AI option label to a stored key.
	 *
	 * @param array  $options Option labels.
	 * @param string $value   AI value.
	 * @return string
	 */
	private static function option_key_from_label( $options, $value ) {
		$normalized_value = strtolower( trim( (string) $value ) );

		foreach ( $options as $key => $label ) {
			if ( $normalized_value === strtolower( $key ) || $normalized_value === strtolower( $label ) ) {
				return $key;
			}
		}

		return '';
	}
}
