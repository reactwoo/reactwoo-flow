<?php
/**
 * GitHub REST integration.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Links and syncs GitHub pull request metadata for flow items.
 */
class RWF_Integration_GitHub {
	/**
	 * Whether GitHub credentials are configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== RWF_Settings::get( 'rwf_github_token' )
			&& RWF_Settings::has_github_repository_config();
	}

	/**
	 * Verify GitHub API credentials.
	 *
	 * @return true|WP_Error
	 */
	public static function test_connection() {
		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_github_not_configured', __( 'GitHub is not configured.', 'reactwoo-flow' ) );
		}

		$result = RWF_Integration_Http::request_json(
			'GET',
			'https://api.github.com/user',
			array(
				'headers' => self::auth_headers(),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

	/**
	 * Sync pull request metadata for an item.
	 *
	 * @param int $post_id Item post ID.
	 * @return array|WP_Error
	 */
	public static function sync_pull_request( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'rwf_invalid_item', __( 'Invalid ReactWoo Flow item.', 'reactwoo-flow' ) );
		}

		if ( ! self::is_configured() ) {
			return new WP_Error( 'rwf_github_not_configured', __( 'GitHub is not configured. Add a token and at least one repository mapping in Settings.', 'reactwoo-flow' ) );
		}

		$repository = self::get_repository_for_item( $post_id );
		if ( '' === $repository ) {
			return new WP_Error( 'rwf_github_repo_not_found', __( 'No GitHub repository is mapped for this item product.', 'reactwoo-flow' ) );
		}

		$pr_url = RWF_CPT::get_meta( $post_id, 'pr_url' );
		$pull   = null;

		if ( '' !== $pr_url ) {
			$pull = self::fetch_pull_by_url( $pr_url );
		}

		if ( null === $pull ) {
			$branch = RWF_CPT::get_meta( $post_id, 'github_branch' );
			if ( '' === $branch ) {
				$branch = RWF_CPT::get_meta( $post_id, 'suggested_github_branch' );
			}
			if ( '' !== $branch ) {
				$pull = self::find_pull_by_branch( $branch, $repository );
			}
		}

		if ( null === $pull || ! is_array( $pull ) ) {
			return new WP_Error( 'rwf_github_pr_not_found', __( 'No matching GitHub pull request was found for this item.', 'reactwoo-flow' ) );
		}

		return self::apply_pull_payload( $post_id, $pull );
	}

	/**
	 * Persist pull request metadata onto a flow item.
	 *
	 * @param int                  $post_id Item post ID.
	 * @param array<string, mixed> $pull    GitHub pull request payload.
	 * @return array<string, mixed>
	 */
	public static function apply_pull_payload( $post_id, $pull ) {
		$state = isset( $pull['state'] ) ? (string) $pull['state'] : '';
		if ( ! empty( $pull['merged_at'] ) ) {
			$state = 'merged';
		}

		$html_url = isset( $pull['html_url'] ) ? (string) $pull['html_url'] : '';
		$head_ref = isset( $pull['head']['ref'] ) ? (string) $pull['head']['ref'] : '';
		$repo     = self::extract_repository_from_pull( $pull );

		if ( '' !== $repo ) {
			RWF_CPT::update_meta( $post_id, 'github_repository', $repo );
		}
		if ( '' !== $html_url ) {
			RWF_CPT::update_meta( $post_id, 'pr_url', $html_url );
		}
		if ( '' !== $head_ref ) {
			RWF_CPT::update_meta( $post_id, 'github_branch', $head_ref );
		}
		RWF_CPT::update_meta( $post_id, 'github_pr_state', $state );

		$repository = '' !== $repo ? $repo : self::get_repository_for_item( $post_id );
		$ci_status  = self::fetch_ci_status_for_pull( $pull, $repository );
		if ( '' === $ci_status && isset( $pull['head']['sha'] ) ) {
			$ci_status = self::fetch_ci_status_for_sha( (string) $pull['head']['sha'], $repository );
		}
		if ( '' !== $ci_status ) {
			RWF_CPT::update_meta( $post_id, 'github_ci_status', $ci_status );
		}

		RWF_CPT::update_meta( $post_id, 'github_pr_synced_at', current_time( 'mysql' ) );

		return array(
			'pr_url'              => $html_url,
			'github_repository'   => $repo,
			'github_branch'       => $head_ref,
			'github_pr_state'     => $state,
			'github_ci_status'    => $ci_status,
			'title'               => isset( $pull['title'] ) ? (string) $pull['title'] : '',
			'number'              => isset( $pull['number'] ) ? (int) $pull['number'] : 0,
		);
	}

