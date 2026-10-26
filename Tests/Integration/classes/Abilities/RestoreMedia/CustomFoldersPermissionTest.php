<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\RestoreMedia;

use Imagify\Tests\Integration\TestCase;

/**
 * Restoring a custom-folder file must be gated by the custom-folders
 * capability, as the legacy AJAX restore handler does.
 *
 * @group Abilities
 */
class CustomFoldersPermissionTest extends TestCase {

	protected $useApi = false;

	/**
	 * ID of the custom-folder file created for the test.
	 *
	 * @var int
	 */
	private $file_id;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}

		$folder_id     = \Imagify_Folders_DB::get_instance()->insert(
			[
				'path'   => '{{ROOT}}custom-folder/',
				'active' => 1,
			]
		);
		$this->file_id = \Imagify_Files_DB::get_instance()->insert(
			[
				'folder_id' => $folder_id,
				'path'      => '{{ROOT}}custom-folder/photo.jpg',
			]
		);

		$this->assertGreaterThan( 0, $this->file_id );
		$this->assertNotSame( 'attachment', get_post_type( $this->file_id ), 'The file ID must not collide with an attachment.' );
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * @group ms-required
	 */
	public function testShouldDenySiteAdminOnMultisite(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertInstanceOf( 'WP_Error', $this->restore() );
	}

	/**
	 * @group ms-required
	 */
	public function testShouldAllowSuperAdminOnMultisite(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$this->assertReachesRestoreLogic( $this->restore() );
	}

	public function testShouldAllowAdminOnSingleSite(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertReachesRestoreLogic( $this->restore() );
	}

	public function testShouldHonorImagifyCapacityFilter(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		add_filter(
			'imagify_capacity',
			function ( $capacity, $describer, $context ) {
				return 'custom-folders' === $context ? 'do_not_allow' : $capacity;
			},
			10,
			3
		);

		$this->assertInstanceOf( 'WP_Error', $this->restore() );
	}

	/**
	 * Restores the test file through the ability.
	 *
	 * @return mixed
	 */
	private function restore() {
		return wp_get_ability( 'imagify/restore-media' )->execute( [ 'media_id' => $this->file_id ] );
	}

	/**
	 * The file is not optimized, so a permitted call fails on that, past the permission check.
	 *
	 * @param mixed $result Result of the ability call.
	 * @return void
	 */
	private function assertReachesRestoreLogic( $result ): void {
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'not optimized', $result['error_message'] );
	}
}
