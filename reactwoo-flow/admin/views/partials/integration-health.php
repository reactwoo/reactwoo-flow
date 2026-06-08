<?php
/**
 * Integration configuration and connectivity summary.
 *
 * Expects $integration_summary and optional $integration_test_results, $integration_tested_at, $show_test_button.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_test_button = ! empty( $show_test_button );
?>

<div class="rwf-panel rwf-integration-health">
	<h2><?php esc_html_e( 'Integration Health', 'reactwoo-flow' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configuration status for external systems. Run a connectivity test from Settings after saving credentials.', 'reactwoo-flow' ); ?>
	</p>

	<table class="widefat striped rwf-integration-health-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Integration', 'reactwoo-flow' ); ?></th>
				<th><?php esc_html_e( 'Configured', 'reactwoo-flow' ); ?></th>
				<th><?php esc_html_e( 'Last Test', 'reactwoo-flow' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $integration_summary as $key => $entry ) : ?>
				<?php
				$test = isset( $integration_test_results[ $key ] ) ? $integration_test_results[ $key ] : null;
				?>
				<tr>
					<td><?php echo esc_html( $entry['label'] ); ?></td>
					<td>
						<?php if ( ! empty( $entry['configured'] ) ) : ?>
							<span class="rwf-status-success"><?php esc_html_e( 'Yes', 'reactwoo-flow' ); ?></span>
						<?php else : ?>
							<span class="rwf-status-muted"><?php esc_html_e( 'No', 'reactwoo-flow' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( empty( $entry['configured'] ) ) : ?>
							<span class="description"><?php esc_html_e( 'Not configured', 'reactwoo-flow' ); ?></span>
						<?php elseif ( is_array( $test ) ) : ?>
							<?php if ( ! empty( $test['ok'] ) ) : ?>
								<span class="rwf-status-success"><?php echo esc_html( $test['message'] ); ?></span>
							<?php else : ?>
								<span class="rwf-status-error"><?php echo esc_html( isset( $test['message'] ) ? $test['message'] : '' ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<span class="description"><?php esc_html_e( 'Not tested yet', 'reactwoo-flow' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $integration_tested_at ) ) : ?>
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: datetime of last connectivity test. */
					__( 'Last connectivity test: %s', 'reactwoo-flow' ),
					$integration_tested_at
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $show_test_button && RWF_Capabilities::can_manage() ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rwf-integration-test-form">
			<?php wp_nonce_field( 'rwf_test_integrations' ); ?>
			<input type="hidden" name="action" value="rwf_test_integrations" />
			<button type="submit" class="button button-secondary">
				<?php esc_html_e( 'Test Connections', 'reactwoo-flow' ); ?>
			</button>
		</form>
	<?php elseif ( ! $show_test_button ) : ?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_SETTINGS ) ); ?>">
				<?php esc_html_e( 'Configure integrations in Settings', 'reactwoo-flow' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
