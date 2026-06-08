<?php
/**
 * Jira Cloud REST integration.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates Jira issues from ReactWoo Flow items.
 */
class RWF_Integration_Jira {
	/**
	 * Whether Jira credentials are configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::get_base_url()
			&& '' !== RWF_Settings::get( 'rwf_jira_email' )
			&& '' !== RWF_Settings::get( 'rwf_jira_api_token' )
			&& '' !== RWF_Settings::get( 'rwf_jira_project_key' );
	}

	/**
	 * Create a Jira issue from a flow item.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function create_issue_from_item( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_jira_not_configured', __( 'Jira is not configured. Add URL, email, API token, and project key in Settings.', 'reactwoo-flow' ) );
		}

		$existing = RWF_CPT::get_meta( $post_id, 'jira_id' );
		if ( '' !== $existing ) {
			return new WP_Error(
				'rwf_jira_already_linked',
				__( 'This item already has a linked Jira issue.', 'reactwoo-flow' ),
				array(
					'jira_id'  => $existing,
					'jira_url' => RWF_CPT::get_meta( $post_id, 'jira_url' ),
				)
			);
		}

		$item_type = RWF_CPT::get_meta( $post_id, 'item_type' );
		$summary   = get_the_title( $post );
		$body_text = self::build_issue_description( $post_id, $post );

		$payload = array(
			'fields' => array(
				'project'     => array(
					'key' => RWF_Settings::get( 'rwf_jira_project_key' ),
				),
				'summary'     => $summary,
				'description' => self::text_to_adf( $body_text ),
				'issuetype'   => array(
					'name' => self::map_issue_type( $item_type ),
				),
			),
		);

		$labels = self::build_labels( $post_id );
		if ( ! empty( $labels ) ) {
			$payload['fields']['labels'] = $labels;
		}

		$result = RWF_Integration_Http::request_json(
			'POST',
			trailingslashit( self::get_base_url() ) . 'rest/api/3/issue',
			array(
				'headers' => array(
					'Authorization' => self::auth_header(),
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data    = is_array( $result['data'] ) ? $result['data'] : array();
		$issue_key = isset( $data['key'] ) ? (string) $data['key'] : '';
		$issue_id  = isset( $data['id'] ) ? (string) $data['id'] : '';

		if ( '' === $issue_key ) {
			return new WP_Error( 'rwf_jira_invalid_response', __( 'Jira did not return an issue key.', 'reactwoo-flow' ), $data );
		}

		$issue_url = trailingslashit( self::get_base_url() ) . 'browse/' . rawurlencode( $issue_key );

		RWF_CPT::update_meta( $post_id, 'jira_id', $issue_key );
		RWF_CPT::update_meta( $post_id, 'jira_url', $issue_url );

		return array(
			'jira_id'  => $issue_key,
			'jira_key' => $issue_key,
			'jira_url' => $issue_url,
			'remote_id' => $issue_id,
		);
	}

	/**
	 * @return string
	 */
	private static function get_base_url() {
		return untrailingslashit( RWF_Settings::get( 'rwf_jira_url' ) );
	}

	/**
	 * @return string
	 */
	private static function auth_header() {
		$email = RWF_Settings::get( 'rwf_jira_email' );
		$token = RWF_Settings::get( 'rwf_jira_api_token' );

		return 'Basic ' . base64_encode( $email . ':' . $token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * @param string $item_type Flow item type key.
	 * @return string
	 */
	private static function map_issue_type( $item_type ) {
		if ( in_array( $item_type, array( 'bug_report', 'security_issue', 'ux_ui_issue' ), true ) ) {
			return 'Bug';
		}
		if ( in_array( $item_type, array( 'feature_request', 'idea' ), true ) ) {
			return 'Story';
		}

		return 'Task';
	}

	/**
	 * @param int     $post_id Item post ID.
	 * @param WP_Post $post    Post object.
	 * @return string
	 */
	private static function build_issue_description( $post_id, $post ) {
		$parts = array(
			__( 'Created from ReactWoo Flow.', 'reactwoo-flow' ),
			'',
			wp_strip_all_tags( $post->post_content ),
		);

		$summary = RWF_CPT::get_meta( $post_id, 'ai_summary' );
		if ( '' !== $summary ) {
			$parts[] = '';
			$parts[] = __( 'AI Summary', 'reactwoo-flow' ) . ':';
			$parts[] = $summary;
		}

		$acceptance = RWF_CPT::get_meta( $post_id, 'acceptance_criteria' );
		if ( '' !== $acceptance ) {
			$parts[] = '';
			$parts[] = __( 'Acceptance Criteria', 'reactwoo-flow' ) . ':';
			$parts[] = $acceptance;
		}

		$product = RWF_CPT::option_label( RWF_CPT::get_products(), RWF_CPT::get_meta( $post_id, 'product' ) );
		$type    = RWF_CPT::option_label( RWF_CPT::get_item_types(), RWF_CPT::get_meta( $post_id, 'item_type' ) );

		$parts[] = '';
		$parts[] = sprintf(
			/* translators: 1: product label, 2: item type label, 3: WordPress item ID */
			__( 'Product: %1$s | Type: %2$s | Flow item #%3$d', 'reactwoo-flow' ),
			$product,
			$type,
			$post_id
		);

		return implode( "\n", $parts );
	}

	/**
	 * @param int $post_id Item post ID.
	 * @return string[]
	 */
	private static function build_labels( $post_id ) {
		$labels = array( 'reactwoo-flow' );
		$product = sanitize_key( RWF_CPT::get_meta( $post_id, 'product' ) );
		$type    = sanitize_key( RWF_CPT::get_meta( $post_id, 'item_type' ) );

		if ( '' !== $product ) {
			$labels[] = 'product-' . $product;
		}
		if ( '' !== $type ) {
			$labels[] = 'type-' . $type;
		}

		return array_values( array_unique( $labels ) );
	}

	/**
	 * Convert plain text to Atlassian Document Format.
	 *
	 * @param string $text Plain text.
	 * @return array<string, mixed>
	 */
	public static function text_to_adf( $text ) {
		$paragraphs = array();

		foreach ( preg_split( "/\r\n|\r|\n/", (string) $text ) as $line ) {
			$paragraphs[] = array(
				'type'    => 'paragraph',
				'content' => array(
					array(
						'type' => 'text',
						'text' => (string) $line,
					),
				),
			);
		}

		if ( empty( $paragraphs ) ) {
			$paragraphs[] = array(
				'type'    => 'paragraph',
				'content' => array(
					array(
						'type' => 'text',
						'text' => '',
					),
				),
			);
		}

		return array(
			'type'    => 'doc',
			'version' => 1,
			'content' => $paragraphs,
		);
	}
}
