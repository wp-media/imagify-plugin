<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GenerateMissingNextgen;

use Imagify\Tests\Integration\TestCase;

/**
 * Every context the generation runs on must be gated by its `bulk-optimize`
 * capability, as the legacy AJAX handler does.
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

		$this->assertInstanceOf( 'WP_Error', $this->generate() );
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

		$this->assertIsArray( $this->generate() );
	}

	public function testShouldAllowAdminOnSingleSite(): void {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertIsArray( $this->generate() );
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

		$this->assertInstanceOf( 'WP_Error', $this->generate() );
	}

	/**
	 * Calls the ability.
	 *
	 * @return mixed
	 */
	private function generate() {
		return wp_get_ability( 'imagify/generate-missing-nextgen' )->execute( [] );
	}
}
