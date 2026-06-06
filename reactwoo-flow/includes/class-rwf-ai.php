<?php
/**
 * OpenAI triage integration.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles AI analysis for flow items.
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
	 * Analyze an item with OpenAI.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function analyse_item( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		$api_key = RWF_Settings::get( 'rwf_openai_api_key' );
		if ( '' === $api_key ) {
			return new WP_Error( 'rwf_missing_api_key', __( 'Add an OpenAI API key in ReactWoo Flow settings before running AI analysis.', 'reactwoo-flow' ) );
		}

		$model   = RWF_Settings::get( 'rwf_openai_model' );
		$payload = array(
			'model'           => '' !== $model ? $model : 'gpt-4o-mini',
			'temperature'     => 0.2,
			'response_format' => array( 'type' => 'json_object' ),
			'messages'        => array(
				array(
					'role'    => 'system',
					'content' => self::get_prompt(),
				),
				array(
					'role'    => 'user',
					'content' => wp_json_encode( self::build_item_context( $post_id ), JSON_PRETTY_PRINT ),
				),
			),
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		$body        = (string) wp_remote_retrieve_body( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'rwf_openai_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'OpenAI request failed with status %d.', 'reactwoo-flow' ),
					$status_code
				),
				array( 'body' => $body )
			);
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || empty( $decoded['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'rwf_openai_invalid_response', __( 'OpenAI returned an unexpected response.', 'reactwoo-flow' ) );
		}

		$content = (string) $decoded['choices'][0]['message']['content'];
		$data    = json_decode( $content, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'rwf_openai_invalid_json', __( 'OpenAI did not return valid JSON.', 'reactwoo-flow' ), array( 'content' => $content ) );
		}

		$data['ai_raw_response'] = $content;

		return self::normalise_analysis( $data );
	}

	/**
	 * Build the context sent to AI.
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
			if ( in_array( $group_key, array( 'ai_analysis', 'integrations' ), true ) ) {
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
	 * Save AI analysis fields.
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
	 * Read the analysis prompt.
	 *
	 * @return string
	 */
	private static function get_prompt() {
		$prompt_file = RWF_PLUGIN_DIR . 'prompts/analyse-item.md';

		if ( file_exists( $prompt_file ) ) {
			$prompt = file_get_contents( $prompt_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false !== $prompt ) {
				return $prompt;
			}
		}

		return 'You are ReactWoo Flow AI triage. Return strict JSON using the requested field keys.';
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
