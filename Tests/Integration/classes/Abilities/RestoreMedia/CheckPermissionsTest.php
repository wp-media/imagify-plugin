<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\RestoreMedia;

use Imagify\Tests\Integration\TestCase;

/**
 * Restoring a custom-folder file must be gated by the custom-folders
 * capability, as the legacy AJAX restore handler does.
 *
 * @covers \Imagify\Abilities\RestoreMedia::check_permissions
 * @group  Abilities
 */
class CheckPermissionsTest extends TestCase {

	protected $useApi = false;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: restoring a custom-folder file is denied (WP_Error) or reaches the restore logic
	 * for the given site type and user.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required, whether the admin is a super admin, and
	 *                        whether `imagify_capacity` denies custom folders.
	 * @param array $expected Whether the call is denied.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		if ( 'multisite' === $config['site'] && ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		if ( 'single' === $config['site'] && is_multisite() ) {
			$this->markTestSkipped( 'Single site only.' );
		}

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
			]
		);

		$this->assertGreaterThan( 0, $file_id );
		$this->assertNotSame( 'attachment', get_post_type( $file_id ), 'The file ID must not collide with an attachment.' );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( $config['super_admin'] && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		if ( $config['deny_custom_folders_capacity'] ) {
			add_filter(
				'imagify_capacity',
				function ( $capacity, $describer, $context ) {
					return 'custom-folders' === $context ? 'do_not_allow' : $capacity;
				},
				10,
				3
			);
		}

		$result = wp_get_ability( 'imagify/restore-media' )->execute( [ 'media_id' => $file_id ] );

		if ( $expected['is_denied'] ) {
			$this->assertInstanceOf( 'WP_Error', $result );
			return;
		}

		// The file is not optimized, so a permitted call fails on that, past the permission check.
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
		$this->assertStringContainsString( 'not optimized', $result['error_message'] );
	}
}
