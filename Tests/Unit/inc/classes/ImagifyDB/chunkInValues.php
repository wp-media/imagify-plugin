<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifyDB;

use Brain\Monkey\Functions;
use Imagify_DB;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for Imagify_DB::chunk_in_values().
 *
 * @covers Imagify_DB::chunk_in_values
 *
 * @group  ImagifyDB
 */
class Test_ChunkInValues extends TestCase {

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_sql' )->returnArg();
		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value ) {
				return $value;
			}
		);
	}

	/**
	 * An empty input should return an empty array of chunks.
	 */
	public function testShouldReturnEmptyArrayForEmptyInput() {
		$this->assertSame( [], Imagify_DB::chunk_in_values( [] ) );
	}

	/**
	 * When the whole list fits under the budget, only one chunk should be returned.
	 */
	public function testShouldReturnSingleChunkWhenUnderBudget() {
		$values = range( 1, 100 );

		$chunks = Imagify_DB::chunk_in_values( $values, 8000 );

		$this->assertCount( 1, $chunks );
		$this->assertSame( $values, $chunks[0] );
	}

	/**
	 * Integer IDs should be split into several chunks once the budget is exceeded,
	 * and no value should be lost or reordered across chunks.
	 */
	public function testShouldSplitIntegersIntoMultipleChunksWhenOverBudget() {
		// 1000 integer IDs, small budget forces several chunks.
		$values = range( 100000, 100999 );

		$chunks = Imagify_DB::chunk_in_values( $values, 100 );

		$this->assertGreaterThan( 1, count( $chunks ) );

		// Every chunk's rendered length must stay under (or at) the budget.
		foreach ( $chunks as $chunk ) {
			$rendered_length = strlen( implode( ',', $chunk ) );
			$this->assertLessThanOrEqual( 100, $rendered_length );
		}

		// No value lost, order preserved.
		$this->assertSame( $values, array_merge( ...$chunks ) );
	}

	/**
	 * Long quoted string values (e.g. file paths) should be split into several chunks
	 * once the rendered (quoted) length exceeds the budget.
	 */
	public function testShouldSplitLongStringValuesIntoMultipleChunks() {
		$values = [];

		for ( $i = 0; $i < 50; $i++ ) {
			$values[] = str_repeat( 'a', 300 ) . $i;
		}

		$chunks = Imagify_DB::chunk_in_values( $values, 1000 );

		$this->assertGreaterThan( 1, count( $chunks ) );

		foreach ( $chunks as $chunk ) {
			$rendered = implode(
				',',
				array_map(
					function ( $value ) {
						return "'" . $value . "'";
					},
					$chunk
				)
			);

			$this->assertLessThanOrEqual( 1000, strlen( $rendered ) );
		}

		$this->assertSame( $values, array_merge( ...$chunks ) );
	}

	/**
	 * A single value that alone exceeds the budget should still be returned,
	 * alone in its own chunk, rather than being dropped.
	 */
	public function testShouldKeepOversizedSingleValueAloneInItsOwnChunk() {
		$huge_value = str_repeat( 'x', 5000 );
		$values     = [ 1, $huge_value, 2 ];

		$chunks = Imagify_DB::chunk_in_values( $values, 100 );

		// The oversized value must still be present, alone in its own chunk.
		$found = false;

		foreach ( $chunks as $chunk ) {
			if ( in_array( $huge_value, $chunk, true ) ) {
				$this->assertCount( 1, $chunk );
				$found = true;
			}
		}

		$this->assertTrue( $found );
		$this->assertSame( $values, array_merge( ...$chunks ) );
	}

	/**
	 * An invalid budget (e.g. 0 or negative) should fall back to the default budget
	 * instead of producing a chunk per value.
	 */
	public function testShouldFallBackToDefaultBudgetWhenGivenInvalidValue() {
		$values = range( 1, 10 );
		$chunks = Imagify_DB::chunk_in_values( $values, 0 );

		$this->assertCount( 1, $chunks );
		$this->assertSame( $values, $chunks[0] );
	}
}
