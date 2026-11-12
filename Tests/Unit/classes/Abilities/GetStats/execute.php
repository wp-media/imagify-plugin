<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetStats;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetStats;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GetStats::execute().
 *
 * @covers \Imagify\Abilities\GetStats::execute
 * @group  MCP
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() fires the imagify_mcp_ability_executed action.
	 */
	public function testFiresAbilityExecutedHook(): void {
		$this->stubStatFunctions( 0, 0, 0, 0, 0.0 );
		$this->stubFilesStatsMethods( 0, 0, 0, 0 );
		Actions\expectDone( 'imagify_mcp_ability_executed' )->once();

		( new GetStats() )->execute();
	}

	/**
	 * Tests that execute() returns the expected top-level array structure.
	 */
	public function testReturnsCorrectStructure(): void {
		$this->stubStatFunctions( 0, 0, 0, 0, 0.0 );
		$this->stubFilesStatsMethods( 0, 0, 0, 0 );

		$result = ( new GetStats() )->execute();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'wp', $result );
		$this->assertArrayHasKey( 'custom-folders', $result );
	}

	/**
	 * Tests that the wp context contains the expected keys and values.
	 */
	public function testWpContextContainsCorrectKeys(): void {
		$this->stubStatFunctions( 10, 2, 5000, 4000, 20.0 );
		$this->stubFilesStatsMethods( 0, 0, 0, 0 );

		$result = ( new GetStats() )->execute();

		$this->assertSame( 10, $result['wp']['count_optimized'] );
		$this->assertSame( 2, $result['wp']['count_errors'] );
		$this->assertSame( 5000, $result['wp']['original_size'] );
		$this->assertSame( 4000, $result['wp']['optimized_size'] );
		$this->assertSame( 20.0, $result['wp']['savings_percent'] );
	}

	/**
	 * Tests that the custom-folders context contains the expected keys and values.
	 */
	public function testCustomFoldersContextContainsCorrectKeys(): void {
		$this->stubStatFunctions( 0, 0, 0, 0, 0.0 );
		$this->stubFilesStatsMethods( 5, 1, 8000, 6000 );

		$result = ( new GetStats() )->execute();

		$this->assertSame( 5, $result['custom-folders']['count_optimized'] );
		$this->assertSame( 1, $result['custom-folders']['count_errors'] );
		$this->assertSame( 8000, $result['custom-folders']['original_size'] );
		$this->assertSame( 6000, $result['custom-folders']['optimized_size'] );
		$this->assertSame( 25.0, $result['custom-folders']['savings_percent'] );
	}

	/**
	 * Tests that savings_percent for custom-folders is 0.0 when original_size is 0.
	 */
	public function testCustomFoldersSavingsPercentIsZeroWhenOriginalSizeIsZero(): void {
		$this->stubStatFunctions( 0, 0, 0, 0, 0.0 );
		$this->stubFilesStatsMethods( 0, 0, 0, 0 );

		$result = ( new GetStats() )->execute();

		$this->assertSame( 0.0, $result['custom-folders']['savings_percent'] );
	}

	/**
	 * Set up Brain\Monkey stubs for the WP-context stat functions.
	 *
	 * @param int   $optimized  Return value for imagify_count_optimized_attachments.
	 * @param int   $errors     Return value for imagify_count_error_attachments.
	 * @param int   $orig_size  Return value for imagify_count_saving_data('original_size').
	 * @param int   $opt_size   Return value for imagify_count_saving_data('optimized_size').
	 * @param float $percent    Return value for imagify_count_saving_data('percent').
	 */
	private function stubStatFunctions( int $optimized, int $errors, int $orig_size, int $opt_size, float $percent ): void {
		Functions\when( 'imagify_count_optimized_attachments' )->justReturn( $optimized );
		Functions\when( 'imagify_count_error_attachments' )->justReturn( $errors );
		Functions\when( 'imagify_count_saving_data' )->alias(
			function ( $key ) use ( $orig_size, $opt_size, $percent ) {
				$map = [
					'original_size'  => $orig_size,
					'optimized_size' => $opt_size,
					'percent'        => $percent,
				];
				return isset( $map[ $key ] ) ? $map[ $key ] : 0;
			}
		);
	}

	/**
	 * Stub Imagify_Files_Stats static method calls via a Mockery alias.
	 *
	 * @param int $optimized   count_optimized_files return value.
	 * @param int $errors      count_error_files return value.
	 * @param int $orig_size   get_original_size return value.
	 * @param int $opt_size    get_optimized_size return value.
	 */
	private function stubFilesStatsMethods( int $optimized, int $errors, int $orig_size, int $opt_size ): void {
		$mock = Mockery::mock( 'alias:Imagify_Files_Stats' );
		$mock->shouldReceive( 'count_optimized_files' )->andReturn( $optimized );
		$mock->shouldReceive( 'count_error_files' )->andReturn( $errors );
		$mock->shouldReceive( 'get_original_size' )->andReturn( $orig_size );
		$mock->shouldReceive( 'get_optimized_size' )->andReturn( $opt_size );
	}
}
