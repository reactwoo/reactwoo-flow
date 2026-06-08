<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_AI
 */
class AnalysisNormalisationTest extends TestCase {
	public function test_camel_case_keys_are_mapped_to_meta_fields() {
		$result = RWF_AI::normalise_analysis(
			array(
				'summary'             => 'Short summary',
				'problemStatement'    => 'Problem text',
				'acceptanceCriteria'  => array( 'Criterion one' ),
				'suggestedGitHubBranch' => 'feature/example',
			)
		);

		$this->assertSame( 'Short summary', $result['ai_summary'] );
		$this->assertSame( 'Problem text', $result['problem_statement'] );
		$this->assertSame( array( 'Criterion one' ), $result['acceptance_criteria'] );
		$this->assertSame( 'feature/example', $result['suggested_github_branch'] );
	}

	public function test_missing_ai_fields_are_initialised() {
		$result = RWF_AI::normalise_analysis( array( 'summary' => 'Only summary' ) );

		foreach ( array_keys( RWF_CPT::get_ai_fields() ) as $field_key ) {
			$this->assertArrayHasKey( $field_key, $result );
		}
	}
}
