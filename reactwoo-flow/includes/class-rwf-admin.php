<?php
/**
 * Admin UI and actions.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the ReactWoo Flow admin experience.
 */
class RWF_Admin {
	const PAGE_DASHBOARD = 'rwf-dashboard';
	const PAGE_INBOX = 'rwf-inbox';
	const PAGE_ITEM = 'rwf-item';
	const PAGE_SETTINGS = 'rwf-settings';

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_rwf_save_item', array( __CLASS__, 'handle_save_item' ) );
		add_action( 'admin_post_rwf_transition_status', array( __CLASS__, 'handle_transition_status' ) );
		add_action( 'admin_post_rwf_bulk_items', array( __CLASS__, 'handle_bulk_items' ) );
		add_action( 'admin_post_rwf_export_specification', array( __CLASS__, 'handle_export_specification' ) );
		add_action( 'admin_post_rwf_export_release_notes', array( __CLASS__, 'handle_export_release_notes' ) );
		add_action( 'admin_post_rwf_export_development_handoff', array( __CLASS__, 'handle_export_development_handoff' ) );
		add_action( 'admin_post_rwf_export_agent_runs', array( __CLASS__, 'handle_export_agent_runs' ) );
		add_action( 'admin_post_rwf_export_item_context', array( __CLASS__, 'handle_export_item_context' ) );
		add_action( 'admin_post_rwf_export_qa_review', array( __CLASS__, 'handle_export_qa_review' ) );
		add_action( 'admin_post_rwf_export_ux_review', array( __CLASS__, 'handle_export_ux_review' ) );
		add_action( 'admin_post_rwf_test_integrations', array( __CLASS__, 'handle_test_integrations' ) );
	}

	/**
	 * Register admin pages.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'ReactWoo Flow', 'reactwoo-flow' ),
			__( 'ReactWoo Flow', 'reactwoo-flow' ),
			'edit_rwf_items',
			self::PAGE_DASHBOARD,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-networking',
			26
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Dashboard', 'reactwoo-flow' ),
			__( 'Dashboard', 'reactwoo-flow' ),
			'edit_rwf_items',
			self::PAGE_DASHBOARD,
			array( __CLASS__, 'render_dashboard' )
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Inbox', 'reactwoo-flow' ),
			__( 'Inbox', 'reactwoo-flow' ),
			'edit_rwf_items',
			self::PAGE_INBOX,
			array( __CLASS__, 'render_inbox' )
		);

		add_submenu_page(
			null,
			__( 'Flow Item', 'reactwoo-flow' ),
			__( 'Flow Item', 'reactwoo-flow' ),
			'edit_rwf_items',
			self::PAGE_ITEM,
			array( __CLASS__, 'render_item' )
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Settings', 'reactwoo-flow' ),
			__( 'Settings', 'reactwoo-flow' ),
			RWF_Capabilities::CAP_MANAGE,
			self::PAGE_SETTINGS,
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( false === strpos( $hook, 'rwf' ) && false === strpos( $page, 'rwf-' ) ) {
			return;
		}

		wp_enqueue_style(
			'rwf-admin',
			RWF_PLUGIN_URL . 'assets/admin.css',
			array(),
			RWF_VERSION
		);

		wp_enqueue_script(
			'rwf-admin',
			RWF_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			RWF_VERSION,
			true
		);

		wp_enqueue_media();

		wp_localize_script(
			'rwf-admin',
			'rwfAdmin',
			array(
				'restUrl'      => esc_url_raw( rest_url( RWF_REST::NAMESPACE ) ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'analysing'         => __( 'Analysing...', 'reactwoo-flow' ),
				'analyseLabel'      => __( 'Run Triage Agent', 'reactwoo-flow' ),
				'errorLabel'        => __( 'Triage agent failed. Check provider settings and try again.', 'reactwoo-flow' ),
				'doneLabel'         => __( 'Agent analysis saved. Refreshing...', 'reactwoo-flow' ),
				'generatingSpec'    => __( 'Generating specification...', 'reactwoo-flow' ),
				'generateSpecLabel' => __( 'Generate Specification', 'reactwoo-flow' ),
				'specErrorLabel'    => __( 'Specification generation failed. Check settings and try again.', 'reactwoo-flow' ),
				'specDoneLabel'     => __( 'Specification saved. Refreshing...', 'reactwoo-flow' ),
				'preparingHandoff'  => __( 'Preparing Cursor handoff...', 'reactwoo-flow' ),
				'handoffLabel'      => __( 'Prepare Cursor Handoff', 'reactwoo-flow' ),
				'handoffErrorLabel' => __( 'Cursor handoff preparation failed.', 'reactwoo-flow' ),
				'handoffDoneLabel'  => __( 'Cursor handoff package saved. Refreshing...', 'reactwoo-flow' ),
				'generatingReleaseNotes' => __( 'Generating release notes...', 'reactwoo-flow' ),
				'generateReleaseNotesLabel' => __( 'Generate Release Notes', 'reactwoo-flow' ),
				'releaseNotesErrorLabel' => __( 'Release notes generation failed. Check settings and try again.', 'reactwoo-flow' ),
				'releaseNotesDoneLabel' => __( 'Release notes saved. Refreshing...', 'reactwoo-flow' ),
				'creatingJira'        => __( 'Creating Jira issue...', 'reactwoo-flow' ),
				'createJiraLabel'       => __( 'Create Jira Issue', 'reactwoo-flow' ),
				'jiraErrorLabel'        => __( 'Jira issue creation failed. Check Settings and try again.', 'reactwoo-flow' ),
				'jiraDoneLabel'         => __( 'Jira issue linked. Refreshing...', 'reactwoo-flow' ),
				'publishingConfluence'  => __( 'Publishing to Confluence...', 'reactwoo-flow' ),
				'publishConfluenceLabel' => __( 'Publish to Confluence', 'reactwoo-flow' ),
				'confluenceErrorLabel'  => __( 'Confluence publish failed. Check Settings and try again.', 'reactwoo-flow' ),
				'confluenceDoneLabel'   => __( 'Specification published. Refreshing...', 'reactwoo-flow' ),
				'syncingGithub'         => __( 'Syncing GitHub PR...', 'reactwoo-flow' ),
				'syncGithubLabel'       => __( 'Sync GitHub PR', 'reactwoo-flow' ),
				'githubErrorLabel'      => __( 'GitHub sync failed. Check repository, token, and branch/PR URL.', 'reactwoo-flow' ),
				'githubDoneLabel'       => __( 'GitHub PR synced. Refreshing...', 'reactwoo-flow' ),
				'sendingCursor'         => __( 'Sending to Cursor MCP...', 'reactwoo-flow' ),
				'sendCursorLabel'       => __( 'Send to Cursor MCP', 'reactwoo-flow' ),
				'cursorErrorLabel'      => __( 'Cursor MCP handoff failed. Check the bridge endpoint.', 'reactwoo-flow' ),
				'cursorDoneLabel'       => __( 'Handoff sent to Cursor MCP. Refreshing...', 'reactwoo-flow' ),
				'runningQaReview'       => __( 'Running QA review...', 'reactwoo-flow' ),
				'runQaReviewLabel'      => __( 'Run QA Review', 'reactwoo-flow' ),
				'qaReviewErrorLabel'    => __( 'QA review failed. Check agent settings and try again.', 'reactwoo-flow' ),
				'qaReviewDoneLabel'     => __( 'QA review saved. Refreshing...', 'reactwoo-flow' ),
				'runningUxReview'       => __( 'Running UX review...', 'reactwoo-flow' ),
				'runUxReviewLabel'      => __( 'Run UX Review', 'reactwoo-flow' ),
				'uxReviewErrorLabel'    => __( 'UX review failed. Check agent settings and try again.', 'reactwoo-flow' ),
				'uxReviewDoneLabel'     => __( 'UX review saved. Refreshing...', 'reactwoo-flow' ),
				'syncingJira'           => __( 'Syncing Jira status...', 'reactwoo-flow' ),
				'syncJiraLabel'         => __( 'Sync Jira Status', 'reactwoo-flow' ),
				'jiraSyncErrorLabel'    => __( 'Jira status sync failed.', 'reactwoo-flow' ),
				'jiraSyncDoneLabel'     => __( 'Jira status synced. Refreshing...', 'reactwoo-flow' ),
			)
		);
	}

	/**
	 * Render dashboard page.
	 */
	public static function render_dashboard() {
		$stats                  = self::get_dashboard_stats();
		$integration_summary    = RWF_Integrations::get_configuration_summary();
		$integration_test_results = RWF_Integrations::get_last_test_results();
		$integration_tested_at  = get_option( 'rwf_integration_health_last_test', '' );

		include RWF_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render inbox page.
	 */
	public static function render_inbox() {
		$query   = self::get_inbox_query();
		$filters = self::get_current_filters();

		include RWF_PLUGIN_DIR . 'admin/views/inbox.php';
	}

	/**
	 * Render item edit/create page.
	 */
	public static function render_item() {
		$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( $post_id && ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		if ( $post_id && ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this item.', 'reactwoo-flow' ) );
		}

		include RWF_PLUGIN_DIR . 'admin/views/item-edit.php';
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings() {
		$integration_summary      = RWF_Integrations::get_configuration_summary();
		$integration_test_results = RWF_Integrations::get_last_test_results();
		$integration_tested_at    = get_option( 'rwf_integration_health_last_test', '' );

		include RWF_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Handle item create/update.
	 */
	public static function handle_save_item() {
		check_admin_referer( 'rwf_save_item' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( $post_id && ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this item.', 'reactwoo-flow' ) );
		}

		if ( ! $post_id && ! RWF_Capabilities::can_edit_items() ) {
			wp_die( esc_html__( 'You do not have permission to create items.', 'reactwoo-flow' ) );
		}

		$title       = isset( $_POST['rwf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['rwf_title'] ) ) : '';
		$description = isset( $_POST['rwf_description'] ) ? wp_kses_post( wp_unslash( $_POST['rwf_description'] ) ) : '';

		if ( '' === $title ) {
			$title = __( 'Untitled Flow Item', 'reactwoo-flow' );
		}

		$post_data = array(
			'post_type'    => RWF_CPT::POST_TYPE,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => 'publish',
		);

		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$post_id = absint( $result );
		self::save_item_meta_from_request( $post_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_ITEM,
					'id'      => $post_id,
					'message' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle workflow status transitions from item detail pages.
	 */
	public static function handle_transition_status() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		check_admin_referer( 'rwf_transition_status_' . $post_id );

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to transition this item.', 'reactwoo-flow' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
		$note       = isset( $_POST['transition_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['transition_note'] ) ) : '';
		$result     = RWF_CPT::transition_status( $post_id, $new_status, $note );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => self::PAGE_ITEM,
						'id'      => $post_id,
						'message' => 'status_error',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_ITEM,
					'id'      => $post_id,
					'message' => 'status_updated',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle inbox bulk actions.
	 */
	public static function handle_bulk_items() {
		check_admin_referer( 'rwf_bulk_items' );

		if ( ! RWF_Capabilities::can_edit_items() ) {
			wp_die( esc_html__( 'You do not have permission to update items.', 'reactwoo-flow' ) );
		}

		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$item_ids    = isset( $_POST['item_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['item_ids'] ) ) : array();
		$count       = 0;
		$errors      = 0;

		foreach ( $item_ids as $item_id ) {
			if ( ! $item_id || ! RWF_Capabilities::can_edit_item( $item_id ) ) {
				continue;
			}

			if ( 'analyse' === $bulk_action ) {
				$result = RWF_AI::analyse_and_save( $item_id );
				if ( is_wp_error( $result ) ) {
					$errors++;
				} else {
					$count++;
				}
			} elseif ( 'change_status' === $bulk_action ) {
				$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
				$statuses   = RWF_CPT::get_statuses();
				if ( isset( $statuses[ $new_status ] ) ) {
					$result = RWF_CPT::transition_status( $item_id, $new_status, __( 'Bulk status update from inbox.', 'reactwoo-flow' ) );
					if ( is_wp_error( $result ) ) {
						$errors++;
					} else {
						$count++;
					}
				}
			} elseif ( 'sync_jira' === $bulk_action ) {
				if ( '' === RWF_CPT::get_meta( $item_id, 'jira_id' ) ) {
					$errors++;
					continue;
				}
				$result = RWF_Integration_Jira::sync_issue_status( $item_id );
				if ( is_wp_error( $result ) ) {
					$errors++;
				} else {
					$count++;
				}
			} elseif ( 'archive' === $bulk_action ) {
				wp_trash_post( $item_id );
				$count++;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_INBOX,
					'message' => $errors ? 'bulk_partial' : 'bulk_updated',
					'count'   => $count,
					'errors'  => $errors,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Download a generated specification as Markdown.
	 */
	public static function handle_export_specification() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export this specification.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_export_specification_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$markdown = RWF_CPT::get_meta( $post_id, 'specification_markdown' );
		if ( '' === trim( $markdown ) ) {
			wp_die( esc_html__( 'This item does not have a specification to export.', 'reactwoo-flow' ) );
		}

		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-specification.md' );

		nocache_headers();
		header( 'Content-Type: text/markdown; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download generated release notes as Markdown.
	 */
	public static function handle_export_release_notes() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export these release notes.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_export_release_notes_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$markdown = RWF_CPT::get_meta( $post_id, 'release_notes_markdown' );
		if ( '' === trim( $markdown ) ) {
			wp_die( esc_html__( 'This item does not have release notes to export.', 'reactwoo-flow' ) );
		}

		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-release-notes.md' );

		nocache_headers();
		header( 'Content-Type: text/markdown; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download a generated QA review as Markdown.
	 */
	public static function handle_export_qa_review() {
		self::export_markdown_field(
			'qa_review_markdown',
			'qa_review',
			__( 'You do not have permission to export this QA review.', 'reactwoo-flow' ),
			__( 'This item does not have a QA review to export.', 'reactwoo-flow' ),
			'rwf_export_qa_review_'
		);
	}

	/**
	 * Download a generated UX review as Markdown.
	 */
	public static function handle_export_ux_review() {
		self::export_markdown_field(
			'ux_review_markdown',
			'ux_review',
			__( 'You do not have permission to export this UX review.', 'reactwoo-flow' ),
			__( 'This item does not have a UX review to export.', 'reactwoo-flow' ),
			'rwf_export_ux_review_'
		);
	}

	/**
	 * Run integration connectivity tests.
	 */
	public static function handle_test_integrations() {
		if ( ! RWF_Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to test integrations.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_test_integrations' );

		RWF_Integrations::test_connections();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SETTINGS,
					'message' => 'integrations_tested',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Shared Markdown export handler.
	 *
	 * @param string $meta_key      Meta field containing Markdown.
	 * @param string $suffix        Filename suffix.
	 * @param string $perm_message    Permission error message.
	 * @param string $empty_message   Empty content error message.
	 * @param string $nonce_prefix    Nonce action prefix.
	 */
	private static function export_markdown_field( $meta_key, $suffix, $perm_message, $empty_message, $nonce_prefix ) {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html( $perm_message ) );
		}

		check_admin_referer( $nonce_prefix . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$markdown = RWF_CPT::get_meta( $post_id, $meta_key );
		if ( '' === trim( $markdown ) ) {
			wp_die( esc_html( $empty_message ) );
		}

		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-' . $suffix . '.md' );

		nocache_headers();
		header( 'Content-Type: text/markdown; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );

		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download a prepared development handoff package as JSON.
	 */
	public static function handle_export_development_handoff() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export this development handoff.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_export_development_handoff_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$handoff = RWF_CPT::get_meta( $post_id, 'development_agent_execution' );
		if ( '' === trim( $handoff ) ) {
			wp_die( esc_html__( 'This item does not have a development handoff to export.', 'reactwoo-flow' ) );
		}

		$decoded = json_decode( $handoff, true );
		if ( is_array( $decoded ) ) {
			$handoff = wp_json_encode( $decoded, JSON_PRETTY_PRINT );
		}

		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-cursor-handoff.json' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $handoff ) );

		echo $handoff; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download historical agent runs as JSON.
	 */
	public static function handle_export_agent_runs() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export agent runs for this item.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_export_agent_runs_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$runs = RWF_CPT::get_agent_runs( $post_id );
		if ( empty( $runs ) ) {
			wp_die( esc_html__( 'This item does not have agent runs to export.', 'reactwoo-flow' ) );
		}

		$payload   = wp_json_encode( $runs, JSON_PRETTY_PRINT );
		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-agent-runs.json' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $payload ) );

		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Download the full structured item context package as JSON.
	 */
	public static function handle_export_item_context() {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id || ! RWF_Capabilities::can_edit_item( $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to export this item context.', 'reactwoo-flow' ) );
		}

		check_admin_referer( 'rwf_export_item_context_' . $post_id );

		$post = get_post( $post_id );
		if ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		$payload   = wp_json_encode( RWF_AI::build_cursor_context( $post_id ), JSON_PRETTY_PRINT );
		$slug      = sanitize_title( get_post_field( 'post_name', $post_id ) );
		$file_name = sanitize_file_name( 'rwf-' . $post_id . ( $slug ? '-' . $slug : '' ) . '-context.json' );

		nocache_headers();
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . strlen( $payload ) );

		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Save fields from the item form.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function save_item_meta_from_request( $post_id ) {
		foreach ( RWF_CPT::get_field_groups() as $group ) {
			foreach ( $group['fields'] as $field_key => $definition ) {
				$request_key = 'rwf_' . $field_key;

				if ( isset( $_POST[ $request_key ] ) ) {
					if ( 'status' === $field_key && '' !== RWF_CPT::get_meta( $post_id, 'status' ) ) {
						continue;
					}

					RWF_CPT::update_meta( $post_id, $field_key, wp_unslash( $_POST[ $request_key ] ) );
				}
			}
		}

		if ( '' === RWF_CPT::get_meta( $post_id, 'status' ) ) {
			RWF_CPT::update_meta( $post_id, 'status', 'new' );
		}

		if ( '' === RWF_CPT::get_meta( $post_id, 'priority' ) ) {
			RWF_CPT::update_meta( $post_id, 'priority', 'medium' );
		}
	}

	/**
	 * Render a form field.
	 *
	 * @param string $field_key  Field key.
	 * @param array  $definition Field definition.
	 * @param string $value      Current value.
	 */
	public static function render_field( $field_key, $definition, $value ) {
		$name        = 'rwf_' . $field_key;
		$type        = isset( $definition['type'] ) ? $definition['type'] : 'text';
		$description = isset( $definition['description'] ) ? $definition['description'] : '';
		?>
		<div class="rwf-field rwf-field-<?php echo esc_attr( $type ); ?>">
			<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $definition['label'] ); ?></label>

			<?php if ( 'textarea' === $type ) : ?>
				<textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
			<?php elseif ( 'select' === $type ) : ?>
				<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="">
						<?php
						echo esc_html(
							isset( $definition['empty_label'] )
								? $definition['empty_label']
								: __( 'Select...', 'reactwoo-flow' )
						);
						?>
					</option>
					<?php foreach ( $definition['options'] as $option_value => $label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php endif; ?>

			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get dashboard stats.
	 *
	 * @return array
	 */
	private static function get_dashboard_stats() {
		return array(
			'new'             => self::count_items_by_status( 'new' ),
			'needs_triage'    => self::count_items_by_status( 'needs_triage' ),
			'in_development'  => self::count_items_by_status( 'in_development' ),
			'ready_for_qa'    => self::count_items_by_status( 'ready_for_qa' ),
			'released_month'  => self::count_released_this_month(),
			'total_open'      => self::count_open_items(),
			'ai_analysed'     => self::count_ai_analysed(),
			'awaiting_action' => self::count_items_by_status( 'awaiting_information' ),
		);
	}

	/**
	 * Count items by workflow status.
	 *
	 * @param string $status Status key.
	 * @return int
	 */
	private static function count_items_by_status( $status ) {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'status' ),
						'value' => $status,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count open items.
	 *
	 * @return int
	 */
	private static function count_open_items() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count released items created this month.
	 *
	 * @return int
	 */
	private static function count_released_this_month() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'after'     => gmdate( 'Y-m-01 00:00:00' ),
						'inclusive' => true,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'status' ),
						'value' => 'released',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count items with agent analysis.
	 *
	 * @return int
	 */
	private static function count_ai_analysed() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'ai_analyzed' ),
						'value' => 'yes',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Get current inbox filters.
	 *
	 * @return array
	 */
	private static function get_current_filters() {
		return array(
			's'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'product'   => isset( $_GET['product'] ) ? sanitize_key( wp_unslash( $_GET['product'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'item_type' => isset( $_GET['item_type'] ) ? sanitize_key( wp_unslash( $_GET['item_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'status'    => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
	}

	/**
	 * Build inbox query.
	 *
	 * @return WP_Query
	 */
	private static function get_inbox_query() {
		$filters    = self::get_current_filters();
		$meta_query = array();

		foreach ( array( 'product', 'item_type', 'status' ) as $filter_key ) {
			if ( '' !== $filters[ $filter_key ] ) {
				$meta_query[] = array(
					'key'   => RWF_CPT::meta_key( $filter_key ),
					'value' => $filters[ $filter_key ],
				);
			}
		}

		$args = array(
			'post_type'      => RWF_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			's'              => $filters['s'],
		);

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		return new WP_Query( $args );
	}
}
