<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Integrations
 */
class IntegrationsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_options'] = array();
	}

	public function test_configuration_summary_lists_all_integrations() {
		$summary = RWF_Integrations::get_configuration_summary();

		$this->assertArrayHasKey( 'jira', $summary );
		$this->assertArrayHasKey( 'github', $summary );
		$this->assertArrayHasKey( 'confluence', $summary );
		$this->assertArrayHasKey( 'cursor_mcp', $summary );
		$this->assertFalse( $summary['jira']['configured'] );
	}

	public function test_get_last_test_results_returns_empty_when_unset() {
		$this->assertSame( array(), RWF_Integrations::get_last_test_results() );
	}

	public function test_get_last_test_results_decodes_stored_json() {
		$GLOBALS['rwf_test_options']['rwf_integration_health_last_results'] = wp_json_encode(
			array(
				'jira' => array(
					'label'   => 'Jira',
					'ok'      => true,
					'message' => 'Connection successful.',
				),
			)
		);

		$results = RWF_Integrations::get_last_test_results();

		$this->assertTrue( $results['jira']['ok'] );
		$this->assertSame( 'Connection successful.', $results['jira']['message'] );
	}
}
