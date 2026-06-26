<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Handoff_Markdown
 */
class HandoffMarkdownTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['rwf_test_post_meta'] = array();
		$GLOBALS['rwf_test_posts']       = array();
	}

	public function test_current_task_markdown_includes_problem_and_acceptance_criteria() {
		$post_id = 42;

		$GLOBALS['rwf_test_posts'][ $post_id ] = (object) array(
			'ID'           => $post_id,
			'post_title'   => 'Mini cart shortcode',
			'post_content' => 'Shortcode renders as plain text on frontend.',
			'post_type'    => RWF_CPT::POST_TYPE,
		);

		RWF_CPT::update_meta( $post_id, 'problem_statement', 'Divi mini cart shows raw shortcode.' );
		RWF_CPT::update_meta( $post_id, 'ai_summary', 'Render module output instead of shortcode text.' );
		RWF_CPT::update_meta( $post_id, 'acceptance_criteria', wp_json_encode( array( 'Frontend shows cart UI', 'No raw shortcode visible' ) ) );
		RWF_CPT::update_meta( $post_id, 'specification_markdown', '## Spec\nRegister handler on `init`.' );
		RWF_CPT::update_meta( $post_id, 'suggested_github_branch', 'fix/mini-cart-divi' );
		RWF_CPT::update_meta( $post_id, 'suggested_qa_checklist', 'Load shop page as guest.' );

		$markdown = RWF_Handoff_Markdown::build_current_task_markdown( $post_id );

		$this->assertStringContainsString( '# Current task', $markdown );
		$this->assertStringContainsString( 'Divi mini cart shows raw shortcode.', $markdown );
		$this->assertStringContainsString( 'Frontend shows cart UI', $markdown );
		$this->assertStringContainsString( 'fix/mini-cart-divi', $markdown );
		$this->assertStringContainsString( 'ai-handoff/cursor-output.md', $markdown );
	}

	public function test_template_filenames_lists_handoff_files() {
		$names = RWF_Handoff_Markdown::template_filenames();

		$this->assertContains( 'current-task.md', $names );
		$this->assertContains( 'cursor-output.md', $names );
		$this->assertContains( 'known-issues.md', $names );
	}
}
