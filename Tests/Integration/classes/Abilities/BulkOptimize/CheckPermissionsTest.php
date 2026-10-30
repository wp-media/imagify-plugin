<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\BulkOptimize;

use Imagify\Tests\Integration\TestCase;

/**
 * The custom-folders context must be gated by the custom-folders capability,
 * as the legacy bulk AJAX handler does.
 *
 * @covers \Imagify\Abilities\BulkOptimize::check_permissions
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
	 * Test: the ability is denied (WP_Error) or runs (array) for the given site type, user and context.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()), whether the admin is a super admin, whether
	 *                        `imagify_capacity` denies custom folders, and the ability input.
	 * @param array $expected Whether the call is denied.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

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

		$result = wp_get_ability( 'imagify/bulk-optimize' )->execute( $config['args'] );

		if ( $expected['is_denied'] ) {
			$this->assertInstanceOf( 'WP_Error', $result );
			return;
		}

		$this->assertIsArray( $result );
	}
}
