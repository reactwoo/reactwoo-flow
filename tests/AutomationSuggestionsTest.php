<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Automation
 */
class AutomationSuggestionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_options']   = array();
		$GLOBALS['rwf_test_post_meta'] = array();
	}

	public function test_apply_triage_suggestions_substitutes_item_id_in_branch() {
		update_post_meta( 12, '_rwf_suggested_github_branch', 'rwf-{id}-example-feature' );
		update_post_meta( 12, '_rwf_ai_analyzed', 'yes' );

		$applied = RWF_Automation::apply_triage_suggestions( 12, false );

		$this->assertSame( 'rwf-12-example-feature', $applied['github_branch'] );
		$this->assertSame( 'rwf-12-example-feature', get_post_meta( 12, '_rwf_github_branch', true ) );
	}

	public function test_apply_triage_suggestions_respects_auto_branch_setting() {
		update_post_meta( 13, '_rwf_suggested_github_branch', 'feature/test' );

		$applied = RWF_Automation::apply_triage_suggestions( 13, true );

		$this->assertFalse( $applied['github_branch'] );
		$this->assertSame( '', get_post_meta( 13, '_rwf_github_branch', true ) );

		$GLOBALS['rwf_test_options']['rwf_auto_apply_suggested_branch'] = 'yes';
		$applied = RWF_Automation::apply_triage_suggestions( 13, true );

		$this->assertSame( 'feature/test', $applied['github_branch'] );
	}
}
