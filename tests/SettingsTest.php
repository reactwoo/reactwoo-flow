<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Settings
 */
class SettingsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_options'] = array();
	}

	public function test_is_yes_returns_true_for_yes_value() {
		$GLOBALS['rwf_test_options']['rwf_auto_create_jira_on_triage'] = 'yes';
		$this->assertTrue( RWF_Settings::is_yes( 'rwf_auto_create_jira_on_triage' ) );
	}

	public function test_is_yes_returns_false_for_empty_value() {
		$this->assertFalse( RWF_Settings::is_yes( 'rwf_auto_create_jira_on_triage' ) );
	}

	public function test_handoff_automation_settings_default_to_no() {
		$this->assertFalse( RWF_Settings::is_yes( 'rwf_auto_publish_confluence_on_spec' ) );
		$this->assertFalse( RWF_Settings::is_yes( 'rwf_auto_send_cursor_on_handoff' ) );
		$this->assertFalse( RWF_Settings::is_yes( 'rwf_auto_sync_github_on_handoff' ) );
	}

	public function test_github_product_repository_map_parses_lines() {
		$GLOBALS['rwf_test_options']['rwf_github_product_repos'] = "geocore_pro reactwoo/reactwoo-geocore-pro\napi_platform: reactwoo/reactwoo-api";

		$map = RWF_Settings::get_github_product_repositories();

		$this->assertSame(
			array(
				'geocore_pro'  => 'reactwoo/reactwoo-geocore-pro',
				'api_platform' => 'reactwoo/reactwoo-api',
			),
			$map
		);
	}

	public function test_github_repository_for_product_falls_back_to_default() {
		$GLOBALS['rwf_test_options']['rwf_github_repository'] = 'reactwoo/reactwoo-flow';

		$this->assertSame( 'reactwoo/reactwoo-flow', RWF_Settings::get_github_repository_for_product( 'unknown_product' ) );
	}
}
