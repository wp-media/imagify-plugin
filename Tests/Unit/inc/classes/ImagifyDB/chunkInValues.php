<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifyDB;

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

		$this->stubEscapeFunctions();
	}

	/**
	 * Test chunk_in_values() against the configTestData fixture.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The test config (values and budget).
	 * @param array $expected The expected assertions to run.
	 */
	public function testShouldReturnExpectedChunks( $config, $expected ) {
		$chunks = Imagify_DB::chunk_in_values( $config['values'], $config['budget'] );

		if ( isset( $expected['chunk_count'] ) ) {
			$this->assertCount( $expected['chunk_count'], $chunks );
		}

		if ( isset( $expected['exact_chunks'] ) ) {
			$this->assertSame( $expected['exact_chunks'], $chunks );
		}

		$quoted = ! empty( $expected['quoted'] );

		if ( ! empty( $expected['max_rendered_length'] ) ) {
			foreach ( $chunks as $chunk ) {
				$this->assertLessThanOrEqual(
					$expected['max_rendered_length'],
					strlen( $this->render( $chunk, $quoted ) )
				);
			}
		}

		if ( ! empty( $expected['fully_packed'] ) ) {
			$budget = $expected['max_rendered_length'];
			$count  = count( $chunks );

			// Every chunk but the last must be full: the next chunk's first value
			// could not have been appended without going over the budget.
			for ( $i = 0; $i < $count - 1; $i++ ) {
				$next_value = $chunks[ $i + 1 ][0];
				$with_next  = $this->render( array_merge( $chunks[ $i ], [ $next_value ] ), $quoted );

				$this->assertGreaterThan(
					$budget,
					strlen( $with_next ),
					"Chunk $i is not filled up to the budget."
				);
			}
		}

		if ( isset( $expected['oversized_value'] ) ) {
			$found = false;

			foreach ( $chunks as $chunk ) {
				if ( in_array( $expected['oversized_value'], $chunk, true ) ) {
					$this->assertCount( 1, $chunk );
					$found = true;
				}
			}

			$this->assertTrue( $found );
		}

		if ( ! empty( $expected['preserves_order'] ) ) {
			$merged = $chunks ? array_merge( ...$chunks ) : [];
			$this->assertSame( $config['values'], $merged );
		}
	}

	/**
	 * Render a chunk the way it would appear inside an `IN ()` clause.
	 *
	 * @param  array $chunk  The chunk values.
	 * @param  bool  $quoted Whether the values are quoted (non integer values).
	 * @return string
	 */
	private function render( array $chunk, bool $quoted ): string {
		if ( ! $quoted ) {
			return implode( ',', $chunk );
		}

		return implode(
			',',
			array_map(
				function ( $value ) {
					return "'" . $value . "'";
				},
				$chunk
			)
		);
	}
}
