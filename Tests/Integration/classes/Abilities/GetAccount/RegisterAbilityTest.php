<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GetAccount;

use Imagify\Tests\Integration\TestCase;

/**
 * Integration tests for imagify/get-account ability registration and permissions.
 *
 * @group Abilities
 */
class RegisterAbilityTest extends TestCase {

	/**
	 * Minimum WordPress version required for the Abilities API.
	 */
	private const MIN_WP_VERSION = '6.9';

	/**
	 * Ability ID.
	 */
	private const ABILITY_ID = 'imagify/get-account';

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
		global $wp_version;

		parent::set_up();

		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			$this->markTestSkipped( 'WordPress Abilities API requires WordPress ' . self::MIN_WP_VERSION . ' or higher.' );
		}
	}

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	public function tear_down() {
		wp_set_current_user( 0 );
		remove_filter( 'imagify_capacity', '__return_false' );
		parent::tear_down();
	}

	/**
	 * Test ability registration and permission scenarios.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected result.
	 *
	 * @return void
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( self::ABILITY_ID );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute( $config['args'] ?? null );

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when user lacks permission.' );
		} else {
			$this->assertIsArray( $result, 'Should return array when user has permission.' );
		}
	}

	/**
	 * Test that the imagify_capacity filter is honoured for an administrator.
	 *
	 * An admin user would normally pass the permission check. When a filter
	 * forces the resolved capacity to an empty string (denied), the ability
	 * must return a WP_Error.
	 *
	 * @return void
	 */
	public function testShouldDenyAccessWhenCapacityFilterReturnsFalse(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		add_filter( 'imagify_capacity', '__return_false' );

		$ability = wp_get_ability( self::ABILITY_ID );

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
