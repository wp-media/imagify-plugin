<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Integration\classes\Imagifybeat\ImagifybeatTestTrait;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests Imagify\Imagifybeat\Actions::add_bulk_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_bulk_optimization_status_to_response
 *
 * @group  Imagifybeat
 */
class AddBulkOptimizationStatusToResponseTest extends TestCase {
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
	 * Test: the contexts the bulk status is returned for. When none, the response must be left untouched.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array      $config   The site type, the user role and the groups sent.
	 * @param  array|null $expected The contexts returned, null for none.
	 * @return void
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );
		$this->switchToSubSite();
		$this->setSiteUser( $config['role'] );

		$response = Actions::get_instance()->add_bulk_optimization_status_to_response(
			[],
			[ 'imagify_bulk_optimization_status' => $config['groups'] ]
		);

		if ( null === $expected ) {
			$this->assertSame( [], $response );
			return;
		}

		$this->assertSame( $expected, array_keys( $response['imagify_bulk_optimization_status']['groups_data'] ) );
	}
}