	/**
	 * REST webhook URL for GitHub event delivery.
	 *
	 * @return string
	 */
	public static function get_webhook_url() {
		return rest_url( RWF_REST::NAMESPACE . '/integrations/github/webhook' );
	}

	/**
	 * Whether incoming GitHub webhooks should be processed.
	 *
	 * @return bool
	 */
	public static function is_webhook_enabled() {
		return RWF_Settings::is_yes( 'rwf_github_webhook_enabled' )
			&& '' !== RWF_Settings::get( 'rwf_github_webhook_secret' );
	}

	/**
	 * Validate GitHub webhook signature.
	 *
	 * @param string $body      Raw request body.
	 * @param string $signature X-Hub-Signature-256 header value.
	 * @return bool
	 */
	public static function verify_webhook_signature( $body, $signature ) {
		$secret = RWF_Settings::get( 'rwf_github_webhook_secret' );
		if ( '' === $secret || '' === $signature ) {
			return false;
		}

		if ( 0 !== stripos( $signature, 'sha256=' ) ) {
			return false;
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', (string) $body, $secret );

		return hash_equals( $expected, $signature );
	}

	/**
	 * Handle a parsed GitHub webhook payload.
	 *
	 * @param string               $event   GitHub event header value.
	 * @param array<string, mixed> $payload Webhook JSON body.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function handle_webhook_event( $event, $payload ) {
		if ( ! self::is_webhook_enabled() ) {
			return new WP_Error( 'rwf_github_webhook_disabled', __( 'GitHub webhooks are not enabled.', 'reactwoo-flow' ), array( 'status' => 403 ) );
		}

		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'rwf_github_webhook_invalid', __( 'Invalid GitHub webhook payload.', 'reactwoo-flow' ), array( 'status' => 400 ) );
		}

		update_option( 'rwf_github_webhook_last_received_at', current_time( 'mysql' ) );

		if ( 'pull_request' === $event ) {
			return self::handle_pull_request_event( $payload );
		}

		if ( 'status' === $event ) {
			return self::handle_status_event( $payload );
		}

		return array(
			'ignored' => true,
			'event'   => $event,
		);
	}

	/**
	 * Resolve the GitHub repository for a flow item.
	 *
	 * @param int $post_id Item post ID.
	 * @return string owner/repo
	 */
	public static function get_repository_for_item( $post_id ) {
		$item_repo = trim( RWF_CPT::get_meta( $post_id, 'github_repository' ) );
		if ( self::is_valid_repository( $item_repo ) ) {
			return $item_repo;
		}

		return RWF_Settings::get_github_repository_for_product( RWF_CPT::get_meta( $post_id, 'product' ) );
	}

	/**
	 * @param array<string, mixed> $payload Webhook payload.
	 * @return array<string, mixed>
	 */
	private static function handle_pull_request_event( $payload ) {
		$action = isset( $payload['action'] ) ? (string) $payload['action'] : '';
		$pull   = isset( $payload['pull_request'] ) && is_array( $payload['pull_request'] ) ? $payload['pull_request'] : array();

		if ( ! in_array( $action, array( 'opened', 'synchronize', 'closed', 'reopened', 'edited' ), true ) || empty( $pull ) ) {
			return array(
				'ignored' => true,
				'event'   => 'pull_request',
				'action'  => $action,
			);
		}

		if ( ! self::payload_matches_repository( $payload, $pull ) ) {
			return array(
				'ignored' => true,
				'reason'  => 'repository_mismatch',
			);
		}

		return self::sync_items_for_pull( $pull );
	}

	/**
	 * @param array<string, mixed> $payload Webhook payload.
	 * @return array<string, mixed>
	 */
	private static function handle_status_event( $payload ) {
		$state  = isset( $payload['state'] ) ? sanitize_key( (string) $payload['state'] ) : '';
		$branch = '';

		if ( ! empty( $payload['branches'][0]['name'] ) ) {
			$branch = (string) $payload['branches'][0]['name'];
		}

		if ( '' === $state || '' === $branch ) {
			return array(
				'ignored' => true,
				'event'   => 'status',
			);
		}

		if ( ! self::payload_matches_repository( $payload ) ) {
			return array(
				'ignored' => true,
				'reason'  => 'repository_mismatch',
			);
		}

		$repository = self::extract_repository_from_payload( $payload );
		$item_ids   = self::find_item_ids_for_branch( $branch, $repository );
		$updated    = array();

		foreach ( $item_ids as $item_id ) {
			RWF_CPT::update_meta( $item_id, 'github_ci_status', $state );
			RWF_CPT::update_meta( $item_id, 'github_pr_synced_at', current_time( 'mysql' ) );
			$updated[] = $item_id;
		}

		return array(
			'event'   => 'status',
			'branch'  => $branch,
			'state'   => $state,
			'updated' => $updated,
		);
	}

	/**
	 * @param array<string, mixed> $pull Pull request payload.
	 * @return array<string, mixed>
	 */
	public static function sync_items_for_pull( $pull ) {
		$repository = self::extract_repository_from_pull( $pull );
		$item_ids   = self::find_item_ids_for_pull( $pull, $repository );
		$updated    = array();

		foreach ( $item_ids as $item_id ) {
			$updated[ $item_id ] = self::apply_pull_payload( $item_id, $pull );
		}

		return array(
			'event'   => 'pull_request',
			'updated' => $updated,
		);
	}

	/**
	 * @param array<string, mixed> $pull                  Pull request payload.
	 * @param string               $repository_full_name  Optional owner/repo filter.
	 * @return int[]
	 */
	public static function find_item_ids_for_pull( $pull, $repository_full_name = '' ) {
		$html_url = isset( $pull['html_url'] ) ? (string) $pull['html_url'] : '';
		$head_ref = isset( $pull['head']['ref'] ) ? (string) $pull['head']['ref'] : '';
		$ids      = array();

		if ( '' !== $html_url ) {
			$ids = array_merge( $ids, self::find_item_ids_by_meta_value( 'pr_url', $html_url ) );
		}
		if ( '' !== $head_ref ) {
			$ids = array_merge( $ids, self::find_item_ids_by_meta_value( 'github_branch', $head_ref ) );
		}

		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );

		return self::filter_item_ids_by_repository( $ids, $repository_full_name );
	}

