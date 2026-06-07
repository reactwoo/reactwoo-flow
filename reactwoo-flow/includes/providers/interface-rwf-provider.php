<?php
/**
 * Provider adapter contract for agent execution.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes agent tasks against a specific AI or orchestration provider.
 */
interface RWF_Provider_Interface {
	/**
	 * Provider identifier (matches settings option keys).
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Whether this provider can run remote execution in the current build.
	 *
	 * @return bool
	 */
	public function is_executable();

	/**
	 * Run the prepared agent record.
	 *
	 * @param array $agent Prepared agent execution record from RWF_Agent::prepare_agent().
	 * @return array|WP_Error Updated agent record on success.
	 */
	public function execute( array $agent );
}
