<?php
/**
 * Plugin Name: ReactWoo Flow
 * Description: AI-native product intake and triage platform for ReactWoo operations.
 * Version: 0.1.0
 * Author: ReactWoo
 * Text Domain: reactwoo-flow
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RWF_VERSION', '0.1.0' );
define( 'RWF_PLUGIN_FILE', __FILE__ );
define( 'RWF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RWF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RWF_PLUGIN_DIR . 'includes/class-rwf-plugin.php';

/**
 * Activate the plugin.
 */
function rwf_activate() {
	require_once RWF_PLUGIN_DIR . 'includes/class-rwf-cpt.php';

	RWF_CPT::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rwf_activate' );

/**
 * Deactivate the plugin.
 */
function rwf_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rwf_deactivate' );

RWF_Plugin::instance()->run();
