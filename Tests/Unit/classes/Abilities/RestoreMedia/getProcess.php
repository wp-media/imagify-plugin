<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;
use ReflectionMethod;

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
		$method = new ReflectionMethod( RestoreMedia::class, 'get_process' );
		$method->setAccessible( true );

		return $method->invoke( new RestoreMedia(), $media_id );
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
		$process = new class {
			public function is_valid(): bool {
				return true;
			}
		};

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
		$process = new class {
			public function is_valid(): bool {
				return false;
			}
		};

		Functions\when( 'get_post_type' )->justReturn( false );
		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$this->assertNull( $this->invoke_get_process( 789 ) );
	}
}
