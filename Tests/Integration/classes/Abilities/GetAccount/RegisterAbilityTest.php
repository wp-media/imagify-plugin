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
	 * Verifies that api_key is never exposed in the output.
	 *
	 * @return void
	 */
	public function testApiKeyIsAbsentFromOutput(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( self::ABILITY_ID );
		$result  = $ability->execute();

		$this->assertArrayNotHasKey( 'api_key', $result, 'api_key must not be exposed in the output.' );
	}

	/**
	 * Verifies all output fields have the types defined in the output schema.
	 * Without a valid API key the error-state values are returned, but types must still match.
	 *
	 * @return void
	 */
	public function testOutputFieldTypesMatchSchema(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( self::ABILITY_ID );
		$result  = $ability->execute();

		$this->assertIsString( $result['plan_label'] );
		$this->assertIsInt( $result['quota'] );
		$this->assertIsInt( $result['consumed_current_month_quota'] );
		$this->assertIsInt( $result['extra_quota'] );
		$this->assertIsInt( $result['extra_quota_consumed'] );
		$this->assertIsString( $result['next_date_update'] );
		$this->assertIsBool( $result['is_api_key_valid'] );
		$this->assertGreaterThanOrEqual( 0, $result['quota'] );
		$this->assertGreaterThanOrEqual( 0, $result['consumed_current_month_quota'] );
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
