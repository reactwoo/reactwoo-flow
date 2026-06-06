<?php
/**
 * Admin UI and actions.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the ReactWoo Flow admin experience.
 */
class RWF_Admin {
	const PAGE_DASHBOARD = 'rwf-dashboard';
	const PAGE_INBOX = 'rwf-inbox';
	const PAGE_ITEM = 'rwf-item';
	const PAGE_SETTINGS = 'rwf-settings';

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_rwf_save_item', array( __CLASS__, 'handle_save_item' ) );
		add_action( 'admin_post_rwf_bulk_items', array( __CLASS__, 'handle_bulk_items' ) );
	}

	/**
	 * Register admin pages.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'ReactWoo Flow', 'reactwoo-flow' ),
			__( 'ReactWoo Flow', 'reactwoo-flow' ),
			'edit_posts',
			self::PAGE_DASHBOARD,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-networking',
			26
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Dashboard', 'reactwoo-flow' ),
			__( 'Dashboard', 'reactwoo-flow' ),
			'edit_posts',
			self::PAGE_DASHBOARD,
			array( __CLASS__, 'render_dashboard' )
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Inbox', 'reactwoo-flow' ),
			__( 'Inbox', 'reactwoo-flow' ),
			'edit_posts',
			self::PAGE_INBOX,
			array( __CLASS__, 'render_inbox' )
		);

		add_submenu_page(
			null,
			__( 'Flow Item', 'reactwoo-flow' ),
			__( 'Flow Item', 'reactwoo-flow' ),
			'edit_posts',
			self::PAGE_ITEM,
			array( __CLASS__, 'render_item' )
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Settings', 'reactwoo-flow' ),
			__( 'Settings', 'reactwoo-flow' ),
			'manage_options',
			self::PAGE_SETTINGS,
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin hook.
	 */
	public static function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( false === strpos( $hook, 'rwf' ) && false === strpos( $page, 'rwf-' ) ) {
			return;
		}

		wp_enqueue_style(
			'rwf-admin',
			RWF_PLUGIN_URL . 'assets/admin.css',
			array(),
			RWF_VERSION
		);

		wp_enqueue_script(
			'rwf-admin',
			RWF_PLUGIN_URL . 'assets/admin.js',
			array( 'jquery' ),
			RWF_VERSION,
			true
		);

		wp_enqueue_media();

		wp_localize_script(
			'rwf-admin',
			'rwfAdmin',
			array(
				'restUrl'      => esc_url_raw( rest_url( RWF_REST::NAMESPACE ) ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'analysing'    => __( 'Analysing...', 'reactwoo-flow' ),
				'analyseLabel' => __( 'Analyse with AI', 'reactwoo-flow' ),
				'errorLabel'   => __( 'Analysis failed. Check settings and try again.', 'reactwoo-flow' ),
				'doneLabel'    => __( 'Analysis saved. Refreshing...', 'reactwoo-flow' ),
			)
		);
	}

	/**
	 * Render dashboard page.
	 */
	public static function render_dashboard() {
		$stats = self::get_dashboard_stats();

		include RWF_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render inbox page.
	 */
	public static function render_inbox() {
		$query   = self::get_inbox_query();
		$filters = self::get_current_filters();

		include RWF_PLUGIN_DIR . 'admin/views/inbox.php';
	}

	/**
	 * Render item edit/create page.
	 */
	public static function render_item() {
		$post_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( $post_id && ( ! $post || RWF_CPT::POST_TYPE !== $post->post_type ) ) {
			wp_die( esc_html__( 'ReactWoo Flow item not found.', 'reactwoo-flow' ) );
		}

		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this item.', 'reactwoo-flow' ) );
		}

		include RWF_PLUGIN_DIR . 'admin/views/item-edit.php';
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings() {
		include RWF_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Handle item create/update.
	 */
	public static function handle_save_item() {
		check_admin_referer( 'rwf_save_item' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this item.', 'reactwoo-flow' ) );
		}

		if ( ! $post_id && ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to create items.', 'reactwoo-flow' ) );
		}

		$title       = isset( $_POST['rwf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['rwf_title'] ) ) : '';
		$description = isset( $_POST['rwf_description'] ) ? wp_kses_post( wp_unslash( $_POST['rwf_description'] ) ) : '';

		if ( '' === $title ) {
			$title = __( 'Untitled Flow Item', 'reactwoo-flow' );
		}

		$post_data = array(
			'post_type'    => RWF_CPT::POST_TYPE,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => 'publish',
		);

		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$post_id = absint( $result );
		self::save_item_meta_from_request( $post_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_ITEM,
					'id'      => $post_id,
					'message' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle inbox bulk actions.
	 */
	public static function handle_bulk_items() {
		check_admin_referer( 'rwf_bulk_items' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to update items.', 'reactwoo-flow' ) );
		}

		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$item_ids    = isset( $_POST['item_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['item_ids'] ) ) : array();
		$count       = 0;
		$errors      = 0;

		foreach ( $item_ids as $item_id ) {
			if ( ! $item_id || ! current_user_can( 'edit_post', $item_id ) ) {
				continue;
			}

			if ( 'analyse' === $bulk_action ) {
				$result = RWF_AI::analyse_and_save( $item_id );
				if ( is_wp_error( $result ) ) {
					$errors++;
				} else {
					$count++;
				}
			} elseif ( 'change_status' === $bulk_action ) {
				$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
				$statuses   = RWF_CPT::get_statuses();
				if ( isset( $statuses[ $new_status ] ) ) {
					RWF_CPT::update_meta( $item_id, 'status', $new_status );
					$count++;
				}
			} elseif ( 'archive' === $bulk_action ) {
				wp_trash_post( $item_id );
				$count++;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_INBOX,
					'message' => $errors ? 'bulk_partial' : 'bulk_updated',
					'count'   => $count,
					'errors'  => $errors,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Save fields from the item form.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function save_item_meta_from_request( $post_id ) {
		foreach ( RWF_CPT::get_field_groups() as $group ) {
			foreach ( $group['fields'] as $field_key => $definition ) {
				$request_key = 'rwf_' . $field_key;

				if ( isset( $_POST[ $request_key ] ) ) {
					RWF_CPT::update_meta( $post_id, $field_key, wp_unslash( $_POST[ $request_key ] ) );
				}
			}
		}

		if ( '' === RWF_CPT::get_meta( $post_id, 'status' ) ) {
			RWF_CPT::update_meta( $post_id, 'status', 'new' );
		}

		if ( '' === RWF_CPT::get_meta( $post_id, 'priority' ) ) {
			RWF_CPT::update_meta( $post_id, 'priority', 'medium' );
		}
	}

	/**
	 * Render a form field.
	 *
	 * @param string $field_key  Field key.
	 * @param array  $definition Field definition.
	 * @param string $value      Current value.
	 */
	public static function render_field( $field_key, $definition, $value ) {
		$name        = 'rwf_' . $field_key;
		$type        = isset( $definition['type'] ) ? $definition['type'] : 'text';
		$description = isset( $definition['description'] ) ? $definition['description'] : '';
		?>
		<div class="rwf-field rwf-field-<?php echo esc_attr( $type ); ?>">
			<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $definition['label'] ); ?></label>

			<?php if ( 'textarea' === $type ) : ?>
				<textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="5"><?php echo esc_textarea( $value ); ?></textarea>
			<?php elseif ( 'select' === $type ) : ?>
				<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value=""><?php esc_html_e( 'Select...', 'reactwoo-flow' ); ?></option>
					<?php foreach ( $definition['options'] as $option_value => $label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php endif; ?>

			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get dashboard stats.
	 *
	 * @return array
	 */
	private static function get_dashboard_stats() {
		return array(
			'new'             => self::count_items_by_status( 'new' ),
			'needs_triage'    => self::count_items_by_status( 'needs_triage' ),
			'in_development'  => self::count_items_by_status( 'in_development' ),
			'ready_for_qa'    => self::count_items_by_status( 'ready_for_qa' ),
			'released_month'  => self::count_released_this_month(),
			'total_open'      => self::count_open_items(),
			'ai_analysed'     => self::count_ai_analysed(),
			'awaiting_action' => self::count_items_by_status( 'awaiting_information' ),
		);
	}

	/**
	 * Count items by workflow status.
	 *
	 * @param string $status Status key.
	 * @return int
	 */
	private static function count_items_by_status( $status ) {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'status' ),
						'value' => $status,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count open items.
	 *
	 * @return int
	 */
	private static function count_open_items() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count released items created this month.
	 *
	 * @return int
	 */
	private static function count_released_this_month() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'after'     => gmdate( 'Y-m-01 00:00:00' ),
						'inclusive' => true,
					),
				),
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'status' ),
						'value' => 'released',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Count items with AI analysis.
	 *
	 * @return int
	 */
	private static function count_ai_analysed() {
		$query = new WP_Query(
			array(
				'post_type'      => RWF_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => RWF_CPT::meta_key( 'ai_analyzed' ),
						'value' => 'yes',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Get current inbox filters.
	 *
	 * @return array
	 */
	private static function get_current_filters() {
		return array(
			's'         => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'product'   => isset( $_GET['product'] ) ? sanitize_key( wp_unslash( $_GET['product'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'item_type' => isset( $_GET['item_type'] ) ? sanitize_key( wp_unslash( $_GET['item_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'status'    => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);
	}

	/**
	 * Build inbox query.
	 *
	 * @return WP_Query
	 */
	private static function get_inbox_query() {
		$filters    = self::get_current_filters();
		$meta_query = array();

		foreach ( array( 'product', 'item_type', 'status' ) as $filter_key ) {
			if ( '' !== $filters[ $filter_key ] ) {
				$meta_query[] = array(
					'key'   => RWF_CPT::meta_key( $filter_key ),
					'value' => $filters[ $filter_key ],
				);
			}
		}

		$args = array(
			'post_type'      => RWF_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			's'              => $filters['s'],
		);

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		return new WP_Query( $args );
	}
}
