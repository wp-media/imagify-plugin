<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Integration\classes\Imagifybeat\ImagifybeatTestTrait;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests Imagify\Imagifybeat\Actions::add_custom_folders_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_custom_folders_optimization_status_to_response
 *
 * @group  Imagifybeat
 */
class AddCustomFoldersOptimizationStatusToResponseTest extends TestCase {
	use ImagifybeatTestTrait;

	/**
	 * No API calls here.
	 *
	 * @var bool
	 */
	protected $useApi = false;

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->tearDownImagifybeat();

		parent::tear_down();
	}

	/**
	 * Test: whether the file columns are returned. When they are not, the response must be left untouched.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array $config   The site type, the user role and the context sent.
	 * @param  bool  $expected Whether the file columns are returned.
	 * @return void
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$file_id = $this->createCustomFoldersFile();

		$this->switchToSubSite();
		$this->setSiteUser( $config['role'] );

		$response = Actions::get_instance()->add_custom_folders_optimization_status_to_response(
			[],
			[ 'imagify_custom_folders_optimization_status' => [ $config['context'] => [ '_' . $file_id => 1 ] ] ]
		);

		if ( ! $expected ) {
			$this->assertSame( [], $response );
			return;
		}

		$columns = $response['imagify_custom_folders_optimization_status'];

		$this->assertArrayHasKey( 'custom-folders_' . $file_id, $columns );
		$this->assertStringContainsString( 'pixel.png', $columns[ 'custom-folders_' . $file_id ]['title'] );
	}
}
