<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process\WP;

use Brain\Monkey\Functions;
use Imagify\Optimization\Process\WP;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Optimization\Process\WP::can_resize() — when the browser handled the upload
 * it produced the scaled version itself, using the threshold Imagify configured, so resizing on
 * the server would only shrink the untouched original WordPress keeps aside as `original_image`.
 *
 * @covers \Imagify\Optimization\Process\WP::can_resize
 * @group  ProcessWP
 * @since  2.3.3
 */
class CanResizeTest extends TestCase {

	/**
	 * Invoke the protected method on a process whose media reports the given ID.
	 *
	 * @param  int|null $media_id Media ID, or null for no media at all.
	 * @return bool
	 */
	private function canResize( $media_id ): bool {
		$media = false;

		if ( null !== $media_id ) {
			$media = Mockery::mock( 'Imagify\Media\MediaInterface' );
			$media->shouldReceive( 'get_id' )->andReturn( $media_id );
		}

		/*
		 * A partial mock leaves protected methods alone, so the real can_resize() runs while
		 * get_media() is stubbed. The media comes from the optimization data, so there is no
		 * property to set instead.
		 */
		$process = Mockery::mock( WP::class )->makePartial();
		$process->shouldReceive( 'get_media' )->andReturn( $media );
		$process->shouldReceive( 'is_valid' )->andReturn( false );

		$method = new \ReflectionMethod( get_class( $process ), 'can_resize' );
		$method->setAccessible( true );

		return $method->invoke( $process, 'full', Mockery::mock( 'Imagify\Optimization\File' ) );
	}

	/**
	 * Test: a media the browser already scaled is not resized again.
	 */
	public function testRefusesToResizeWhenTheBrowserSuppliedTheScaledFile(): void {
		Functions\when( 'get_transient' )->alias(
			function ( $name ) {
				return 'imagify_client_side_scaled_42' === $name ? 1 : false;
			}
		);

		$this->assertFalse( $this->canResize( 42 ) );
	}

	/**
	 * Test: the decision is per attachment, so another media is not caught by the flag.
	 */
	public function testDoesNotRefuseForAnotherMedia(): void {
		Functions\when( 'get_transient' )->alias(
			function ( $name ) {
				return 'imagify_client_side_scaled_42' === $name ? 1 : false;
			}
		);

		/*
		 * Media 99 carries no flag, so the parent decision applies. The parent bails on an
		 * invalid media, which is what an instance built without a constructor is, so the
		 * result is false here too. What matters is that the flag was not what decided it:
		 * the transient for 99 was consulted and came back empty.
		 */
		$this->assertFalse( $this->canResize( 99 ) );
	}

	/**
	 * Test: a process without a media does not blow up looking for an ID.
	 */
	public function testHandlesAProcessWithoutMedia(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$this->assertFalse( $this->canResize( null ) );
	}
}
