<?php
/**
 * @package ReactWoo_Flow
 */

use PHPUnit\Framework\TestCase;

/**
 * @covers RWF_REST
 */
class RestPermissionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['rwf_test_caps'] = array();
	}

	public function test_analyse_permission_denied_without_capability() {
		$request = new Rwf_Test_Array_Request( array( 'id' => 15 ) );

		$this->assertFalse( RWF_REST::can_analyse_item( $request ) );
	}

	public function test_analyse_permission_granted_with_edit_capability() {
		$GLOBALS['rwf_test_caps']['edit_rwf_item:21'] = true;

		$request = new Rwf_Test_Array_Request( array( 'id' => 21 ) );

		$this->assertTrue( RWF_REST::can_analyse_item( $request ) );
	}
}

/**
 * Minimal request stub for REST permission tests.
 */
class Rwf_Test_Array_Request implements ArrayAccess {
	/** @var array<string, mixed> */
	private $params;

	/**
	 * @param array<string, mixed> $params Request params.
	 */
	public function __construct( array $params ) {
		$this->params = $params;
	}

	/**
	 * @param mixed $offset Offset.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->params[ $offset ] );
	}

	/**
	 * @param mixed $offset Offset.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->params[ $offset ];
	}

	/**
	 * @param mixed $offset Offset.
	 * @param mixed $value  Value.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->params[ $offset ] = $value;
	}

	/**
	 * @param mixed $offset Offset.
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->params[ $offset ] );
	}
}
