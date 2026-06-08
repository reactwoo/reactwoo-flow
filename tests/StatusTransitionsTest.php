<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_CPT
 */
class StatusTransitionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_post_meta'] = array();
	}

	public function test_new_item_can_move_to_needs_triage() {
		$options = RWF_CPT::get_available_status_transitions( 'new' );
		$this->assertArrayHasKey( 'needs_triage', $options );
	}

	public function test_released_item_can_only_close() {
		$options = RWF_CPT::get_available_status_transitions( 'released' );
		$this->assertSame( array( 'closed' ), array_keys( $options ) );
	}

	public function test_valid_transition_updates_status_and_history() {
		$post_id = 42;
		RWF_CPT::update_meta( $post_id, 'status', 'new' );

		$result = RWF_CPT::transition_status( $post_id, 'needs_triage', 'Unit test transition.' );

		$this->assertTrue( $result );
		$this->assertSame( 'needs_triage', RWF_CPT::get_meta( $post_id, 'status' ) );

		$history = RWF_CPT::get_status_history( $post_id );
		$this->assertCount( 1, $history );
		$this->assertSame( 'new', $history[0]['from'] );
		$this->assertSame( 'needs_triage', $history[0]['to'] );
	}

	public function test_invalid_transition_returns_error() {
		$post_id = 99;
		RWF_CPT::update_meta( $post_id, 'status', 'new' );

		$result = RWF_CPT::transition_status( $post_id, 'released' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rwf_invalid_status_transition', $result->code );
		$this->assertSame( 'new', RWF_CPT::get_meta( $post_id, 'status' ) );
	}
}
