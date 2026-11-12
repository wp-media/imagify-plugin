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

	/**
	 * Data provider for testShouldUseTheLayoutOfTheScreen().
	 *
	 * @return array
	 */
	public static function layoutData(): array {
		return [
			'Edit Media screen: compact layout' => [ 'attachment', false ],
			'Media Library: list layout'        => [ 'upload', true ],
			'No screen sent: list layout'       => [ '', true ],
		];
	}

	/**
	 * Test: the optimization data keeps the layout of the screen the request comes from, so the Edit Media panel
	 * looks the same after an in-place optimization as after a reload.
	 *
	 * @dataProvider layoutData
	 *
	 * @param  string $screen_id       The screen ID sent by the browser.
	 * @param  bool   $has_list_layout Whether the Media Library list layout is expected.
	 * @return void
	 */
	public function testShouldUseTheLayoutOfTheScreen( string $screen_id, bool $has_list_layout ): void {
		$this->switchToSubSite();

		$user_id  = $this->setSiteUser( 'administrator' );
		$media_id = self::factory()->attachment->create(
			[
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
			]
		);

		update_post_meta( $media_id, '_wp_attached_file', 'imagify-layout.jpg' );
		update_post_meta( $media_id, '_wp_attachment_metadata', [ 'file' => 'imagify-layout.jpg' ] );
		update_post_meta( $media_id, '_imagify_status', 'success' );
		update_post_meta(
			$media_id,
			'_imagify_data',
			[
				'sizes' => [
					'full' => [
						'success'        => true,
						'original_size'  => 1000,
						'optimized_size' => 800,
						'percent'        => 20.0,
					],
				],
				'stats' => [
					'original_size'  => 1000,
					'optimized_size' => 800,
					'percent'        => 20.0,
				],
			]
		);

		// The browser saw the media as locked, but it is not anymore: its status changed.
		$response = Actions::get_instance()->add_library_optimization_status_to_response(
			[],
			[ 'imagify_library_optimization_status' => [ 'wp' => [ '_' . $media_id => 1 ] ] ],
			$screen_id
		);
		$html     = $response['imagify_library_optimization_status'][ 'wp_' . $media_id ];

		$this->assertStringContainsString( 'Original Saving', $html );

		if ( $has_list_layout ) {
			$this->assertStringContainsString( 'imagify-datas-list', $html );
		} else {
			$this->assertStringNotContainsString( 'imagify-datas-list', $html );
		}

		// The layout must not leak into the next requests handled by the same PHP process.
		$this->assertFalse( has_filter( 'imagify_is_media_page', '__return_true' ) );
	}
}
