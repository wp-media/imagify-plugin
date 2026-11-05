<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_bulk_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_bulk_optimization_status_to_response
 * @group  Imagifybeat
 */
class Test_AddBulkOptimizationStatusToResponse extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Restore the Bulk singleton.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', Bulk::class );

		parent::tearDown();
	}

	/**
	 * Test: the contexts used and the status sent.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs, the Imagifybeat data and the running bulk processes.
	 * @param array $expected The contexts the bulk instances are requested for, and the status sent (null: none).
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$bulk    = $this->stubBulk();
		$running = $config['running'];

		Functions\when( 'get_transient' )->alias(
			function ( $name ) use ( $running ) {
				if ( preg_match( '/^imagify_(.+)_optimize_running$/', $name, $matches ) && isset( $running[ $matches[1] ] ) ) {
					return $running[ $matches[1] ];
				}

				return false;
			}
		);

		$response = Actions::get_instance()->add_bulk_optimization_status_to_response( [], $config['data'] );

		$this->assertSame( $expected['contexts'], $bulk->contexts );
		$this->assertSame( null === $expected['status'] ? [] : [ 'imagify_bulk_optimization_status' => $expected['status'] ], $response );
	}
}
