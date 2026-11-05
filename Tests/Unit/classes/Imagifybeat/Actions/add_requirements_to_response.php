<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Imagifybeat\Actions::add_requirements_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_requirements_to_response
 * @group  Imagifybeat
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_AddRequirementsToResponse extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Test: the requirements are added for the users who can open a screen that needs them.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs and the Imagifybeat data.
	 * @param bool  $expected Whether the requirements are sent.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$requirements = Mockery::mock( 'alias:Imagify_Requirements' );
		$requirements->shouldReceive( 'supports_curl' )->andReturn( true );
		$requirements->shouldReceive( 'supports_image_editor' )->andReturn( true );
		$requirements->shouldReceive( 'is_imagify_blocked' )->andReturn( false );
		$requirements->shouldReceive( 'is_api_up' )->andReturn( true );
		$requirements->shouldReceive( 'is_api_key_valid' )->andReturn( true );
		$requirements->shouldReceive( 'is_over_quota' )->andReturn( false );

		$response = Actions::get_instance()->add_requirements_to_response( [ 'other' => 1 ], $config['data'] );

		if ( ! $expected ) {
			$this->assertSame( [ 'other' => 1 ], $response );
			return;
		}

		$this->assertSame(
			[
				'other'                => 1,
				'imagify_requirements' => [
					'curl_missing'          => false,
					'editor_missing'        => false,
					'external_http_blocked' => false,
					'api_down'              => false,
					'key_is_valid'          => true,
					'is_over_quota'         => false,
				],
			],
			$response
		);
	}
}
