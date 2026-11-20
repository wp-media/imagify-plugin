<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Media\CustomFolders;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::get_process().
 *
 * @covers \Imagify\Abilities\RestoreMedia::get_process
 * @group  MCP
 */
class Test_GetProcess extends TestCase {

	/**
	 * Invokes the protected get_process() method.
	 *
	 * @param int $media_id The attachment ID.
	 * @return mixed
	 */
	private function invoke_get_process( int $media_id ) {
		$method = $this->get_reflective_method( 'get_process', RestoreMedia::class );

		return $method->invoke( new RestoreMedia(), $media_id );
	}

	/**
	 * Stubs the wp context so the current user does or does not hold `manage`.
	 *
	 * @param bool $can_manage Whether the current user holds `manage`.
	 */
	private function stub_manage( bool $can_manage ): void {
		Functions\when( 'imagify_get_context' )->justReturn(
			new class( $can_manage ) {
				private $can_manage;

				public function __construct( bool $can_manage ) {
					$this->can_manage = $can_manage;
				}

				public function current_user_can( $describer ): bool {
					return $this->can_manage && 'manage' === $describer;
				}
			}
		);
	}

	/**
	 * Builds a custom-folders process stub.
	 *
	 * @param array $row The media row; empty when the file is not in the table.
	 * @return object
	 */
	private function make_custom_folders_process( array $row ): object {
		$media = Mockery::mock( CustomFolders::class );
		$media->shouldReceive( 'get_row' )->andReturn( $row );

		return new class( $media ) {
			private $media;

			public function __construct( $media ) {
				$this->media = $media;
			}

			public function get_media() {
				return $this->media;
			}
		};
	}

	/**
	 * Tests that an attachment resolves to a process in the wp context.
	 */
	public function testReturnsWpProcessForAttachment(): void {
		$process = new \stdClass();

		Functions\when( 'get_post_type' )->justReturn( 'attachment' );
		Functions\expect( 'imagify_get_optimization_process' )
			->once()
			->with( 123, 'wp' )
			->andReturn( $process );

		$this->assertSame( $process, $this->invoke_get_process( 123 ) );
	}

	/**
	 * Tests that a non-attachment falls back to a valid custom-folders process.
	 */
	public function testReturnsCustomFoldersProcessWhenValid(): void {
		$process = $this->make_custom_folders_process( [ 'path' => '{{ROOT}}photo.jpg' ] );

		$this->stub_manage( true );
		Functions\when( 'get_post_type' )->justReturn( false );
		Functions\expect( 'imagify_get_optimization_process' )
			->once()
			->with( 456, 'custom-folders' )
			->andReturn( $process );

		$this->assertSame( $process, $this->invoke_get_process( 456 ) );
	}

	/**
	 * Tests that null is returned when neither context recognises the media.
	 */
	public function testReturnsNullWhenNoContextRecognisesMedia(): void {
		$process = $this->make_custom_folders_process( [] );

		$this->stub_manage( true );
		Functions\when( 'get_post_type' )->justReturn( false );
		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$this->assertNull( $this->invoke_get_process( 789 ) );
	}

	/**
	 * Tests that a caller without `manage` never reaches the custom-folders table.
	 */
	public function testReturnsNullForSiteOnlyCallerOnNonAttachment(): void {
		$this->stub_manage( false );
		Functions\when( 'get_post_type' )->justReturn( false );
		Functions\expect( 'imagify_get_optimization_process' )->never();

		$this->assertNull( $this->invoke_get_process( 456 ) );
	}

	/**
	 * Tests that a valid custom-folders media without a row is unknown: any positive ID is "valid" there.
	 */
	public function testReturnsNullWhenCustomFoldersMediaHasNoRow(): void {
		$this->stub_manage( true );
		Functions\when( 'get_post_type' )->justReturn( false );
		Functions\when( 'imagify_get_optimization_process' )->justReturn( $this->make_custom_folders_process( [] ) );

		$this->assertNull( $this->invoke_get_process( 999999 ) );
	}
}
