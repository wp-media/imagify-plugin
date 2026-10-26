<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GenerateMissingNextgen;

use Imagify\Tests\Integration\TestCase;

/**
 * Every context the generation runs on must be gated by its `bulk-optimize`
 * capability, as the legacy AJAX handler does.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::check_permissions
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
	 * Test: generating on both contexts is denied (WP_Error) or runs (array) for the given
	 * site type and user.
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

		// Run on both contexts, whoever the current user is.
		\Imagify_Folders_DB::get_instance()->insert(
			[
				'path'   => '{{ROOT}}custom-folder/',
				'active' => 1,
			]
		);
		add_filter(
			'imagify_bulk_page_types',
			function () {
				return [
					'library|wp'                    => 1,
					'custom-folders|custom-folders' => 1,
				];
			}
		);

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

		$result = wp_get_ability( 'imagify/generate-missing-nextgen' )->execute( [] );

		if ( $expected['is_denied'] ) {
			$this->assertInstanceOf( 'WP_Error', $result );
			return;
		}

		$this->assertIsArray( $result );
	}
}
