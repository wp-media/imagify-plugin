<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Brain\Monkey\Functions;
use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_library_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_library_optimization_status_to_response
 * @group  Imagifybeat
 */
class Test_AddLibraryOptimizationStatusToResponse extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Test: the media whose status is sent.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The media the user can optimize, the media being optimized and the statuses sent by the browser.
	 * @param int[] $expected IDs of the media whose status is sent.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( [] );
		$this->mockProcesses( $config['allowed_ids'], $config['locked_ids'] );

		Functions\when( 'get_imagify_media_column_content' )->alias(
			function ( $process ) {
				return "column-{$process->media_id}";
			}
		);

		$response = Actions::get_instance()->add_library_optimization_status_to_response(
			[],
			[ 'imagify_library_optimization_status' => $config['statuses'] ]
		);

		$columns = [];

		foreach ( $expected as $media_id ) {
			$columns[ "wp_{$media_id}" ] = "column-{$media_id}";
		}

		$this->assertSame( $columns ? [ 'imagify_library_optimization_status' => $columns ] : [], $response );
	}
}
