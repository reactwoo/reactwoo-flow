<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Redact
 * @covers RWF_AI::build_item_context
 * @covers RWF_Agent::prepare_agent
 */
class RedactionTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_post_meta'] = array();
		$GLOBALS['rwf_test_options']   = array();
	}

	public function test_emails_are_redacted() {
		$this->assertSame( 'Contact [email] today', RWF_Redact::redact_text( 'Contact jane.doe+shop@example.co.uk today' ) );
	}

	public function test_customer_urls_are_redacted_but_github_is_kept() {
		$text = RWF_Redact::redact_text( 'Broken on https://client-shop.example/checkout see https://github.com/reactwoo/reactwoo-flow/pull/12' );

		$this->assertStringNotContainsString( 'client-shop', $text );
		$this->assertStringContainsString( '[url]', $text );
		$this->assertStringContainsString( 'https://github.com/reactwoo/reactwoo-flow/pull/12', $text );
	}

	public function test_safe_host_url_with_query_string_is_redacted() {
		$this->assertSame( '[url]', RWF_Redact::redact_text( 'https://reactwoo.com/my-account/?token=abc' ) );
	}

	public function test_lookalike_host_is_not_treated_as_safe() {
		$this->assertFalse( RWF_Redact::is_safe_url( 'https://evilgithub.com/x' ) );
		$this->assertTrue( RWF_Redact::is_safe_url( 'https://clients.reactwoo.com/x' ) );
	}

	public function test_licence_keys_and_secrets_are_redacted() {
		$this->assertSame( '[key]', RWF_Redact::redact_text( 'ABCD-1234-EFGH-5678' ) );
		$this->assertSame( 'password: [secret]', RWF_Redact::redact_text( 'password: hunter2!' ) );
		$this->assertSame( 'my licence key is [secret]', RWF_Redact::redact_text( 'my licence key is RW-XYZ' ) );
		$this->assertSame( 'Authorization: [secret]', RWF_Redact::redact_text( 'Authorization: Bearer sk-abc.def' ) );
		$this->assertSame( 'token [key] end', RWF_Redact::redact_text( 'token 9f8e7d6c5b4a39281706f5e4d3c2b1a0 end' ) );
	}

	public function test_ip_addresses_are_redacted_but_versions_are_kept() {
		$this->assertSame( 'From [ip] on PHP 8.2.34', RWF_Redact::redact_text( 'From 192.168.10.4 on PHP 8.2.34' ) );
	}

	public function test_code_identifiers_survive() {
		$text = 'Fatal error in woocommerce_checkout_update_order_review_fragments at line 42';
		$this->assertSame( $text, RWF_Redact::redact_text( $text ) );
	}

	public function test_excluded_keys_are_removed_recursively() {
		$value = RWF_Redact::redact_value(
			array(
				'fields' => array(
					'customer_email' => array( 'value' => 'a@b.com' ),
					'reporter'       => array( 'value' => 'Jane' ),
					'php_version'    => array( 'value' => '8.2' ),
				),
			)
		);

		$this->assertArrayNotHasKey( 'customer_email', $value['fields'] );
		$this->assertArrayNotHasKey( 'reporter', $value['fields'] );
		$this->assertSame( '8.2', $value['fields']['php_version']['value'] );
	}

	public function test_item_context_withholds_customer_fields() {
		$post_id = 501;
		RWF_CPT::update_meta( $post_id, 'customer_email', 'jane@example.com' );
		RWF_CPT::update_meta( $post_id, 'reporter', 'Jane Doe' );
		RWF_CPT::update_meta( $post_id, 'license_key', 'ABCD-1234-EFGH-5678' );
		RWF_CPT::update_meta( $post_id, 'site_url', 'https://jane-shop.example' );
		RWF_CPT::update_meta( $post_id, 'php_version', '8.2.34' );
		RWF_CPT::update_meta( $post_id, 'error_message', 'Mail to jane@example.com failed' );

		$context = RWF_AI::build_item_context( $post_id );
		$encoded = wp_json_encode( $context );

		foreach ( array( 'customer_email', 'reporter', 'license_key', 'site_url' ) as $field ) {
			$this->assertArrayNotHasKey( $field, $context['fields'] );
		}
		$this->assertStringNotContainsString( 'jane', $encoded );
		$this->assertStringNotContainsString( 'ABCD-1234', $encoded );
		$this->assertSame( '8.2.34', $context['fields']['php_version']['value'] );
		$this->assertSame( 'Mail to [email] failed', $context['fields']['error_message']['value'] );
	}

	public function test_prepare_agent_redacts_any_input_context() {
		$agent = RWF_Agent::prepare_agent(
			array(
				'agent_type'    => 'planning',
				'input_context' => array(
					'customer_email' => 'x@y.com',
					'note'           => 'Call me on x@y.com',
				),
			)
		);

		$this->assertArrayNotHasKey( 'customer_email', $agent['input_context'] );
		$this->assertSame( 'Call me on [email]', $agent['input_context']['note'] );
	}
}
