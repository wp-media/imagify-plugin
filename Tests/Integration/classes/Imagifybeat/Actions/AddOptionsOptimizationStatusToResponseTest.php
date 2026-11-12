<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Integration\classes\Imagifybeat\ImagifybeatTestTrait;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests Imagify\Imagifybeat\Actions::add_options_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_options_optimization_status_to_response
 *
 * @group  Imagifybeat
 */
class AddOptionsOptimizationStatusToResponseTest extends TestCase {
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
		delete_transient( 'imagify_missing_next_gen_total' );

		$this->tearDownImagifybeat();

		parent::tear_down();
	}

	/**
	 * Test: whether the progress is returned. When it is not, the response must be left untouched.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array    $config   The site type, the user role and the contexts sent.
	 * @param  int|null $expected The total returned, null for none.
	 * @return void
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );
		$this->switchToSubSite();
		$this->setSiteUser( $config['role'] );

		// Next-gen images are being generated for 5 media.
		set_transient( 'imagify_missing_next_gen_total', 5 );

		$response = Actions::get_instance()->add_options_optimization_status_to_response(
			[],
			[ 'imagify_options_optimization_status' => $config['contexts'] ]
		);

		if ( null === $expected ) {
			$this->assertSame( [], $response );
			return;
		}

		$this->assertSame( $expected, $response['imagify_options_optimization_status']['total'] );
	}
}
