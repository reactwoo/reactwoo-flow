<?php
/**
 * Plugin capability registration and checks.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages ReactWoo Flow custom capabilities.
 */
class RWF_Capabilities {
	const CAP_MANAGE = 'manage_rwf';

	/**
	 * Capability keys granted to operational roles on activation.
	 *
	 * @return array
	 */
	public static function get_role_caps() {
		return array(
			self::CAP_MANAGE,
			'edit_rwf_items',
			'edit_rwf_item',
			'edit_others_rwf_items',
			'publish_rwf_items',
			'read_rwf_item',
			'read_private_rwf_items',
			'delete_rwf_items',
			'delete_rwf_item',
			'delete_others_rwf_items',
			'delete_private_rwf_items',
			'delete_published_rwf_items',
		);
	}

	/**
	 * Add hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_caps' ) );
	}

	/**
	 * Ensure capabilities exist after plugin updates without re-activation.
	 */
	public static function maybe_upgrade_caps() {
		$stored_version = get_option( 'rwf_caps_version', '' );

		if ( $stored_version === RWF_VERSION ) {
			return;
		}

		self::activate();
		update_option( 'rwf_caps_version', RWF_VERSION );
	}

	/**
	 * Grant capabilities to administrator on plugin activation.
	 */
	public static function activate() {
		$administrator = get_role( 'administrator' );

		if ( ! $administrator ) {
			return;
		}

		foreach ( self::get_role_caps() as $cap ) {
			$administrator->add_cap( $cap );
		}
	}

	/**
	 * Whether the current user can manage Flow settings.
	 *
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( self::CAP_MANAGE );
	}

	/**
	 * Whether the current user can access inbox/dashboard and create items.
	 *
	 * @return bool
	 */
	public static function can_edit_items() {
		return current_user_can( 'edit_rwf_items' ) || self::can_manage();
	}

	/**
	 * Whether the current user can edit a specific item.
	 *
	 * @param int $post_id Item post ID.
	 * @return bool
	 */
	public static function can_edit_item( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			return false;
		}

		return current_user_can( 'edit_rwf_item', $post_id ) || self::can_manage();
	}
}
