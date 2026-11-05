<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_custom_folders_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_custom_folders_optimization_status_to_response
 * @group  Imagifybeat
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AddCustomFoldersOptimizationStatusToResponseTest extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Test: the files whose columns are sent.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs, the files the user can optimize and the statuses sent by the browser.
	 * @param int[] $expected IDs of the files whose columns are sent.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );
		$this->mockProcesses( $config['allowed_ids'] );

		Mockery::mock( 'overload:Imagify_Files_List_Table' );

		$ajax_post = Mockery::mock();
		$ajax_post->shouldReceive( 'get_media_columns' )->andReturnUsing(
			function ( $process ) {
				return [ 'title' => "file-{$process->media_id}" ];
			}
		);
		Mockery::mock( 'alias:Imagify_Admin_Ajax_Post' )->shouldReceive( 'get_instance' )->andReturn( $ajax_post );

		$response = Actions::get_instance()->add_custom_folders_optimization_status_to_response(
			[],
			[ 'imagify_custom_folders_optimization_status' => $config['statuses'] ]
		);

		$columns = [];

		foreach ( $expected as $file_id ) {
			$columns[ "custom-folders_{$file_id}" ] = [ 'title' => "file-{$file_id}" ];
		}

		$this->assertSame( $columns ? [ 'imagify_custom_folders_optimization_status' => $columns ] : [], $response );
	}
}
