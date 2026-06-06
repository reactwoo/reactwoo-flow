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
	public static function analyse_and_save( $post_id ) {
		$analysis = self::analyse_item( $post_id );

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
	public static function generate_specification_and_save( $post_id ) {
		$specification = self::generate_specification( $post_id );

		if ( is_wp_error( $specification ) ) {
			return $specification;
		}

		self::save_specification( $post_id, $specification );

		return $specification;
	}

	/**
	 * Analyze an item through the planning agent.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function analyse_item( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			array(
				'name'            => __( 'Planning Triage', 'reactwoo-flow' ),
				'agent_type'      => 'planning',
				'prompt_template' => 'analyse-item.md',
				'input_context'   => self::build_item_context( $post_id ),
				'timeout'         => 45,
				'temperature'     => 0.2,
				'response_format' => array( 'type' => 'json_object' ),
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
	public static function generate_specification( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$agent = RWF_Agent::execute(
			array(
				'name'            => __( 'Specification Generator', 'reactwoo-flow' ),
				'agent_type'      => 'planning',
				'prompt_template' => 'generate-spec.md',
				'input_context'   => self::build_specification_context( $post_id ),
				'timeout'         => 60,
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
			if ( in_array( $group_key, array( 'agent_execution', 'ai_analysis', 'specification', 'integrations' ), true ) ) {
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
	}

	/**
	 * Normalize AI response keys and values.
	 *
	 * @param array $data Raw data.
	 * @return array
	 */
	private static function normalise_analysis( $data ) {
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
