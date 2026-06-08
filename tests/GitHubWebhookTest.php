<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Integration_GitHub
 */
class GitHubWebhookTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_options']   = array();
		$GLOBALS['rwf_test_post_meta'] = array();
	}

	public function test_verify_webhook_signature_accepts_valid_hmac() {
		$secret = 'test-secret';
		$body   = '{"action":"opened"}';
		$GLOBALS['rwf_test_options']['rwf_github_webhook_secret'] = $secret;
		$signature = 'sha256=' . hash_hmac( 'sha256', $body, $secret );

		$this->assertTrue( RWF_Integration_GitHub::verify_webhook_signature( $body, $signature ) );
	}

	public function test_verify_webhook_signature_rejects_invalid_hmac() {
		$GLOBALS['rwf_test_options']['rwf_github_webhook_secret'] = 'test-secret';

		$this->assertFalse( RWF_Integration_GitHub::verify_webhook_signature( '{}', 'sha256=deadbeef' ) );
	}

	public function test_is_webhook_enabled_requires_secret_and_setting() {
		$this->assertFalse( RWF_Integration_GitHub::is_webhook_enabled() );

		$GLOBALS['rwf_test_options']['rwf_github_webhook_enabled'] = 'yes';
		$this->assertFalse( RWF_Integration_GitHub::is_webhook_enabled() );

		$GLOBALS['rwf_test_options']['rwf_github_webhook_secret'] = 'secret';
		$this->assertTrue( RWF_Integration_GitHub::is_webhook_enabled() );
	}

	public function test_apply_pull_payload_updates_item_meta() {
		$result = RWF_Integration_GitHub::apply_pull_payload(
			21,
			array(
				'html_url'  => 'https://github.com/reactwoo/flow/pull/9',
				'state'     => 'open',
				'head'      => array( 'ref' => 'feature/test' ),
				'base'      => array(
					'repo' => array( 'full_name' => 'reactwoo/flow' ),
				),
				'title'     => 'Test PR',
				'number'    => 9,
			)
		);

		$this->assertSame( 'https://github.com/reactwoo/flow/pull/9', $result['pr_url'] );
		$this->assertSame( 'reactwoo/flow', get_post_meta( 21, '_rwf_github_repository', true ) );
		$this->assertSame( 'feature/test', get_post_meta( 21, '_rwf_github_branch', true ) );
		$this->assertSame( 'open', get_post_meta( 21, '_rwf_github_pr_state', true ) );
	}

	public function test_find_item_ids_for_pull_filters_by_repository() {
		$GLOBALS['rwf_test_post_meta'][30] = array(
			'_rwf_github_branch'    => 'feature/geocore',
			'_rwf_product'          => 'geocore_pro',
		);
		$GLOBALS['rwf_test_post_meta'][31] = array(
			'_rwf_github_branch'    => 'feature/geocore',
			'_rwf_product'          => 'api_platform',
		);
		$GLOBALS['rwf_test_options']['rwf_github_product_repos'] = "geocore_pro reactwoo/reactwoo-geocore-pro\napi_platform reactwoo/reactwoo-api";

		$pull = array(
			'html_url' => '',
			'head'     => array( 'ref' => 'feature/geocore' ),
			'base'     => array(
				'repo' => array( 'full_name' => 'reactwoo/reactwoo-geocore-pro' ),
			),
		);

		$ids = RWF_Integration_GitHub::find_item_ids_for_pull( $pull, 'reactwoo/reactwoo-geocore-pro' );

		$this->assertSame( array( 30 ), $ids );
	}

	public function test_is_configured_accepts_product_mappings_without_default_repo() {
		$GLOBALS['rwf_test_options']['rwf_github_token']         = 'ghp_test';
		$GLOBALS['rwf_test_options']['rwf_github_product_repos'] = 'geocore_pro reactwoo/reactwoo-geocore-pro';

		$this->assertTrue( RWF_Integration_GitHub::is_configured() );
	}
}
