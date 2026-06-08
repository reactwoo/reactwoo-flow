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
	 * Verify Jira API credentials.
	 *
	 * @return true|WP_Error
	 */
	public static function test_connection() {
		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_jira_not_configured', __( 'Jira is not configured.', 'reactwoo-flow' ) );
		}

		$result = RWF_Integration_Http::request_json(
			'GET',
			trailingslashit( self::get_base_url() ) . 'rest/api/3/myself',
			array(
				'headers' => array(
					'Authorization' => self::auth_header(),
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
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

		self::append_epic_link_fields( $payload['fields'], $post_id );

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
	 * Resolve the epic issue key for Jira linking.
	 *
	 * @param int $post_id Item post ID.
	 * @return string
	 */
	public static function resolve_epic_key( $post_id ) {
		$item_epic = self::normalise_issue_key( RWF_CPT::get_meta( $post_id, 'jira_epic_key' ) );
		if ( '' !== $item_epic ) {
			return $item_epic;
		}

		return self::normalise_issue_key( RWF_Settings::get( 'rwf_jira_default_epic_key' ) );
	}

	/**
	 * Append epic link fields to a Jira create-issue payload.
	 *
	 * @param array<string, mixed> $fields  Jira fields array (by reference).
	 * @param int                  $post_id Item post ID.
	 * @return void
	 */
	public static function append_epic_link_fields( &$fields, $post_id ) {
		$epic_key = self::resolve_epic_key( $post_id );
		if ( '' === $epic_key ) {
			return;
		}

		$link_field = trim( (string) RWF_Settings::get( 'rwf_jira_epic_link_field' ) );
		if ( '' !== $link_field ) {
			$fields[ $link_field ] = $epic_key;
			return;
		}

		$fields['parent'] = array(
			'key' => $epic_key,
		);
	}

	/**
	 * @param string $issue_key Raw issue key.
	 * @return string
	 */
	public static function normalise_issue_key( $issue_key ) {
		$key = strtoupper( trim( (string) $issue_key ) );
		if ( '' === $key ) {
			return '';
		}

		return preg_match( '/^[A-Z][A-Z0-9_]+-\d+$/', $key ) ? $key : '';
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

	/**
	 * Sync Jira issue status for a linked item.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function sync_issue_status( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_jira_not_configured', __( 'Jira is not configured.', 'reactwoo-flow' ) );
		}

		$issue_key = RWF_CPT::get_meta( $post_id, 'jira_id' );
		if ( '' === $issue_key ) {
			return new WP_Error( 'rwf_jira_not_linked', __( 'This item has no linked Jira issue.', 'reactwoo-flow' ) );
		}

		$result = RWF_Integration_Http::request_json(
			'GET',
			trailingslashit( self::get_base_url() ) . 'rest/api/3/issue/' . rawurlencode( $issue_key ) . '?fields=status',
			array(
				'headers' => array(
					'Authorization' => self::auth_header(),
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data   = is_array( $result['data'] ) ? $result['data'] : array();
		$status = isset( $data['fields']['status']['name'] ) ? (string) $data['fields']['status']['name'] : '';

		if ( '' === $status ) {
			return new WP_Error( 'rwf_jira_status_missing', __( 'Jira did not return an issue status.', 'reactwoo-flow' ), $data );
		}

		RWF_CPT::update_meta( $post_id, 'jira_status', $status );
		RWF_CPT::update_meta( $post_id, 'jira_status_synced_at', current_time( 'mysql' ) );

		return array(
			'jira_id'     => $issue_key,
			'jira_status' => $status,
			'jira_url'    => RWF_CPT::get_meta( $post_id, 'jira_url' ),
		);
	}
}
