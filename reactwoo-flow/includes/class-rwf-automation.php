<?php
/**
 * Workflow automation hooks.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional post-triage and post-spec automation.
 */
class RWF_Automation {
	/**
	 * Run automation after successful triage.
	 *
	 * @param int $post_id Item post ID.
	 * @return void
	 */
	public static function after_triage( $post_id ) {
		if ( RWF_Settings::is_yes( 'rwf_auto_apply_suggested_branch' ) || RWF_Settings::is_yes( 'rwf_auto_apply_default_epic' ) ) {
			self::apply_triage_suggestions( $post_id, true );
		}

		if ( RWF_Settings::is_yes( 'rwf_auto_create_jira_on_triage' ) && RWF_Integration_Jira::is_configured() ) {
			if ( '' === RWF_CPT::get_meta( $post_id, 'jira_id' ) ) {
				RWF_Integration_Jira::create_issue_from_item( $post_id );
			}
		}
	}

	/**
	 * Apply triage agent delivery hints to integration fields.
	 *
	 * @param int  $post_id Item post ID.
	 * @param bool $respect_auto_settings When true, only apply fields enabled in automation settings.
	 * @return array<string, mixed>
	 */
	public static function apply_triage_suggestions( $post_id, $respect_auto_settings = false ) {
		$applied = array(
			'github_branch' => false,
			'jira_epic_key' => false,
		);

		$apply_branch = ! $respect_auto_settings || RWF_Settings::is_yes( 'rwf_auto_apply_suggested_branch' );
		$apply_epic   = ! $respect_auto_settings || RWF_Settings::is_yes( 'rwf_auto_apply_default_epic' );

		if ( $apply_branch && '' === RWF_CPT::get_meta( $post_id, 'github_branch' ) ) {
			$suggested = trim( (string) RWF_CPT::get_meta( $post_id, 'suggested_github_branch' ) );
			if ( '' !== $suggested ) {
				$branch = str_replace( '{id}', (string) $post_id, $suggested );
				RWF_CPT::update_meta( $post_id, 'github_branch', sanitize_text_field( $branch ) );
				$applied['github_branch'] = $branch;
			}
		}

		if ( $apply_epic && '' === RWF_CPT::get_meta( $post_id, 'jira_epic_key' ) ) {
			$epic_key = RWF_Integration_Jira::normalise_issue_key( RWF_Settings::get( 'rwf_jira_default_epic_key' ) );
			if ( '' !== $epic_key ) {
				RWF_CPT::update_meta( $post_id, 'jira_epic_key', $epic_key );
				$applied['jira_epic_key'] = $epic_key;
			}
		}

		return $applied;
	}

	/**
	 * Run automation after specification generation.
	 *
	 * @param int $post_id Item post ID.
	 * @return void
	 */
	public static function after_specification( $post_id ) {
		if ( RWF_Settings::is_yes( 'rwf_auto_publish_confluence_on_spec' ) && RWF_Integration_Confluence::is_configured() ) {
			if ( '' === RWF_CPT::get_meta( $post_id, 'confluence_page_id' ) && RWF_CPT::is_specification_generated( $post_id ) ) {
				RWF_Integration_Confluence::publish_specification( $post_id );
			}
		}

		$current_status = RWF_CPT::get_meta( $post_id, 'status' );
		$current_status = $current_status ? $current_status : 'new';

		if ( RWF_Settings::is_yes( 'rwf_auto_advance_ready_for_development' ) && 'ready_for_specification' === $current_status ) {
			RWF_CPT::transition_status(
				$post_id,
				'ready_for_development',
				__( 'Auto-advanced after specification generation.', 'reactwoo-flow' )
			);
		}
	}

	/**
	 * Run automation after development handoff preparation.
	 *
	 * @param int $post_id Item post ID.
	 * @return void
	 */
	public static function after_handoff( $post_id ) {
		if ( RWF_Settings::is_yes( 'rwf_auto_send_cursor_on_handoff' ) && RWF_Integration_Cursor_MCP::is_configured() ) {
			if ( '' === RWF_CPT::get_meta( $post_id, 'cursor_handoff_sent_at' ) ) {
				RWF_Integration_Cursor_MCP::send_handoff( $post_id );
			}
		}

		if ( RWF_Settings::is_yes( 'rwf_auto_sync_github_on_handoff' ) && RWF_Integration_GitHub::is_configured() ) {
			$pr_url = RWF_CPT::get_meta( $post_id, 'pr_url' );
			$branch = RWF_CPT::get_meta( $post_id, 'github_branch' );
			if ( '' !== $pr_url || '' !== $branch ) {
				RWF_Integration_GitHub::sync_pull_request( $post_id );
			}
		}
	}
}
