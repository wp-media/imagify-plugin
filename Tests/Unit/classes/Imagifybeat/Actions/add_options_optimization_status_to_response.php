<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_options_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_options_optimization_status_to_response
 * @group  Imagifybeat
 */
class Test_AddOptionsOptimizationStatusToResponse extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Restore the Bulk singleton.
	 */
	protected function tearDown(): void {
		$this->resetPropertyValue( 'instance', Bulk::class );

		parent::tearDown();
	}

	/**
	 * Test: the contexts used and the progress sent.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs, the Imagifybeat data, the total of media to process and the next-gen format.
	 * @param array $expected The contexts the bulk instances are requested for, and the progress sent (null: none).
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$bulk = $this->stubBulk();

		Functions\when( 'get_transient' )->justReturn( $config['total'] );
		Functions\when( 'get_imagify_option' )->justReturn( $config['format'] );

		$response = Actions::get_instance()->add_options_optimization_status_to_response( [], $config['data'] );

		$this->assertSame( $expected['contexts'], $bulk->contexts );
		$this->assertSame( null === $expected['status'] ? [] : [ 'imagify_options_optimization_status' => $expected['status'] ], $response );
	}
}
