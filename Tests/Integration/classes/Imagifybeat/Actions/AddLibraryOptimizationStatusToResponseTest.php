<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Actions;

use Imagify\Imagifybeat\Actions;
use Imagify\Tests\Integration\classes\Imagifybeat\ImagifybeatTestTrait;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests Imagify\Imagifybeat\Actions::add_library_optimization_status_to_response().
 *
 * @covers \Imagify\Imagifybeat\Actions::add_library_optimization_status_to_response
 *
 * @group  Imagifybeat
 */
class AddLibraryOptimizationStatusToResponseTest extends TestCase {
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
	 * Test: whether the media status is returned. When it is not, the response must be left untouched.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array $config   The user role, the media asked for, its lock state and what the browser sends.
	 * @param  bool  $expected Whether the media status is returned.
	 * @return void
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->switchToSubSite();

		$user_id     = $this->setSiteUser( $config['role'] );
		$other_id    = self::factory()->user->create( [ 'role' => 'author' ] );
		$own_media   = self::factory()->attachment->create(
			[
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
			]
		);
		$other_media = self::factory()->attachment->create(
			[
				'post_author'    => $other_id,
				'post_mime_type' => 'image/jpeg',
			]
		);
		$media_ids   = [
			'own'     => $own_media,
			'other'   => $other_media,
			'missing' => $other_media + 1000,
		];
		$media_id    = $media_ids[ $config['media'] ];

		if ( $config['locked'] ) {
			set_transient( sprintf( 'imagify_%1$s_%2$s_process_locked', 'wp', $other_media ), 1 );
		}

		$response = Actions::get_instance()->add_library_optimization_status_to_response(
			[],
			[ 'imagify_library_optimization_status' => [ 'wp' => [ '_' . $media_id => $config['seen_locked'] ] ] ]
		);

		if ( ! $expected ) {
			$this->assertSame( [], $response );
			return;
		}

		$this->assertArrayHasKey( 'wp_' . $media_id, $response['imagify_library_optimization_status'] );
	}
}
