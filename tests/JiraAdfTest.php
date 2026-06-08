<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Integration_Jira
 */
class JiraAdfTest extends TestCase {
	public function test_text_to_adf_builds_paragraphs() {
		$adf = RWF_Integration_Jira::text_to_adf( "Line one\nLine two" );

		$this->assertSame( 'doc', $adf['type'] );
		$this->assertCount( 2, $adf['content'] );
		$this->assertSame( 'Line one', $adf['content'][0]['content'][0]['text'] );
		$this->assertSame( 'Line two', $adf['content'][1]['content'][0]['text'] );
	}
}
