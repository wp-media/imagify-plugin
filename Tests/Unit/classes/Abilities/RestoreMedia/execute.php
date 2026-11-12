<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::execute().
 *
 * Uses a testable subclass to avoid bootstrapping the full WP and Imagify
 * optimization layers. The subclass overrides `get_process()` and
 * `get_restored_size()` to inject controlled mock objects.
 *
 * @covers \Imagify\Abilities\RestoreMedia::execute
 * @group  RestoreMedia
 */
class Test_Execute extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		// MediaResolver builds translated WP_Error messages when no identifier resolves.
		Functions\stubTranslationFunctions();
	}


	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a testable RestoreMedia instance.
	 *
	 * @param mixed    $process       The process to return from get_process(), or null.
	 * @param int      $restored_size The size to return from get_restored_size().
	 * @return RestoreMedia
	 */
	private function make_ability( $process, int $restored_size = 0 ): RestoreMedia {
		return new class( $process, $restored_size ) extends RestoreMedia {
			/** @var mixed */
			private $process;
			/** @var int */
			private $size;

			public function __construct( $process, int $size ) {
				$this->process = $process;
				$this->size    = $size;
			}

			protected function get_process( int $media_id ) {
				return $this->process;
			}

			protected function get_restored_size( $process ): int {
				return $this->size;
			}
		};
	}

	/**
	 * Build a mock process object whose restore() returns the given value.
	 *
	 * @param mixed $return_value  What restore() should return.
	 * @param bool  $is_optimized  What get_data()->is_optimized() should return.
	 * @return object
	 */
	private function make_process( $return_value, bool $is_optimized = true ): object {
		return new class( $return_value, $is_optimized ) {
			/** @var mixed */
			private $return_value;
			/** @var bool */
			private $is_optimized;

			public function __construct( $return_value, bool $is_optimized ) {
				$this->return_value = $return_value;
				$this->is_optimized = $is_optimized;
			}

			public function get_data(): object {
				$flag = $this->is_optimized;
				return new class( $flag ) {
					/** @var bool */
					private $flag;

					public function __construct( bool $flag ) {
						$this->flag = $flag;
					}

					public function is_optimized(): bool {
						return $this->flag;
					}
				};
			}

			public function restore() {
				return $this->return_value;
			}
		};
	}

	// -------------------------------------------------------------------------
	// Scenario: invalid media_id
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when media_id is zero.
	 */
	public function testReturnsErrorWhenMediaIdIsZero(): void {
		$ability = $this->make_ability( null );
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns error when media_id is negative.
	 */
	public function testReturnsErrorWhenMediaIdIsNegative(): void {
		$ability = $this->make_ability( null );
		$result  = $ability->execute( [ 'media_id' => -5 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertNotEmpty( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns error when media_id is absent.
	 */
	public function testReturnsErrorWhenMediaIdAbsent(): void {
		$ability = $this->make_ability( null );
		$result  = $ability->execute( [] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: unknown media_id (no context resolves it)
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when neither context finds the media.
	 */
	public function testReturnsErrorWhenMediaNotFoundInAnyContext(): void {
		$ability = $this->make_ability( null );
		$result  = $ability->execute( [ 'media_id' => 99999 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertSame( 'Invalid media.', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: media not optimized
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns a clear error when the media is not optimized.
	 *
	 * Covers both "never optimized" and "already restored" cases — both share
	 * the same condition: get_data()->is_optimized() === false.
	 */
	public function testReturnsErrorWhenMediaNotOptimized(): void {
		$process = $this->make_process( true, false );
		$ability = $this->make_ability( $process );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertSame( 'This media is not optimized and cannot be restored.', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: no backup
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when restore() returns WP_Error 'no_backup'.
	 */
	public function testReturnsErrorWhenNoBackup(): void {
		$process = $this->make_process( new WP_Error( 'no_backup', 'This media has no backup file.' ) );
		$ability = $this->make_ability( $process );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertSame( 'This media has no backup file.', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: media locked
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when restore() returns WP_Error 'media_locked'.
	 */
	public function testReturnsErrorWhenMediaLocked(): void {
		$process = $this->make_process( new WP_Error( 'media_locked', 'This media is already being processed.' ) );
		$ability = $this->make_ability( $process );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertSame( 'This media is already being processed.', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: destination not writable
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns error when restore() returns WP_Error 'destination_not_writable'.
	 */
	public function testReturnsErrorWhenDestinationNotWritable(): void {
		$process = $this->make_process( new WP_Error( 'destination_not_writable', 'The image to replace is not writable.' ) );
		$ability = $this->make_ability( $process );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['restored_size'] );
		$this->assertSame( 'The image to replace is not writable.', $result['error_message'] );
	}

	// -------------------------------------------------------------------------
	// Scenario: success
	// -------------------------------------------------------------------------

	/**
	 * Tests that execute() returns success with restored_size when restore() returns true.
	 */
	public function testReturnsSuccessWithRestoredSize(): void {
		$process = $this->make_process( true );
		$ability = $this->make_ability( $process, 512000 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 512000, $result['restored_size'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns success even when file size is zero (e.g. unavailable).
	 */
	public function testReturnsSuccessWithZeroRestoredSizeWhenUnavailable(): void {
		$process = $this->make_process( true );
		$ability = $this->make_ability( $process, 0 );
		$result  = $ability->execute( [ 'media_id' => 42 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 0, $result['restored_size'] );
		$this->assertNull( $result['error_message'] );
	}
}
