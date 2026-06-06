<?php
/**
 * Main plugin orchestrator.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads and wires the ReactWoo Flow components.
 */
final class RWF_Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var RWF_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return RWF_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Run plugin hooks.
	 */
	public function run() {
		$this->load_dependencies();

		RWF_CPT::init();
		RWF_Settings::init();
		RWF_Admin::init();
		RWF_REST::init();
	}

	/**
	 * Load required classes.
	 */
	private function load_dependencies() {
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-cpt.php';
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-settings.php';
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-agent.php';
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-ai.php';
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-rest.php';
		require_once RWF_PLUGIN_DIR . 'includes/class-rwf-admin.php';
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton.' );
	}
}
