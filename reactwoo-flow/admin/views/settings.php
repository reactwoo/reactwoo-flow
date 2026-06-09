<?php
/**
 * Settings view.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$sections = array(
	'agents'     => __( 'Agent Orchestration', 'reactwoo-flow' ),
	'providers'  => __( 'Provider Connections', 'reactwoo-flow' ),
	'intake'     => __( 'Intake', 'reactwoo-flow' ),
	'jira'       => __( 'Jira', 'reactwoo-flow' ),
	'confluence' => __( 'Confluence', 'reactwoo-flow' ),
	'github'     => __( 'GitHub', 'reactwoo-flow' ),
	'automation' => __( 'Workflow Automation', 'reactwoo-flow' ),
);
?>

<div class="wrap rwf-wrap">
	<h1><?php esc_html_e( 'ReactWoo Flow Settings', 'reactwoo-flow' ); ?></h1>

	<?php if ( 'integrations_tested' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Integration connectivity test completed. Review the results below.', 'reactwoo-flow' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
	$show_test_button = true;
	include RWF_PLUGIN_DIR . 'admin/views/partials/integration-health.php';
	?>

	<form method="post" action="options.php">
		<?php settings_fields( RWF_Settings::OPTION_GROUP ); ?>

		<?php foreach ( $sections as $section_key => $section_label ) : ?>
			<div class="rwf-panel">
				<h2><?php echo esc_html( $section_label ); ?></h2>

				<?php if ( 'agents' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'ReactWoo Flow routes each workflow through an agent type, provider, model, prompt template, context payload, output, and execution status.', 'reactwoo-flow' ); ?></p>
					<table class="widefat striped rwf-agent-catalog">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Agent Type', 'reactwoo-flow' ); ?></th>
								<th><?php esc_html_e( 'Preferred Engine', 'reactwoo-flow' ); ?></th>
								<th><?php esc_html_e( 'Purpose', 'reactwoo-flow' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( RWF_Agent::get_agent_types() as $agent_type ) : ?>
								<tr>
									<td><?php echo esc_html( $agent_type['label'] ); ?></td>
									<td><?php echo esc_html( $agent_type['preferred'] ); ?></td>
									<td><?php echo esc_html( $agent_type['purpose'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php elseif ( 'providers' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Provider credentials are execution engines only. ReactWoo Flow owns orchestration and context; Cursor is the preferred future development executor through MCP.', 'reactwoo-flow' ); ?></p>
				<?php elseif ( 'intake' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Configure website/support intake behavior before items enter triage.', 'reactwoo-flow' ); ?></p>
				<?php elseif ( 'jira' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Jira Cloud credentials for creating issues from triaged items.', 'reactwoo-flow' ); ?></p>
				<?php elseif ( 'confluence' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Uses the same Atlassian email and API token as Jira. Specifications publish to the configured space.', 'reactwoo-flow' ); ?></p>
				<?php elseif ( 'github' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'One personal access token authenticates API calls to every mapped repository. Each satellite product can point at its own GitHub repo.', 'reactwoo-flow' ); ?></p>
					<div class="notice notice-info inline" style="margin: 12px 0 16px;">
						<p>
							<strong><?php esc_html_e( 'GitHub webhook callback URL', 'reactwoo-flow' ); ?></strong>
						</p>
						<p>
							<input
								type="text"
								class="large-text code"
								readonly
								onfocus="this.select();"
								value="<?php echo esc_attr( RWF_Integration_GitHub::get_webhook_url() ); ?>"
							/>
						</p>
						<p class="description">
							<?php esc_html_e( 'Add this URL to each mapped repository webhook in GitHub (pull_request and status events). Use the per-product webhook secret from the map below in each repo’s GitHub webhook settings.', 'reactwoo-flow' ); ?>
						</p>
						<?php
						$webhook_received_at = get_option( 'rwf_github_webhook_last_received_at', '' );
						if ( '' !== $webhook_received_at ) :
							?>
							<p class="description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: datetime of last webhook. */
										__( 'Last webhook received: %s', 'reactwoo-flow' ),
										$webhook_received_at
									)
								);
								?>
							</p>
						<?php endif; ?>
					</div>
				<?php elseif ( 'automation' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Optional workflow shortcuts after triage or specification generation. Integrations must be configured first.', 'reactwoo-flow' ); ?></p>
				<?php endif; ?>

				<table class="form-table" role="presentation">
					<tbody>
						<?php foreach ( RWF_Settings::get_settings_schema() as $option_key => $definition ) : ?>
							<?php if ( $section_key !== $definition['section'] ) : ?>
								<?php continue; ?>
							<?php endif; ?>

							<tr>
								<th scope="row">
									<label for="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $definition['label'] ); ?></label>
								</th>
								<td>
									<?php if ( 'select' === $definition['type'] ) : ?>
										<select id="<?php echo esc_attr( $option_key ); ?>" name="<?php echo esc_attr( $option_key ); ?>">
											<?php foreach ( $definition['options'] as $value => $label ) : ?>
												<option value="<?php echo esc_attr( $value ); ?>" <?php selected( RWF_Settings::get( $option_key ), $value ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php elseif ( 'textarea' === $definition['type'] ) : ?>
										<textarea
											id="<?php echo esc_attr( $option_key ); ?>"
											name="<?php echo esc_attr( $option_key ); ?>"
											class="large-text code"
											rows="8"
										><?php echo esc_textarea( RWF_Settings::get( $option_key ) ); ?></textarea>
									<?php else : ?>
										<input
											id="<?php echo esc_attr( $option_key ); ?>"
											name="<?php echo esc_attr( $option_key ); ?>"
											type="<?php echo esc_attr( $definition['type'] ); ?>"
											class="regular-text"
											value="<?php echo esc_attr( RWF_Settings::get( $option_key ) ); ?>"
											autocomplete="<?php echo 'password' === $definition['type'] ? 'off' : 'on'; ?>"
										/>
									<?php endif; ?>
									<?php if ( ! empty( $definition['description'] ) ) : ?>
										<p class="description"><?php echo esc_html( $definition['description'] ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<?php if ( 'github' === $section_key && 'rwf_github_token' === $option_key ) : ?>
								<?php include RWF_PLUGIN_DIR . 'admin/views/partials/settings-github-product-map.php'; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>

		<?php submit_button(); ?>
	</form>
</div>
