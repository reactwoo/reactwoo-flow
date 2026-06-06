<?php
/**
 * Settings view.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sections = array(
	'agents'     => __( 'Agent Orchestration', 'reactwoo-flow' ),
	'providers'  => __( 'Provider Connections', 'reactwoo-flow' ),
	'jira'       => __( 'Jira (future)', 'reactwoo-flow' ),
	'confluence' => __( 'Confluence (future)', 'reactwoo-flow' ),
	'github'     => __( 'GitHub (future)', 'reactwoo-flow' ),
);
?>

<div class="wrap rwf-wrap">
	<h1><?php esc_html_e( 'ReactWoo Flow Settings', 'reactwoo-flow' ); ?></h1>

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
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Stored now as planning metadata. Integration logic will be added in a future phase.', 'reactwoo-flow' ); ?></p>
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
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>

		<?php submit_button(); ?>
	</form>
</div>
