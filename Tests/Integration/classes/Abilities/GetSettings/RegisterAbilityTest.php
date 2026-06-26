<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GetSettings;

use Imagify\Tests\Integration\TestCase;

/**
 * Integration tests for imagify/get-settings ability registration and output.
 *
 * @group Abilities
 */
class RegisterAbilityTest extends TestCase {

	/**
	 * @var bool
	 */
	protected $useApi = false;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	public function tear_down() {
		wp_set_current_user( 0 );
		remove_all_filters( 'imagify_capacity' );
		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected result.
	 *
	 * @return void
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( 'imagify/get-settings' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute();

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when user lacks permission.' );
			return;
		}

		$this->assertIsArray( $result, 'Should return array when user has permission.' );
		$this->assertArrayNotHasKey( 'api_key', $result, 'api_key must be stripped from response.' );
		$this->assertArrayNotHasKey( 'version', $result, 'version must be stripped from response.' );

		foreach ( $expected['has_keys'] as $key ) {
			$this->assertArrayHasKey( $key, $result, "Result should contain key '{$key}'." );
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

		$ability = wp_get_ability( 'imagify/get-settings' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute();

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when imagify_capacity filter denies access.' );
	}

	/**
	 * Set up user with or without permission.
	 *
	 * @param bool $has_permission Whether user should have manage_options capability.
	 *
	 * @return void
	 */
	private function set_up_user( bool $has_permission ): void {
		if ( $has_permission ) {
			$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		} else {
			$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		}

		wp_set_current_user( $user_id );
	}
}
