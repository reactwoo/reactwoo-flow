<?php
/**
 * Inbox view.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$count   = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$errors  = isset( $_GET['errors'] ) ? absint( $_GET['errors'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="wrap rwf-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'ReactWoo Flow Inbox', 'reactwoo-flow' ); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_ITEM ) ); ?>">
		<?php esc_html_e( 'Add New', 'reactwoo-flow' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( 'bulk_updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( sprintf( _n( '%d item updated.', '%d items updated.', $count, 'reactwoo-flow' ), $count ) ); ?></p>
		</div>
	<?php elseif ( 'bulk_partial' === $message ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: updated count, 2: error count. */
						__( '%1$d items updated. %2$d requests failed.', 'reactwoo-flow' ),
						$count,
						$errors
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<form class="rwf-filter-form" method="get">
		<input type="hidden" name="page" value="<?php echo esc_attr( RWF_Admin::PAGE_INBOX ); ?>" />
		<p class="search-box">
			<label class="screen-reader-text" for="rwf-search"><?php esc_html_e( 'Search Items', 'reactwoo-flow' ); ?></label>
			<input id="rwf-search" type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" />
			<button class="button" type="submit"><?php esc_html_e( 'Search', 'reactwoo-flow' ); ?></button>
		</p>

		<div class="rwf-filters">
			<select name="product">
				<option value=""><?php esc_html_e( 'All Products', 'reactwoo-flow' ); ?></option>
				<?php foreach ( RWF_CPT::get_products() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['product'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="item_type">
				<option value=""><?php esc_html_e( 'All Types', 'reactwoo-flow' ); ?></option>
				<?php foreach ( RWF_CPT::get_item_types() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['item_type'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="status">
				<option value=""><?php esc_html_e( 'All Statuses', 'reactwoo-flow' ); ?></option>
				<?php foreach ( RWF_CPT::get_statuses() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['status'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button class="button" type="submit"><?php esc_html_e( 'Filter', 'reactwoo-flow' ); ?></button>
		</div>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'rwf_bulk_items' ); ?>
		<input type="hidden" name="action" value="rwf_bulk_items" />

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label class="screen-reader-text" for="rwf-bulk-action"><?php esc_html_e( 'Bulk action', 'reactwoo-flow' ); ?></label>
				<select id="rwf-bulk-action" name="bulk_action">
					<option value=""><?php esc_html_e( 'Bulk actions', 'reactwoo-flow' ); ?></option>
					<option value="analyse"><?php esc_html_e( 'Run Triage Agent', 'reactwoo-flow' ); ?></option>
					<option value="change_status"><?php esc_html_e( 'Change Status', 'reactwoo-flow' ); ?></option>
					<?php if ( RWF_Integration_Jira::is_configured() ) : ?>
						<option value="sync_jira"><?php esc_html_e( 'Sync Jira Status', 'reactwoo-flow' ); ?></option>
					<?php endif; ?>
					<option value="archive"><?php esc_html_e( 'Archive', 'reactwoo-flow' ); ?></option>
				</select>

				<select name="new_status">
					<?php foreach ( RWF_CPT::get_statuses() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<button class="button action" type="submit"><?php esc_html_e( 'Apply', 'reactwoo-flow' ); ?></button>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped table-view-list rwf-inbox-table">
			<thead>
				<tr>
					<td class="manage-column column-cb check-column">
						<input type="checkbox" class="rwf-select-all" />
					</td>
					<th><?php esc_html_e( 'ID', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Title', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Product', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Type', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Priority', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Status', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Agent Analysed', 'reactwoo-flow' ); ?></th>
					<th><?php esc_html_e( 'Created Date', 'reactwoo-flow' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $query->have_posts() ) : ?>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$item_id  = get_the_ID();
						$product  = RWF_CPT::get_meta( $item_id, 'product' );
						$type     = RWF_CPT::get_meta( $item_id, 'item_type' );
						$priority = RWF_CPT::get_meta( $item_id, 'priority' );
						$status   = RWF_CPT::get_meta( $item_id, 'status' );
						?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="item_ids[]" value="<?php echo esc_attr( $item_id ); ?>" />
							</th>
							<td><?php echo esc_html( $item_id ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . RWF_Admin::PAGE_ITEM . '&id=' . $item_id ) ); ?>">
									<strong><?php the_title(); ?></strong>
								</a>
							</td>
							<td><?php echo esc_html( RWF_CPT::option_label( RWF_CPT::get_products(), $product ) ); ?></td>
							<td><?php echo esc_html( RWF_CPT::option_label( RWF_CPT::get_item_types(), $type ) ); ?></td>
							<td><span class="rwf-pill rwf-pill-priority"><?php echo esc_html( RWF_CPT::option_label( RWF_CPT::get_priorities(), $priority ) ); ?></span></td>
							<td><span class="rwf-pill"><?php echo esc_html( RWF_CPT::option_label( RWF_CPT::get_statuses(), $status ) ); ?></span></td>
							<td>
								<?php if ( RWF_CPT::is_ai_analyzed( $item_id ) ) : ?>
									<span class="rwf-ai-yes"><?php esc_html_e( 'Yes', 'reactwoo-flow' ); ?></span>
								<?php else : ?>
									<span class="rwf-ai-no"><?php esc_html_e( 'No', 'reactwoo-flow' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( get_the_date() ); ?></td>
						</tr>
					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>
				<?php else : ?>
					<tr>
						<td colspan="9"><?php esc_html_e( 'No ReactWoo Flow items found.', 'reactwoo-flow' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>
