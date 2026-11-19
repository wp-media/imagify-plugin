<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GenerateMissingNextgen;

use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Stats\OptimizedMediaWithoutNextGen;
use Imagify\Tests\Integration\TestCase;

/**
 * Outside the network admin of a network-activated install the run excludes custom folders, so the
 * impact estimate ignores their (network-wide) files.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::get_impact_estimate
 * @group  Abilities
 */
class ImpactEstimateCheckPermissionsTest extends TestCase {

	protected $useApi = false;

	/**
	 * Backup file created by a test.
	 *
	 * @var string
	 */
	private $backup_path = '';

	public function tear_down() {
		if ( $this->backup_path && file_exists( $this->backup_path ) ) {
			wp_delete_file( $this->backup_path );
		}

		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: an optimized custom-folder file lacking a next-gen version counts only when the run covers custom folders.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()).
	 * @param array $expected The estimated count and total.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		update_imagify_option( 'optimization_format', 'webp' );

		$folder_id = \Imagify_Folders_DB::get_instance()->insert(
			[
				'path'   => '{{ROOT}}custom-folder/',
				'active' => 1,
			]
		);
		$file_id   = \Imagify_Files_DB::get_instance()->insert(
			[
				'folder_id' => $folder_id,
				'path'      => '{{ROOT}}custom-folder/photo.jpg',
				'mime_type' => 'image/jpeg',
				'status'    => 'success',
				'data'      => '',
			]
		);

		$this->assertGreaterThan( 0, $file_id );

		// A file only counts as missing a next-gen version when its backup exists.
		$this->backup_path = \Imagify_Custom_Folders::get_file_backup_path( \Imagify_Files_Scan::remove_placeholder( '{{ROOT}}custom-folder/photo.jpg' ) );
		wp_mkdir_p( dirname( $this->backup_path ) );
		file_put_contents( $this->backup_path, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		// Optimized library attachments keep `total` above the custom-folder count, so a leaked count is not clamped away.
		add_filter(
			'imagify_count_optimized_attachments',
			function () {
				return 5;
			}
		);

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$ability = new GenerateMissingNextgen( Bulk::get_instance(), OptimizedMediaWithoutNextGen::get_instance() );
		$impact  = $ability->get_impact_estimate( [] );

		$this->assertSame( $expected['count'], $impact['count'] );
		$this->assertSame( $expected['total'], $impact['total'] );
	}
}
