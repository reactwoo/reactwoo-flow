<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Agent::usage_from_response
 * @covers RWF_Provider_XAI
 */
class ProviderUsageTest extends TestCase {
	public function test_xai_cost_ticks_convert_to_usd() {
		$usage = RWF_Agent::usage_from_response(
			array(
				'usage' => array(
					'prompt_tokens'     => 199,
					'completion_tokens' => 1,
					'total_tokens'      => 200,
					'cost_in_usd_ticks' => 37756000,
				),
			)
		);

		$this->assertSame( 199, $usage['input_tokens'] );
		$this->assertSame( 1, $usage['output_tokens'] );
		$this->assertSame( 200, $usage['total_tokens'] );
		$this->assertSame( 0.0037756, $usage['cost_usd'] );
		$this->assertSame( 'provider', $usage['cost_source'] );
	}

	public function test_anthropic_usage_has_tokens_but_no_cost() {
		$usage = RWF_Agent::usage_from_response( array( 'usage' => array( 'input_tokens' => 10, 'output_tokens' => 5 ) ) );

		$this->assertSame( 15, $usage['total_tokens'] );
		$this->assertNull( $usage['cost_usd'] );
		$this->assertSame( '', $usage['cost_source'] );
	}

	public function test_missing_usage_returns_empty_record() {
		$this->assertSame( RWF_Agent::empty_usage(), RWF_Agent::usage_from_response( array() ) );
	}

	public function test_xai_is_registered_provider() {
		$this->assertArrayHasKey( 'xai', RWF_Agent::get_providers() );
		$this->assertInstanceOf( RWF_Provider_XAI::class, RWF_Agent::get_provider( 'xai' ) );
	}

	public function test_xai_success_response_sets_output_and_usage() {
		$agent = RWF_Agent::prepare_agent( array( 'agent_type' => 'planning', 'provider' => 'xai', 'model' => 'grok-4.3' ) );
		$body  = wp_json_encode(
			array(
				'choices' => array( array( 'message' => array( 'content' => '{"ai_summary":"ok"}' ) ) ),
				'usage'   => array( 'prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120, 'cost_in_usd_ticks' => 1250000000 ),
			)
		);

		$result = RWF_Provider_XAI::apply_response( $agent, 200, $body );

		$this->assertSame( RWF_Agent::STATUS_SUCCEEDED, $result['status'] );
		$this->assertSame( '{"ai_summary":"ok"}', $result['output'] );
		$this->assertSame( 0.125, $result['usage']['cost_usd'] );
	}

	public function test_xai_error_response_keeps_reported_cost() {
		$agent  = RWF_Agent::prepare_agent( array( 'agent_type' => 'planning', 'provider' => 'xai' ) );
		$result = RWF_Provider_XAI::apply_response( $agent, 500, wp_json_encode( array( 'usage' => array( 'cost_in_usd_ticks' => 10000000 ) ) ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( RWF_Agent::STATUS_FAILED, $result->data['status'] );
		$this->assertSame( 0.001, $result->data['usage']['cost_usd'] );
	}

	public function test_xai_payload_uses_default_model_when_blank() {
		$agent          = RWF_Agent::prepare_agent( array( 'agent_type' => 'planning', 'provider' => 'xai' ) );
		$agent['model'] = '';
		$payload        = RWF_Provider_XAI::build_payload( $agent );

		$this->assertSame( RWF_Provider_XAI::DEFAULT_MODEL, $payload['model'] );
		$this->assertSame( 'system', $payload['messages'][0]['role'] );
	}
}
