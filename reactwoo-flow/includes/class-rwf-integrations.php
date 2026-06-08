<?php
/**
 * Integration status and connectivity helpers.
 *
 * @package ReactWoo_Flow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Summarises external integration configuration and connectivity.
 */
class RWF_Integrations {
	/**
	 * Fast configuration summary (no remote calls).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_configuration_summary() {
		return array(
			'jira'       => array(
				'label'       => __( 'Jira', 'reactwoo-flow' ),
				'configured'  => RWF_Integration_Jira::is_configured(),
			),
			'github'     => array(
				'label'       => __( 'GitHub', 'reactwoo-flow' ),
				'configured'  => RWF_Integration_GitHub::is_configured(),
			),
			'confluence' => array(
				'label'       => __( 'Confluence', 'reactwoo-flow' ),
				'configured'  => RWF_Integration_Confluence::is_configured(),
			),
			'cursor_mcp' => array(
				'label'       => __( 'Cursor MCP', 'reactwoo-flow' ),
				'configured'  => RWF_Integration_Cursor_MCP::is_configured(),
			),
		);
	}

	/**
	 * Test remote connectivity for configured integrations.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function test_connections() {
		$results = array();

		if ( RWF_Integration_Jira::is_configured() ) {
			$results['jira'] = self::format_test_result(
				__( 'Jira', 'reactwoo-flow' ),
				RWF_Integration_Jira::test_connection()
			);
		}

		if ( RWF_Integration_GitHub::is_configured() ) {
			$results['github'] = self::format_test_result(
				__( 'GitHub', 'reactwoo-flow' ),
				RWF_Integration_GitHub::test_connection()
			);
		}

		if ( RWF_Integration_Confluence::is_configured() ) {
			$results['confluence'] = self::format_test_result(
				__( 'Confluence', 'reactwoo-flow' ),
				RWF_Integration_Confluence::test_connection()
			);
		}

		if ( RWF_Integration_Cursor_MCP::is_configured() ) {
			$results['cursor_mcp'] = self::format_test_result(
				__( 'Cursor MCP', 'reactwoo-flow' ),
				RWF_Integration_Cursor_MCP::test_connection()
			);
		}

		update_option( 'rwf_integration_health_last_test', current_time( 'mysql' ) );
		update_option( 'rwf_integration_health_last_results', wp_json_encode( $results ) );

		return $results;
	}

	/**
	 * Last saved connectivity test results.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_last_test_results() {
		$raw = get_option( 'rwf_integration_health_last_results', '' );
		$data = json_decode( (string) $raw, true );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * @param string         $label  Integration label.
	 * @param true|WP_Error  $result Test result.
	 * @return array<string, mixed>
	 */
	private static function format_test_result( $label, $result ) {
		if ( is_wp_error( $result ) ) {
			return array(
				'label'   => $label,
				'ok'      => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'label'   => $label,
			'ok'      => true,
			'message' => __( 'Connection successful.', 'reactwoo-flow' ),
		);
	}
}
