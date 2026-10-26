<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\BulkOptimize;

use Imagify\Tests\Integration\TestCase;

/**
 * The custom-folders context must be gated by the custom-folders capability,
 * as the legacy bulk AJAX handler does.
 *
 * @group Abilities
 */
class CustomFoldersPermissionTest extends TestCase {

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
	 * @group ms-required
	 */
	public function testShouldDenySiteAdminOnMultisite(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$ability = wp_get_ability( 'imagify/bulk-optimize' );

		$this->assertInstanceOf( 'WP_Error', $ability->execute( [ 'context' => 'custom-folders' ] ) );
		$this->assertIsArray( $ability->execute( [ 'context' => 'wp' ] ), 'The media library stays available to a site admin.' );
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

		$this->assertIsArray( wp_get_ability( 'imagify/bulk-optimize' )->execute( [ 'context' => 'custom-folders' ] ) );
	}

	public function testShouldAllowAdminOnSingleSite(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertIsArray( wp_get_ability( 'imagify/bulk-optimize' )->execute( [ 'context' => 'custom-folders' ] ) );
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

		$ability = wp_get_ability( 'imagify/bulk-optimize' );

		$this->assertInstanceOf( 'WP_Error', $ability->execute( [ 'context' => 'custom-folders' ] ) );
		$this->assertIsArray( $ability->execute( [ 'context' => 'wp' ] ) );
	}
}
