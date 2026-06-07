<?php
/**
 * Frontend intake forms.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles public website/support intake submissions.
 */
class RWF_Intake {
	/**
	 * Add hooks.
	 */
	public static function init() {
		add_shortcode( 'reactwoo_flow_intake', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'admin_post_rwf_submit_intake', array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_rwf_submit_intake', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Register frontend assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'rwf-frontend',
			RWF_PLUGIN_URL . 'assets/frontend.css',
			array(),
			RWF_VERSION
		);
	}

	/**
	 * Render the intake shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'product'   => '',
				'item_type' => 'support_ticket',
				'title'     => __( 'Send a ReactWoo request', 'reactwoo-flow' ),
			),
			$atts,
			'reactwoo_flow_intake'
		);

		wp_enqueue_style( 'rwf-frontend' );

		$submitted = isset( $_GET['rwf_intake_submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rwf_intake_submitted'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error     = isset( $_GET['rwf_intake_error'] ) ? sanitize_key( wp_unslash( $_GET['rwf_intake_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		?>
		<div class="rwf-intake">
			<?php if ( $submitted ) : ?>
				<div class="rwf-intake-notice rwf-intake-notice-success">
					<?php esc_html_e( 'Thanks. Your request has been sent to ReactWoo Flow for triage.', 'reactwoo-flow' ); ?>
				</div>
			<?php elseif ( $error ) : ?>
				<div class="rwf-intake-notice rwf-intake-notice-error">
					<?php esc_html_e( 'Your request could not be submitted. Please check the form and try again.', 'reactwoo-flow' ); ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<h2><?php echo esc_html( $atts['title'] ); ?></h2>
				<?php wp_nonce_field( 'rwf_submit_intake' ); ?>
				<input type="hidden" name="action" value="rwf_submit_intake" />
				<input type="hidden" name="rwf_redirect" value="<?php echo esc_url( self::current_url() ); ?>" />
				<input type="text" name="rwf_company_website" value="" class="rwf-honeypot" tabindex="-1" autocomplete="off" />

				<div class="rwf-intake-grid">
					<label>
						<span><?php esc_html_e( 'Product', 'reactwoo-flow' ); ?></span>
						<select name="rwf_product" required>
							<option value=""><?php esc_html_e( 'Select product', 'reactwoo-flow' ); ?></option>
							<?php foreach ( RWF_CPT::get_products() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $atts['product'], $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<label>
						<span><?php esc_html_e( 'Request Type', 'reactwoo-flow' ); ?></span>
						<select name="rwf_item_type" required>
							<?php foreach ( RWF_CPT::get_item_types() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $atts['item_type'], $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<label>
						<span><?php esc_html_e( 'Your Name', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_reporter" autocomplete="name" />
					</label>

					<label>
						<span><?php esc_html_e( 'Email', 'reactwoo-flow' ); ?></span>
						<input type="email" name="rwf_customer_email" autocomplete="email" />
					</label>
				</div>

				<label>
					<span><?php esc_html_e( 'Title', 'reactwoo-flow' ); ?></span>
					<input type="text" name="rwf_title" required />
				</label>

				<label>
					<span><?php esc_html_e( 'Description', 'reactwoo-flow' ); ?></span>
					<textarea name="rwf_description" rows="7" required></textarea>
				</label>

				<div class="rwf-intake-grid">
					<label>
						<span><?php esc_html_e( 'Plugin Version', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_plugin_version" />
					</label>

					<label>
						<span><?php esc_html_e( 'Site URL', 'reactwoo-flow' ); ?></span>
						<input type="url" name="rwf_site_url" />
					</label>
				</div>

				<div class="rwf-intake-grid">
					<label>
						<span><?php esc_html_e( 'WordPress Version', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_wordpress_version" />
					</label>

					<label>
						<span><?php esc_html_e( 'WooCommerce Version', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_woocommerce_version" />
					</label>

					<label>
						<span><?php esc_html_e( 'PHP Version', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_php_version" />
					</label>

					<label>
						<span><?php esc_html_e( 'Theme', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_theme" />
					</label>

					<label>
						<span><?php esc_html_e( 'Browser', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_browser" />
					</label>

					<label>
						<span><?php esc_html_e( 'Device', 'reactwoo-flow' ); ?></span>
						<input type="text" name="rwf_device" />
					</label>
				</div>

				<label>
					<span><?php esc_html_e( 'Error Message', 'reactwoo-flow' ); ?></span>
					<textarea name="rwf_error_message" rows="4"></textarea>
				</label>

				<label>
					<span><?php esc_html_e( 'Steps to Reproduce', 'reactwoo-flow' ); ?></span>
					<textarea name="rwf_steps_to_reproduce" rows="4"></textarea>
				</label>

				<div class="rwf-intake-grid">
					<label>
						<span><?php esc_html_e( 'Expected Behaviour', 'reactwoo-flow' ); ?></span>
						<textarea name="rwf_expected_behaviour" rows="4"></textarea>
					</label>

					<label>
						<span><?php esc_html_e( 'Actual Behaviour', 'reactwoo-flow' ); ?></span>
						<textarea name="rwf_actual_behaviour" rows="4"></textarea>
					</label>
				</div>

				<div class="rwf-intake-grid">
					<label>
						<span><?php esc_html_e( 'Screenshot URLs', 'reactwoo-flow' ); ?></span>
						<textarea name="rwf_screenshots" rows="3" placeholder="<?php esc_attr_e( 'One URL per line', 'reactwoo-flow' ); ?>"></textarea>
					</label>

					<label>
						<span><?php esc_html_e( 'Log Files or Log Excerpts', 'reactwoo-flow' ); ?></span>
						<textarea name="rwf_log_files" rows="3"></textarea>
					</label>
				</div>

				<button type="submit"><?php esc_html_e( 'Submit Request', 'reactwoo-flow' ); ?></button>
			</form>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Handle intake form submissions.
	 */
	public static function handle_submission() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'rwf_submit_intake' ) ) {
			self::redirect_with_error( 'nonce' );
		}

		if ( ! empty( $_POST['rwf_company_website'] ) ) {
			self::redirect_with_error( 'spam' );
		}

		$title       = isset( $_POST['rwf_title'] ) ? sanitize_text_field( wp_unslash( $_POST['rwf_title'] ) ) : '';
		$description = isset( $_POST['rwf_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['rwf_description'] ) ) : '';
		$product     = isset( $_POST['rwf_product'] ) ? sanitize_key( wp_unslash( $_POST['rwf_product'] ) ) : '';
		$item_type   = isset( $_POST['rwf_item_type'] ) ? sanitize_key( wp_unslash( $_POST['rwf_item_type'] ) ) : '';

		$products   = RWF_CPT::get_products();
		$item_types = RWF_CPT::get_item_types();

		if ( '' === $title || '' === $description || ! isset( $products[ $product ] ) || ! isset( $item_types[ $item_type ] ) ) {
			self::redirect_with_error( 'required' );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => RWF_CPT::POST_TYPE,
				'post_title'   => $title,
				'post_content' => $description,
				'post_status'  => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with_error( 'insert' );
		}

		RWF_CPT::update_meta( $post_id, 'product', $product );
		RWF_CPT::update_meta( $post_id, 'item_type', $item_type );
		RWF_CPT::update_meta( $post_id, 'priority', 'medium' );
		RWF_CPT::transition_status( $post_id, 'needs_triage', __( 'Submitted from website intake form.', 'reactwoo-flow' ) );
		RWF_CPT::update_meta( $post_id, 'source', 'website_form' );
		RWF_CPT::update_meta( $post_id, 'reporter', isset( $_POST['rwf_reporter'] ) ? wp_unslash( $_POST['rwf_reporter'] ) : '' );
		RWF_CPT::update_meta( $post_id, 'customer_email', isset( $_POST['rwf_customer_email'] ) ? wp_unslash( $_POST['rwf_customer_email'] ) : '' );
		self::save_optional_meta_fields(
			$post_id,
			array(
				'plugin_version',
				'site_url',
				'wordpress_version',
				'woocommerce_version',
				'php_version',
				'theme',
				'browser',
				'device',
				'error_message',
				'steps_to_reproduce',
				'expected_behaviour',
				'actual_behaviour',
				'screenshots',
				'log_files',
			)
		);
		self::send_notification( $post_id );

		self::redirect_with_success();
	}

	/**
	 * Send optional notification email for new intake submissions.
	 *
	 * @param int $post_id Item post ID.
	 */
	private static function send_notification( $post_id ) {
		$email = RWF_Settings::get( 'rwf_intake_notification_email' );

		if ( '' === $email || ! is_email( $email ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: item ID. */
			__( 'New ReactWoo Flow intake item #%d', 'reactwoo-flow' ),
			$post_id
		);
		$body    = sprintf(
			/* translators: 1: item ID, 2: title, 3: product, 4: type, 5: admin URL. */
			__( "A new ReactWoo Flow item was submitted.\n\nID: %1\$d\nTitle: %2\$s\nProduct: %3\$s\nType: %4\$s\n\nOpen item: %5\$s", 'reactwoo-flow' ),
			$post_id,
			get_the_title( $post_id ),
			RWF_CPT::option_label( RWF_CPT::get_products(), RWF_CPT::get_meta( $post_id, 'product' ) ),
			RWF_CPT::option_label( RWF_CPT::get_item_types(), RWF_CPT::get_meta( $post_id, 'item_type' ) ),
			admin_url( 'admin.php?page=' . RWF_Admin::PAGE_ITEM . '&id=' . $post_id )
		);

		wp_mail( $email, $subject, $body );
	}

	/**
	 * Save optional frontend fields into item meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $fields  Field keys without the rwf_ prefix.
	 */
	private static function save_optional_meta_fields( $post_id, $fields ) {
		foreach ( $fields as $field_key ) {
			$request_key = 'rwf_' . $field_key;
			$value       = isset( $_POST[ $request_key ] ) ? wp_unslash( $_POST[ $request_key ] ) : '';

			RWF_CPT::update_meta( $post_id, $field_key, $value );
		}
	}

	/**
	 * Redirect after successful submission.
	 */
	private static function redirect_with_success() {
		wp_safe_redirect(
			add_query_arg(
				'rwf_intake_submitted',
				'1',
				self::get_redirect_url()
			)
		);
		exit;
	}

	/**
	 * Redirect after failed submission.
	 *
	 * @param string $error Error code.
	 */
	private static function redirect_with_error( $error ) {
		wp_safe_redirect(
			add_query_arg(
				'rwf_intake_error',
				sanitize_key( $error ),
				self::get_redirect_url()
			)
		);
		exit;
	}

	/**
	 * Return a safe redirect target.
	 *
	 * @return string
	 */
	private static function get_redirect_url() {
		$redirect = isset( $_POST['rwf_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['rwf_redirect'] ) ) : '';

		return $redirect ? $redirect : home_url( '/' );
	}

	/**
	 * Return current URL without form status query args.
	 *
	 * @return string
	 */
	private static function current_url() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return remove_query_arg( array( 'rwf_intake_submitted', 'rwf_intake_error' ), $scheme . $host . $uri );
	}
}
