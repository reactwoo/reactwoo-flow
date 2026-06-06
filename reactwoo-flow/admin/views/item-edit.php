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
?>

<div class="wrap rwf-wrap">
	<h1><?php echo $is_existing ? esc_html__( 'Edit ReactWoo Flow Item', 'reactwoo-flow' ) : esc_html__( 'Add ReactWoo Flow Item', 'reactwoo-flow' ); ?></h1>

	<?php if ( 'saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Item saved.', 'reactwoo-flow' ); ?></p>
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
				<?php esc_html_e( 'Analyse with AI', 'reactwoo-flow' ); ?>
			</button>
			<span class="rwf-analysis-status" aria-live="polite"></span>
		<?php endif; ?>
	</div>

	<?php if ( $is_existing && RWF_CPT::is_ai_analyzed( $post_id ) ) : ?>
		<div class="rwf-ai-banner">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: analysis date. */
					__( 'AI analysis saved %s.', 'reactwoo-flow' ),
					RWF_CPT::get_meta( $post_id, 'ai_analyzed_at' )
				)
			);
			?>
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
					<p class="description"><?php esc_html_e( 'Save the item before running AI analysis.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<div class="rwf-field-grid">
					<?php foreach ( $group['fields'] as $field_key => $definition ) : ?>
						<?php
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
