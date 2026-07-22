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
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpectedChunks( $config, $expected ) {
		$chunks = Imagify_DB::chunk_in_values( $config['values'], $config['budget'] );

		if ( isset( $expected['chunk_count'] ) ) {
			$this->assertCount( $expected['chunk_count'], $chunks );
		}

		if ( isset( $expected['min_chunks'] ) ) {
			$this->assertGreaterThanOrEqual( $expected['min_chunks'], count( $chunks ) );
		}

		if ( ! empty( $expected['max_rendered_length'] ) ) {
			foreach ( $chunks as $chunk ) {
				$rendered = empty( $expected['quoted'] )
					? implode( ',', $chunk )
					: implode(
						',',
						array_map(
							function ( $value ) {
								return "'" . $value . "'";
							},
							$chunk
						)
					);

				$this->assertLessThanOrEqual( $expected['max_rendered_length'], strlen( $rendered ) );
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
}
