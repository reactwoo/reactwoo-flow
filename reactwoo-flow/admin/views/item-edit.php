<?php
/**
 * Item edit view.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_existing = $post && $post_id;
$message     = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$title       = $is_existing ? get_the_title( $post ) : '';
$description = $is_existing ? $post->post_content : '';
$agent_runs  = $is_existing ? RWF_CPT::get_agent_runs( $post_id ) : array();
$current_status = $is_existing ? RWF_CPT::get_meta( $post_id, 'status' ) : 'new';
$current_status = $current_status ? $current_status : 'new';
$status_options = RWF_CPT::get_statuses();
$status_transitions = $is_existing ? RWF_CPT::get_available_status_transitions( $current_status ) : array();
$status_history = $is_existing ? RWF_CPT::get_status_history( $post_id ) : array();
?>

<div class="wrap rwf-wrap">
	<h1><?php echo $is_existing ? esc_html__( 'Edit ReactWoo Flow Item', 'reactwoo-flow' ) : esc_html__( 'Add ReactWoo Flow Item', 'reactwoo-flow' ); ?></h1>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Item saved.', 'reactwoo-flow' ); ?></p>
		</div>
	<?php elseif ( 'status_updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Workflow status updated.', 'reactwoo-flow' ); ?></p>
		</div>
	<?php elseif ( 'status_error' === $message ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Workflow status could not be updated from the current state.', 'reactwoo-flow' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="rwf-actions-bar">
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_INBOX ) ); ?>">
			<?php esc_html_e( 'Back to Inbox', 'reactwoo-flow' ); ?>
		</a>

		<?php if ( $is_existing ) : ?>
			<button
				type="button"
				class="button button-secondary rwf-analyse-button"
				data-item-id="<?php echo esc_attr( $post_id ); ?>"
			>
				<?php esc_html_e( 'Run Triage Agent', 'reactwoo-flow' ); ?>
			</button>
			<button
				type="button"
				class="button button-secondary rwf-generate-spec-button"
				data-item-id="<?php echo esc_attr( $post_id ); ?>"
			>
				<?php esc_html_e( 'Generate Specification', 'reactwoo-flow' ); ?>
			</button>
			<button
				type="button"
				class="button button-secondary rwf-prepare-handoff-button"
				data-item-id="<?php echo esc_attr( $post_id ); ?>"
			>
				<?php esc_html_e( 'Prepare Cursor Handoff', 'reactwoo-flow' ); ?>
			</button>
			<?php if ( RWF_CPT::is_specification_generated( $post_id ) ) : ?>
				<a
					class="button"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rwf_export_specification&post_id=' . $post_id ), 'rwf_export_specification_' . $post_id ) ); ?>"
				>
					<?php esc_html_e( 'Export Markdown', 'reactwoo-flow' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( RWF_CPT::is_development_handoff_prepared( $post_id ) ) : ?>
				<a
					class="button"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rwf_export_development_handoff&post_id=' . $post_id ), 'rwf_export_development_handoff_' . $post_id ) ); ?>"
				>
					<?php esc_html_e( 'Export Handoff JSON', 'reactwoo-flow' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $agent_runs ) ) : ?>
				<a
					class="button"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rwf_export_agent_runs&post_id=' . $post_id ), 'rwf_export_agent_runs_' . $post_id ) ); ?>"
				>
					<?php esc_html_e( 'Export Agent Runs', 'reactwoo-flow' ); ?>
				</a>
			<?php endif; ?>
			<span class="rwf-analysis-status" aria-live="polite"></span>
		<?php endif; ?>
	</div>

	<?php if ( $is_existing && RWF_CPT::is_ai_analyzed( $post_id ) ) : ?>
		<div class="rwf-ai-banner">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: analysis date. */
					__( 'Agent analysis saved %s.', 'reactwoo-flow' ),
					RWF_CPT::get_meta( $post_id, 'ai_analyzed_at' )
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $is_existing && RWF_CPT::is_development_handoff_prepared( $post_id ) ) : ?>
		<div class="rwf-handoff-banner">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: handoff preparation date. */
					__( 'Cursor development handoff prepared %s.', 'reactwoo-flow' ),
					RWF_CPT::get_meta( $post_id, 'development_handoff_prepared_at' )
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $is_existing && RWF_CPT::is_specification_generated( $post_id ) ) : ?>
		<div class="rwf-spec-banner">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: specification generation date. */
					__( 'Specification generated %s.', 'reactwoo-flow' ),
					RWF_CPT::get_meta( $post_id, 'specification_generated_at' )
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $is_existing ) : ?>
		<div class="rwf-panel rwf-workflow-panel">
			<h2><?php esc_html_e( 'Workflow Orchestration', 'reactwoo-flow' ); ?></h2>
			<p>
				<strong><?php esc_html_e( 'Current Status:', 'reactwoo-flow' ); ?></strong>
				<span class="rwf-pill"><?php echo esc_html( RWF_CPT::option_label( $status_options, $current_status ) ); ?></span>
				<?php if ( RWF_CPT::get_meta( $post_id, 'status_changed_at' ) ) : ?>
					<span class="description">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: status change date. */
								__( 'Last changed %s.', 'reactwoo-flow' ),
								RWF_CPT::get_meta( $post_id, 'status_changed_at' )
							)
						);
						?>
					</span>
				<?php endif; ?>
			</p>

			<?php if ( ! empty( $status_transitions ) ) : ?>
				<form class="rwf-transition-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'rwf_transition_status_' . $post_id ); ?>
					<input type="hidden" name="action" value="rwf_transition_status" />
					<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />

					<label for="rwf-new-status"><?php esc_html_e( 'Move to', 'reactwoo-flow' ); ?></label>
					<select id="rwf-new-status" name="new_status">
						<?php foreach ( $status_transitions as $status_key => $status_label ) : ?>
							<option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option>
						<?php endforeach; ?>
					</select>

					<label for="rwf-transition-note" class="screen-reader-text"><?php esc_html_e( 'Transition note', 'reactwoo-flow' ); ?></label>
					<input id="rwf-transition-note" name="transition_note" type="text" placeholder="<?php esc_attr_e( 'Optional transition note', 'reactwoo-flow' ); ?>" />
					<button class="button" type="submit"><?php esc_html_e( 'Update Workflow', 'reactwoo-flow' ); ?></button>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No workflow transitions are currently available from this status.', 'reactwoo-flow' ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $status_history ) ) : ?>
				<h3><?php esc_html_e( 'Recent Status History', 'reactwoo-flow' ); ?></h3>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Recorded', 'reactwoo-flow' ); ?></th>
							<th><?php esc_html_e( 'From', 'reactwoo-flow' ); ?></th>
							<th><?php esc_html_e( 'To', 'reactwoo-flow' ); ?></th>
							<th><?php esc_html_e( 'User', 'reactwoo-flow' ); ?></th>
							<th><?php esc_html_e( 'Note', 'reactwoo-flow' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( array_reverse( $status_history ), 0, 8 ) as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( isset( $entry['recorded_at'] ) ? $entry['recorded_at'] : '' ); ?></td>
								<td><?php echo esc_html( RWF_CPT::option_label( $status_options, isset( $entry['from'] ) ? $entry['from'] : '' ) ); ?></td>
								<td><?php echo esc_html( RWF_CPT::option_label( $status_options, isset( $entry['to'] ) ? $entry['to'] : '' ) ); ?></td>
								<td><?php echo esc_html( isset( $entry['user_name'] ) ? $entry['user_name'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $entry['note'] ) ? $entry['note'] : '' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $agent_runs ) ) : ?>
		<div class="rwf-panel rwf-agent-history">
			<h2><?php esc_html_e( 'Agent Run History', 'reactwoo-flow' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Recent orchestration attempts for this item. Full context and output are available from the export.', 'reactwoo-flow' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Recorded', 'reactwoo-flow' ); ?></th>
						<th><?php esc_html_e( 'Scope', 'reactwoo-flow' ); ?></th>
						<th><?php esc_html_e( 'Agent', 'reactwoo-flow' ); ?></th>
						<th><?php esc_html_e( 'Provider', 'reactwoo-flow' ); ?></th>
						<th><?php esc_html_e( 'Model', 'reactwoo-flow' ); ?></th>
						<th><?php esc_html_e( 'Status', 'reactwoo-flow' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( array_slice( array_reverse( $agent_runs ), 0, 10 ) as $run ) : ?>
						<tr>
							<td><?php echo esc_html( isset( $run['recorded_at'] ) ? $run['recorded_at'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $run['scope'] ) ? $run['scope'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $run['name'] ) ? $run['name'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $run['provider'] ) ? $run['provider'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $run['model'] ) ? $run['model'] : '' ); ?></td>
							<td><span class="rwf-pill"><?php echo esc_html( isset( $run['status'] ) ? $run['status'] : '' ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<form class="rwf-item-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'rwf_save_item' ); ?>
		<input type="hidden" name="action" value="rwf_save_item" />
		<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />

		<div class="rwf-panel">
			<h2><?php esc_html_e( 'Basic Details', 'reactwoo-flow' ); ?></h2>

			<div class="rwf-field">
				<label for="rwf_title"><?php esc_html_e( 'Title', 'reactwoo-flow' ); ?></label>
				<input id="rwf_title" name="rwf_title" type="text" value="<?php echo esc_attr( $title ); ?>" required />
			</div>

			<div class="rwf-field">
				<label for="rwf_description"><?php esc_html_e( 'Description', 'reactwoo-flow' ); ?></label>
				<textarea id="rwf_description" name="rwf_description" rows="8" required><?php echo esc_textarea( $description ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Paste the original idea, customer request, support message, or bug report here.', 'reactwoo-flow' ); ?></p>
			</div>
		</div>

		<?php foreach ( RWF_CPT::get_field_groups() as $group_key => $group ) : ?>
			<div class="rwf-panel rwf-panel-<?php echo esc_attr( $group_key ); ?>">
				<h2><?php echo esc_html( $group['title'] ); ?></h2>

				<?php if ( 'integrations' === $group_key ) : ?>
					<p class="description"><?php esc_html_e( 'These fields are placeholders for future Jira, GitHub, and release management phases.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<?php if ( 'ai_analysis' === $group_key && ! $is_existing ) : ?>
					<p class="description"><?php esc_html_e( 'Save the item before running agent analysis.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<?php if ( 'agent_execution' === $group_key ) : ?>
					<p class="description"><?php esc_html_e( 'ReactWoo Flow stores orchestration metadata for each agent run: agent type, provider, model, prompt template, context payload, output, and execution status.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<?php if ( 'specification' === $group_key && ! $is_existing ) : ?>
					<p class="description"><?php esc_html_e( 'Save the item before generating a specification.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<div class="rwf-field-grid">
					<?php foreach ( $group['fields'] as $field_key => $definition ) : ?>
						<?php
						if ( $is_existing && 'status' === $field_key ) {
							continue;
						}

						$value = $is_existing ? RWF_CPT::get_meta( $post_id, $field_key ) : '';

						if ( ! $is_existing && 'status' === $field_key ) {
							$value = 'new';
						}

						if ( ! $is_existing && 'priority' === $field_key ) {
							$value = 'medium';
						}

						RWF_Admin::render_field( $field_key, $definition, $value );
						?>

						<?php if ( in_array( $field_key, array( 'screenshots', 'log_files' ), true ) ) : ?>
							<button class="button rwf-media-button" type="button" data-rwf-media-target="rwf_<?php echo esc_attr( $field_key ); ?>">
								<?php esc_html_e( 'Add Media URL', 'reactwoo-flow' ); ?>
							</button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<p class="submit">
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Save Item', 'reactwoo-flow' ); ?></button>
		</p>
	</form>
</div>
