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
	'openai'     => __( 'OpenAI', 'reactwoo-flow' ),
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

				<?php if ( 'openai' === $section_key ) : ?>
					<p class="description"><?php esc_html_e( 'Used by the Phase 1 AI triage button. The API key is stored in WordPress options.', 'reactwoo-flow' ); ?></p>
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
									<input
										id="<?php echo esc_attr( $option_key ); ?>"
										name="<?php echo esc_attr( $option_key ); ?>"
										type="<?php echo esc_attr( $definition['type'] ); ?>"
										class="regular-text"
										value="<?php echo esc_attr( RWF_Settings::get( $option_key ) ); ?>"
										autocomplete="<?php echo 'password' === $definition['type'] ? 'off' : 'on'; ?>"
									/>
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
