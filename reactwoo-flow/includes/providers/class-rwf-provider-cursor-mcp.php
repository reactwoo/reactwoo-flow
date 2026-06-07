<?php
/**
 * Cursor MCP bridge provider adapter (prepare-only until bridge ships).
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placeholder for future Cursor MCP remote execution.
 */
class RWF_Provider_Cursor_MCP implements RWF_Provider_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'cursor_mcp';
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_executable() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function execute( array $agent ) {
		$agent['status'] = RWF_Agent::STATUS_FAILED;
		$agent['error']  = __( 'Cursor MCP execution is planned for a future bridge. ReactWoo Flow can prepare the context payload now, but cannot send it to Cursor yet.', 'reactwoo-flow' );

		return new WP_Error( 'rwf_cursor_mcp_not_connected', $agent['error'], $agent );
	}
}
