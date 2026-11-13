<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_CPT
 */
class SupportWorkflowTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_post_meta'] = array();
	}

	public function test_public_item_types_exclude_internal_types() {
		$types = array_keys( RWF_CPT::get_public_item_types() );

		$this->assertSame( array( 'support_ticket', 'bug_report', 'feature_request' ), $types );
		$this->assertNotContains( 'technical_debt', $types );
		$this->assertNotContains( 'release_task', $types );
		$this->assertNotContains( 'security_issue', $types );
	}

	public function test_public_products_are_current_catalogue_only() {
		$products = RWF_CPT::get_public_products();

		$this->assertArrayHasKey( 'geo_core', $products );
		$this->assertArrayHasKey( 'geocore_pro', $products );
		$this->assertArrayHasKey( 'maintenance', $products );
		$this->assertArrayNotHasKey( 'wooaliai', $products );
		$this->assertArrayNotHasKey( 'internal_infrastructure', $products );
	}

	public function test_legacy_products_still_resolve_for_existing_items() {
		$products = RWF_CPT::get_products();

		foreach ( array( 'reactwoo_core', 'wooaliai', 'google_reviews', 'whmcs_bridge', 'geocore_pro' ) as $legacy ) {
			$this->assertArrayHasKey( $legacy, $products );
		}
	}

	public function test_every_status_has_transitions_and_targets_exist() {
		$statuses = RWF_CPT::get_statuses();
		$map      = RWF_CPT::get_status_transition_map();

		foreach ( array_keys( $statuses ) as $status ) {
			$this->assertArrayHasKey( $status, $map, "No transitions for $status" );
			foreach ( $map[ $status ] as $target ) {
				$this->assertArrayHasKey( $target, $statuses, "Unknown target $target from $status" );
			}
		}
	}

	public function test_support_items_go_to_diagnosis_after_triage() {
		$this->assertSame( 'diagnosing', RWF_CPT::get_status_after_triage( 'support_ticket', 'email' ) );
		$this->assertSame( 'diagnosing', RWF_CPT::get_status_after_triage( 'bug_report', 'website_form' ) );
		$this->assertSame( 'confirmed', RWF_CPT::get_status_after_triage( 'bug_report', 'internal' ) );
		$this->assertSame( 'confirmed', RWF_CPT::get_status_after_triage( 'feature_request', 'website_form' ) );
	}

	public function test_support_item_can_escalate_to_development() {
		$post_id = 777;
		RWF_CPT::update_meta( $post_id, 'status', 'diagnosing' );

		$this->assertTrue( RWF_CPT::transition_status( $post_id, 'confirmed', 'Escalated as a product bug.' ) );
		$this->assertSame( 'confirmed', RWF_CPT::get_meta( $post_id, 'status' ) );
	}

	public function test_fix_cannot_skip_verification() {
		$post_id = 778;
		RWF_CPT::update_meta( $post_id, 'status', 'applying_fix' );

		$result = RWF_CPT::transition_status( $post_id, 'resolved' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
