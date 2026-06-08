<?php
/**
 * Dashboard view.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap rwf-wrap">
	<h1><?php esc_html_e( 'ReactWoo Flow Dashboard', 'reactwoo-flow' ); ?></h1>

	<div class="rwf-actions-bar">
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_ITEM ) ); ?>">
			<?php esc_html_e( 'Add Flow Item', 'reactwoo-flow' ); ?>
		</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_INBOX ) ); ?>">
			<?php esc_html_e( 'Open Inbox', 'reactwoo-flow' ); ?>
		</a>
	</div>

	<div class="rwf-stat-grid">
		<div class="rwf-stat-card">
			<span class="rwf-stat-number"><?php echo esc_html( $stats['new'] ); ?></span>
			<span class="rwf-stat-label"><?php esc_html_e( 'New Items', 'reactwoo-flow' ); ?></span>
		</div>
		<div class="rwf-stat-card">
			<span class="rwf-stat-number"><?php echo esc_html( $stats['needs_triage'] ); ?></span>
			<span class="rwf-stat-label"><?php esc_html_e( 'Awaiting Triage', 'reactwoo-flow' ); ?></span>
		</div>
		<div class="rwf-stat-card">
			<span class="rwf-stat-number"><?php echo esc_html( $stats['in_development'] ); ?></span>
			<span class="rwf-stat-label"><?php esc_html_e( 'In Development', 'reactwoo-flow' ); ?></span>
		</div>
		<div class="rwf-stat-card">
			<span class="rwf-stat-number"><?php echo esc_html( $stats['ready_for_qa'] ); ?></span>
			<span class="rwf-stat-label"><?php esc_html_e( 'Ready for QA', 'reactwoo-flow' ); ?></span>
		</div>
		<div class="rwf-stat-card">
			<span class="rwf-stat-number"><?php echo esc_html( $stats['released_month'] ); ?></span>
			<span class="rwf-stat-label"><?php esc_html_e( 'Released This Month', 'reactwoo-flow' ); ?></span>
		</div>
	</div>

	<?php
	$show_test_button = false;
	include RWF_PLUGIN_DIR . 'admin/views/partials/integration-health.php';
	?>

	<div class="rwf-panel">
		<h2><?php esc_html_e( 'Platform Overview', 'reactwoo-flow' ); ?></h2>
		<p><?php esc_html_e( 'ReactWoo Flow orchestrates intake, AI triage, specifications, release notes, QA and UX reviews, and handoff to Cursor. Jira, GitHub, and Confluence integrations sync delivery metadata while WordPress remains the source of workflow context.', 'reactwoo-flow' ); ?></p>
		<ul class="rwf-check-list">
			<li><?php esc_html_e( 'Planning, release, QA, and UX agents with per-item overrides', 'reactwoo-flow' ); ?></li>
			<li><?php esc_html_e( 'Jira issue creation and status sync', 'reactwoo-flow' ); ?></li>
			<li><?php esc_html_e( 'GitHub PR metadata and CI status on linked items', 'reactwoo-flow' ); ?></li>
			<li><?php esc_html_e( 'Confluence specification publishing and Cursor MCP handoff', 'reactwoo-flow' ); ?></li>
		</ul>
	</div>
</div>
