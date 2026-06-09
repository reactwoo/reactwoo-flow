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

	public function test_github_product_map_returns_repository_per_slug() {
		$GLOBALS['rwf_test_options']['rwf_github_product_map'] = array(
			'geocore_pro'  => array(
				'repository'     => 'reactwoo/reactwoo-geocore-pro',
				'webhook_secret' => 'secret-a',
			),
			'api_platform' => array(
				'repository'     => 'reactwoo/reactwoo-api',
				'webhook_secret' => 'secret-b',
			),
		);

		$this->assertSame(
			array(
				'geocore_pro'  => 'reactwoo/reactwoo-geocore-pro',
				'api_platform' => 'reactwoo/reactwoo-api',
			),
			RWF_Settings::get_github_product_repositories()
		);
	}

	public function test_github_repository_for_product_returns_empty_when_unmapped() {
		$this->assertSame( '', RWF_Settings::get_github_repository_for_product( 'unknown_product' ) );
	}

	public function test_github_webhook_secret_resolves_by_repository() {
		$GLOBALS['rwf_test_options']['rwf_github_product_map'] = array(
			'geocore_pro' => array(
				'repository'     => 'reactwoo/reactwoo-geocore-pro',
				'webhook_secret' => 'secret-a',
			),
		);

		$this->assertSame( 'secret-a', RWF_Settings::get_github_webhook_secret_for_repository( 'reactwoo/reactwoo-geocore-pro' ) );
		$this->assertSame( '', RWF_Settings::get_github_webhook_secret_for_repository( 'reactwoo/other' ) );
	}
}
