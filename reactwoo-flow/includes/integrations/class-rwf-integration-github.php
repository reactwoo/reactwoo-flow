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
		return '' !== self::get_repository()
			&& '' !== RWF_Settings::get( 'rwf_github_token' );
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
			return new WP_Error( 'rwf_github_not_configured', __( 'GitHub is not configured. Add repository and token in Settings.', 'reactwoo-flow' ) );
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
				$pull = self::find_pull_by_branch( $branch );
			}
		}

		if ( null === $pull || ! is_array( $pull ) ) {
			return new WP_Error( 'rwf_github_pr_not_found', __( 'No matching GitHub pull request was found for this item.', 'reactwoo-flow' ) );
		}

		$state = isset( $pull['state'] ) ? (string) $pull['state'] : '';
		if ( ! empty( $pull['merged_at'] ) ) {
			$state = 'merged';
		}

		$html_url = isset( $pull['html_url'] ) ? (string) $pull['html_url'] : '';
		$head_ref = isset( $pull['head']['ref'] ) ? (string) $pull['head']['ref'] : '';

		if ( '' !== $html_url ) {
			RWF_CPT::update_meta( $post_id, 'pr_url', $html_url );
		}
		if ( '' !== $head_ref ) {
			RWF_CPT::update_meta( $post_id, 'github_branch', $head_ref );
		}
		RWF_CPT::update_meta( $post_id, 'github_pr_state', $state );

		$ci_status = self::fetch_ci_status_for_pull( $pull );
		if ( '' !== $ci_status ) {
			RWF_CPT::update_meta( $post_id, 'github_ci_status', $ci_status );
		}

		return array(
			'pr_url'          => $html_url,
			'github_branch'   => $head_ref,
			'github_pr_state' => $state,
			'github_ci_status' => $ci_status,
			'title'           => isset( $pull['title'] ) ? (string) $pull['title'] : '',
			'number'          => isset( $pull['number'] ) ? (int) $pull['number'] : 0,
		);
	}

	/**
	 * @param array<string, mixed> $pull Pull request payload.
	 * @return string
	 */
	private static function fetch_ci_status_for_pull( $pull ) {
		if ( ! is_array( $pull ) || empty( $pull['head']['sha'] ) ) {
			return '';
		}

		$repo = self::parse_repository();
		if ( null === $repo ) {
			return '';
		}

		$sha    = (string) $pull['head']['sha'];
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
	 * @return string owner/repo
	 */
	private static function get_repository() {
		return trim( RWF_Settings::get( 'rwf_github_repository' ) );
	}

	/**
	 * @return array{owner: string, repo: string}|null
	 */
	private static function parse_repository() {
		$repository = self::get_repository();
		if ( ! preg_match( '#^([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)$#', $repository, $matches ) ) {
			return null;
		}

		return array(
			'owner' => $matches[1],
			'repo'  => $matches[2],
		);
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
	 * @param string $branch Branch name.
	 * @return array<string, mixed>|null
	 */
	private static function find_pull_by_branch( $branch ) {
		$repo = self::parse_repository();
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
