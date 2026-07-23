<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GetStats;

use Imagify\Tests\Integration\TestCase;

/**
 * @group Abilities
 */
class RegisterAbilityTest extends TestCase {

	protected $useApi = false;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		remove_all_filters( 'imagify_capacity' );
		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( 'imagify/get-stats' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute();

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when user lacks permission.' );
			return;
		}

		$this->assertIsArray( $result, 'Should return array when user has permission.' );

		foreach ( $expected['has_keys'] as $key ) {
			$this->assertArrayHasKey( $key, $result, "Result should contain key '{$key}'." );
		}
	}

	/**
	 * Tests that stats result fields have the correct types for all groups.
	 */
	public function testStatsFieldsHaveCorrectTypes(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/get-stats' );
		$result  = $ability->execute();

		foreach ( [ 'wp', 'custom-folders' ] as $group ) {
			$this->assertIsArray( $result[ $group ] );
			$this->assertIsInt( $result[ $group ]['count_optimized'] );
			$this->assertIsInt( $result[ $group ]['count_errors'] );
			$this->assertIsInt( $result[ $group ]['original_size'] );
			$this->assertIsInt( $result[ $group ]['optimized_size'] );
			$this->assertIsFloat( $result[ $group ]['savings_percent'] );
			$this->assertGreaterThanOrEqual( 0, $result[ $group ]['count_optimized'] );
			$this->assertGreaterThanOrEqual( 0, $result[ $group ]['count_errors'] );
			$this->assertGreaterThanOrEqual( 0, $result[ $group ]['original_size'] );
			$this->assertGreaterThanOrEqual( 0, $result[ $group ]['optimized_size'] );
		}
	}

	/**
	 * Test that the imagify_capacity filter is honoured for an administrator.
	 *
	 * An admin user would normally pass the permission check. When a filter
	 * replaces the resolved capacity with 'do_not_allow' (a reserved WordPress
	 * capability no user can be granted), the ability must return a WP_Error.
	 *
	 * @return void
	 */
	public function testShouldDenyAccessWhenCapacityFilterReturnsFalse(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		add_filter( 'imagify_capacity', static function () { return 'do_not_allow'; } );

		$ability = wp_get_ability( 'imagify/get-stats' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute();

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when imagify_capacity filter denies access.' );
	}

	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create( [
			'role' => $has_permission ? 'administrator' : 'subscriber',
		] );
		wp_set_current_user( $user_id );
	}
}
