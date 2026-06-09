<?php
/**
 * GitHub product-to-repository mapping table.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_map = RWF_Settings::get_github_product_map();
$products    = RWF_CPT::get_products();
$has_token   = '' !== RWF_Settings::get( 'rwf_github_token' );
?>

<tr>
	<th scope="row"><?php esc_html_e( 'Product Repository Map', 'reactwoo-flow' ); ?></th>
	<td>
		<table class="widefat striped rwf-github-product-map" id="rwf-github-product-map">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'GitHub repository', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Webhook secret', 'reactwoo-flow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $products as $slug => $label ) : ?>
					<?php
					$selected_repo = isset( $product_map[ $slug ]['repository'] ) ? (string) $product_map[ $slug ]['repository'] : '';
					$has_secret    = ! empty( $product_map[ $slug ]['webhook_secret'] );
					?>
					<tr data-product-slug="<?php echo esc_attr( $slug ); ?>">
						<td>
							<strong><?php echo esc_html( $label ); ?></strong>
							<br />
							<code><?php echo esc_html( $slug ); ?></code>
						</td>
						<td>
							<select
								class="rwf-github-repo-select"
								name="rwf_github_product_map[<?php echo esc_attr( $slug ); ?>][repository]"
								data-selected="<?php echo esc_attr( $selected_repo ); ?>"
								<?php disabled( ! $has_token ); ?>
							>
								<option value=""><?php esc_html_e( '— Select repository —', 'reactwoo-flow' ); ?></option>
								<?php if ( '' !== $selected_repo ) : ?>
									<option value="<?php echo esc_attr( $selected_repo ); ?>" selected><?php echo esc_html( $selected_repo ); ?></option>
								<?php endif; ?>
							</select>
						</td>
						<td>
							<input
								type="password"
								class="regular-text"
								name="rwf_github_product_map[<?php echo esc_attr( $slug ); ?>][webhook_secret]"
								value=""
								placeholder="<?php echo esc_attr( $has_secret ? __( 'Saved — leave blank to keep', 'reactwoo-flow' ) : __( 'Secret from GitHub webhook', 'reactwoo-flow' ) ); ?>"
								autocomplete="off"
							/>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'Map each inbox product to its satellite GitHub repository. Inbox sync and webhooks use the item product to choose the correct repo.', 'reactwoo-flow' ); ?>
		</p>
		<p id="rwf-github-repos-status" class="description">
			<?php if ( ! $has_token ) : ?>
				<?php esc_html_e( 'Enter and save a GitHub personal access token, then repository choices will load here.', 'reactwoo-flow' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Loading repositories from GitHub…', 'reactwoo-flow' ); ?>
			<?php endif; ?>
		</p>
	</td>
</tr>
