<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_Integration_Jira
 */
class JiraEpicTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_options']   = array();
		$GLOBALS['rwf_test_post_meta'] = array();
	}

	public function test_normalise_issue_key_accepts_valid_keys() {
		$this->assertSame( 'RWF-100', RWF_Integration_Jira::normalise_issue_key( 'rwf-100' ) );
		$this->assertSame( 'GEO-12', RWF_Integration_Jira::normalise_issue_key( ' GEO-12 ' ) );
	}

	public function test_normalise_issue_key_rejects_invalid_keys() {
		$this->assertSame( '', RWF_Integration_Jira::normalise_issue_key( 'not-a-key' ) );
		$this->assertSame( '', RWF_Integration_Jira::normalise_issue_key( '' ) );
	}

	public function test_resolve_epic_key_prefers_item_meta() {
		update_post_meta( 9, '_rwf_jira_epic_key', 'ITEM-9' );
		$GLOBALS['rwf_test_options']['rwf_jira_default_epic_key'] = 'DEFAULT-1';

		$this->assertSame( 'ITEM-9', RWF_Integration_Jira::resolve_epic_key( 9 ) );
	}

	public function test_append_epic_link_fields_uses_custom_field_when_configured() {
		$GLOBALS['rwf_test_options']['rwf_jira_epic_link_field'] = 'customfield_10014';
		update_post_meta( 3, '_rwf_jira_epic_key', 'RWF-3' );

		$fields = array();
		RWF_Integration_Jira::append_epic_link_fields( $fields, 3 );

		$this->assertSame( 'RWF-3', $fields['customfield_10014'] );
		$this->assertArrayNotHasKey( 'parent', $fields );
	}

	public function test_append_epic_link_fields_uses_parent_when_no_custom_field() {
		update_post_meta( 4, '_rwf_jira_epic_key', 'RWF-4' );

		$fields = array();
		RWF_Integration_Jira::append_epic_link_fields( $fields, 4 );

		$this->assertSame( 'RWF-4', $fields['parent']['key'] );
	}
}
