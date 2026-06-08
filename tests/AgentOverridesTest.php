<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_AI
 */
class AgentOverridesTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_post_meta'] = array();
		$GLOBALS['rwf_test_options']     = array();
	}

	public function test_runtime_override_takes_precedence_over_item_meta() {
		RWF_CPT::update_meta( 7, 'override_planning_provider', 'openai' );
		RWF_CPT::update_meta( 7, 'override_planning_model', 'gpt-4o-mini' );

		$resolved = RWF_AI::resolve_agent_overrides(
			7,
			'planning',
			array(
				'provider' => 'anthropic',
				'model'    => 'claude-sonnet-4-20250514',
			)
		);

		$this->assertSame( 'anthropic', $resolved['provider'] );
		$this->assertSame( 'claude-sonnet-4-20250514', $resolved['model'] );
	}

	public function test_item_meta_used_when_runtime_override_empty() {
		RWF_CPT::update_meta( 8, 'override_release_provider', 'anthropic' );
		RWF_CPT::update_meta( 8, 'override_release_model', 'claude-3-5-sonnet-20241022' );

		$resolved = RWF_AI::resolve_agent_overrides( 8, 'release' );

		$this->assertSame( 'anthropic', $resolved['provider'] );
		$this->assertSame( 'claude-3-5-sonnet-20241022', $resolved['model'] );
	}
}
