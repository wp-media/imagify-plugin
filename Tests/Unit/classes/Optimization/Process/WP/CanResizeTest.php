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
 * Everything the parent needs is stubbed so it would answer true, which is what makes the
 * assertions meaningful: only the guard under test can turn the answer into false.
 *
 * @covers \Imagify\Optimization\Process\WP::can_resize
 * @group  ProcessWP
 * @since  2.3.3
 */
class CanResizeTest extends TestCase {

	/**
	 * Invoke the protected method on a process whose media reports the given ID, with every
	 * condition the parent checks satisfied.
	 *
	 * @param  int|null $media_id Media ID, or null for no media at all.
	 * @return bool
	 */
	private function canResize( $media_id ): bool {
		$media = false;

		if ( null !== $media_id ) {
			$context = Mockery::mock( 'Imagify\Context\ContextInterface' );
			$context->shouldReceive( 'can_resize' )->andReturn( true );

			$media = Mockery::mock( 'Imagify\Media\MediaInterface' );
			$media->shouldReceive( 'get_id' )->andReturn( $media_id );
			$media->shouldReceive( 'get_context_instance' )->andReturn( $context );
		}

		$file = Mockery::mock( 'Imagify\Optimization\File' );
		$file->shouldReceive( 'is_image' )->andReturn( true );

		/*
		 * A partial mock leaves protected methods alone, so the real can_resize() runs while
		 * get_media() is stubbed. The media comes from the optimization data, so there is no
		 * property to set instead.
		 */
		$process = Mockery::mock( WP::class )->makePartial();
		$process->shouldReceive( 'get_media' )->andReturn( $media );
		// is_valid() is get_media() && get_media()->is_valid(), so it cannot be true without a media.
		$process->shouldReceive( 'is_valid' )->andReturn( null !== $media_id );

		$method = new \ReflectionMethod( get_class( $process ), 'can_resize' );
		$method->setAccessible( true );

		return $method->invoke( $process, 'full', $file );
	}

	/**
	 * Test: a media the browser already scaled is not resized again. Without the guard this
	 * returns true, since every condition the parent checks is satisfied.
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
	 * Test: an ordinary media is still resized, so the guard does not block everything.
	 */
	public function testStillResizesWhenTheBrowserDidNotScaleTheFile(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$this->assertTrue( $this->canResize( 42 ) );
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

		$this->assertTrue( $this->canResize( 99 ) );
	}

	/**
	 * Test: a process without a media does not blow up looking for an ID.
	 */
	public function testHandlesAProcessWithoutMedia(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$this->assertFalse( $this->canResize( null ) );
	}
}
