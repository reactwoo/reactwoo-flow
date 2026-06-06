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

	<div class="rwf-panel">
		<h2><?php esc_html_e( 'MVP Scope', 'reactwoo-flow' ); ?></h2>
		<p><?php esc_html_e( 'ReactWoo Flow currently owns product intake, support desk capture, structured triage fields, and AI analysis. Jira, GitHub, Confluence, Cursor, release notes, QA, and UX integrations are recorded as future placeholders only.', 'reactwoo-flow' ); ?></p>
		<ul class="rwf-check-list">
			<li><?php esc_html_e( 'Single rwf_item custom post type', 'reactwoo-flow' ); ?></li>
			<li><?php esc_html_e( 'Product, type, priority, status, reporter, and environment fields', 'reactwoo-flow' ); ?></li>
			<li><?php esc_html_e( 'OpenAI-powered triage output saved against each item', 'reactwoo-flow' ); ?></li>
		</ul>
	</div>
</div>
