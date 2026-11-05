<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Brain\Monkey\Functions;
use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_bulk_optimization_stats_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_bulk_optimization_stats_to_response
 * @group  Imagifybeat
 */
class AddBulkOptimizationStatsToResponseTest extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Test: the folder types the stats are computed for, if any.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs and the Imagifybeat data.
	 * @param array $expected The folder types passed to imagify_get_bulk_stats(), null if not called.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		if ( null === $expected['types'] ) {
			Functions\expect( 'imagify_get_bulk_stats' )->never();
		} else {
			Functions\expect( 'imagify_get_bulk_stats' )
				->once()
				->with( $expected['types'], [ 'fullset' => true ] )
				->andReturn( [ 'stats' ] );
		}

		$response = Actions::get_instance()->add_bulk_optimization_stats_to_response( [], $config['data'] );

		$this->assertSame( null === $expected['types'] ? [] : [ 'imagify_bulk_optimization_stats' => [ 'stats' ] ], $response );
	}
}
