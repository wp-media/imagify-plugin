<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk\WP;

use Brain\Monkey\Functions;
use Imagify\Bulk\WP;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for the private \Imagify\Bulk\WP::get_original_file_path_from_metadata().
 *
 * Backups are always written at the path derived from the true original file
 * (`wp_get_original_image_path()`), while the bulk queries derive `$file_path` from
 * `_wp_attached_file`, which points to the WP-scaled copy when one exists. This helper mirrors
 * the original-file derivation from already-batched `_wp_attachment_metadata`, so the bulk
 * "has backup" checks stop looking for a backup file that never existed for scaled uploads.
 *
 * @covers \Imagify\Bulk\WP::get_original_file_path_from_metadata
 * @group  Bulk
 */
class GetOriginalFilePathFromMetadataTest extends TestCase {

	/**
	 * Regression guard for #1210: a WP-scaled image must resolve its backup path from the
	 * true original file, not from the scaled path stored in `_wp_attached_file`.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test config: `file_path` and `metadata`.
	 * @param array $expected Expected `result`.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		Functions\when( 'trailingslashit' )->alias(
			function ( $path ) {
				return rtrim( $path, '/\\' ) . '/';
			}
		);

		$wp = ( new \ReflectionClass( WP::class ) )->newInstanceWithoutConstructor();

		$method = new \ReflectionMethod( WP::class, 'get_original_file_path_from_metadata' );
		$method->setAccessible( true );

		$result = $method->invoke( $wp, $config['file_path'], $config['metadata'] );

		$this->assertSame( $expected['result'], $result );
	}
}