	/**
	 * @param string $branch               Branch name.
	 * @param string $repository_full_name Optional owner/repo filter.
	 * @return int[]
	 */
	public static function find_item_ids_for_branch( $branch, $repository_full_name = '' ) {
		$ids = self::find_item_ids_by_meta_value( 'github_branch', $branch );

		return self::filter_item_ids_by_repository( $ids, $repository_full_name );
	}

	/**
	 * @param int[]  $item_ids             Candidate item IDs.
	 * @param string $repository_full_name owner/repo filter.
	 * @return int[]
	 */
	private static function filter_item_ids_by_repository( $item_ids, $repository_full_name ) {
		if ( '' === $repository_full_name ) {
			return $item_ids;
		}

		$filtered = array();
		foreach ( $item_ids as $item_id ) {
			if ( strcasecmp( self::get_repository_for_item( $item_id ), $repository_full_name ) === 0 ) {
				$filtered[] = $item_id;
			}
		}

		return $filtered;
	}

	/**
	 * @param string $field_key Meta field key without prefix.
	 * @param string $value     Meta value.
	 * @return int[]
	 */
	private static function find_item_ids_by_meta_value( $field_key, $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( $field_key ),
						'value' => $value,
					),
				),
			)
		);

		return array_map( 'absint', $query->posts );
	}

	/**
	 * @param array<string, mixed>      $payload Event payload.
	 * @param array<string, mixed>|null $pull    Optional pull request payload.
	 * @return bool
	 */
	private static function payload_matches_repository( $payload, $pull = null ) {
		$full_name = self::extract_repository_from_payload( $payload, $pull );
		if ( '' === $full_name ) {
			return true;
		}

		$configured = RWF_Settings::get_all_github_repositories();
		if ( empty( $configured ) ) {
			return true;
		}

		foreach ( $configured as $repo ) {
			if ( strcasecmp( $full_name, $repo ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $pull Pull request payload.
	 * @return string
	 */
	private static function extract_repository_from_pull( $pull ) {
		if ( is_array( $pull ) && isset( $pull['base']['repo']['full_name'] ) ) {
			return (string) $pull['base']['repo']['full_name'];
		}

		return '';
	}

	/**
	 * @param array<string, mixed>      $payload Event payload.
	 * @param array<string, mixed>|null $pull    Optional pull request payload.
	 * @return string
	 */
	private static function extract_repository_from_payload( $payload, $pull = null ) {
		if ( isset( $payload['repository']['full_name'] ) ) {
			return (string) $payload['repository']['full_name'];
		}

		return self::extract_repository_from_pull( is_array( $pull ) ? $pull : array() );
	}

	/**
	 * @param array<string, mixed> $pull       Pull request payload.
	 * @param string               $repository owner/repo.
	 * @return string
	 */
	private static function fetch_ci_status_for_pull( $pull, $repository = '' ) {
		if ( ! is_array( $pull ) || empty( $pull['head']['sha'] ) ) {
			return '';
		}

		return self::fetch_ci_status_for_sha( (string) $pull['head']['sha'], $repository );
	}

	/**
	 * @param string $sha        Commit SHA.
	 * @param string $repository owner/repo.
	 * @return string
	 */
	private static function fetch_ci_status_for_sha( $sha, $repository = '' ) {
		$repo = self::parse_repository( $repository );
		if ( null === $repo || '' === $sha ) {
			return '';
		}

		$result = RWF_Integration_Http::request_json(
			'GET',
			sprintf(
				'https://api.github.com/repos/%s/%s/commits/%s/status',
				$repo['owner'],
				$repo['repo'],
				rawurlencode( $sha )
			),
			array(
				'headers' => self::auth_headers(),
			)
		);

		if ( is_wp_error( $result ) || ! is_array( $result['data'] ) ) {
			return '';
		}

		return isset( $result['data']['state'] ) ? (string) $result['data']['state'] : '';
	}

	/**
	 * @param string $repository owner/repo.
	 * @return array{owner: string, repo: string}|null
	 */
	private static function parse_repository( $repository = '' ) {
		if ( '' === $repository ) {
			$repository = trim( RWF_Settings::get( 'rwf_github_repository' ) );
		}

		if ( ! self::is_valid_repository( $repository ) ) {
			return null;
		}

		if ( ! preg_match( '#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$#', $repository, $matches ) ) {
			return null;
		}

		return array(
			'owner' => $matches[1],
			'repo'  => $matches[2],
		);
	}

	/**
	 * @param string $repository owner/repo.
	 * @return bool
	 */
	private static function is_valid_repository( $repository ) {
		return is_string( $repository ) && (bool) preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repository );
	}

	/**
	 * @param string $pr_url Pull request URL.
	 * @return array<string, mixed>|null
	 */
	private static function fetch_pull_by_url( $pr_url ) {
		if ( ! preg_match( '#github\.com/([^/]+)/([^/]+)/pull/(\d+)#i', $pr_url, $matches ) ) {
			return null;
		}

		$result = RWF_Integration_Http::request_json(
			'GET',
			sprintf( 'https://api.github.com/repos/%s/%s/pulls/%d', $matches[1], $matches[2], (int) $matches[3] ),
			array(
				'headers' => self::auth_headers(),
			)
		);

		if ( is_wp_error( $result ) || ! is_array( $result['data'] ) ) {
			return null;
		}

		return $result['data'];
	}

	/**
	 * @param string $branch     Branch name.
	 * @param string $repository owner/repo.
	 * @return array<string, mixed>|null
	 */
	private static function find_pull_by_branch( $branch, $repository ) {
		$repo = self::parse_repository( $repository );
		if ( null === $repo ) {
			return null;
		}

		$query = add_query_arg(
			array(
				'state' => 'open',
				'head'  => $repo['owner'] . ':' . $branch,
			),
			sprintf( 'https://api.github.com/repos/%s/%s/pulls', $repo['owner'], $repo['repo'] )
		);

		$result = RWF_Integration_Http::request_json(
			'GET',
			$query,
			array(
				'headers' => self::auth_headers(),
			)
		);

		if ( is_wp_error( $result ) || ! is_array( $result['data'] ) || empty( $result['data'][0] ) ) {
			return null;
		}

		return $result['data'][0];
	}

	/**
	 * @return array<string, string>
	 */
	private static function auth_headers() {
		return array(
			'Authorization' => 'Bearer ' . RWF_Settings::get( 'rwf_github_token' ),
			'User-Agent'    => 'ReactWoo-Flow',
		);
	}
}
