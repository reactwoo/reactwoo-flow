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
		if ( RWF_Settings::is_yes( 'rwf_auto_create_jira_on_triage' ) && RWF_Integration_Jira::is_configured() ) {
			if ( '' === RWF_CPT::get_meta( $post_id, 'jira_id' ) ) {
				RWF_Integration_Jira::create_issue_from_item( $post_id );
			}
		}
	}

	/**
	 * Run automation after specification generation.
	 *
	 * @param int $post_id Item post ID.
	 * @return void
	 */
	public static function after_specification( $post_id ) {
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
}
