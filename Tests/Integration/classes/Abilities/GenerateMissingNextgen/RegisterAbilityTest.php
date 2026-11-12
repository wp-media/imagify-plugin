<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GenerateMissingNextgen;

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
		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( array $config, array $expected ): void {
		$this->set_up_user( $config['has_permission'] );

		$ability = wp_get_ability( 'imagify/generate-missing-nextgen' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		// Since finding 3's input_schema addition, the WP Abilities API validates the input
		// against `type: object` — an explicit empty array must be passed instead of no args.
		$result = $ability->execute( [] );

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when user lacks permission.' );
			return;
		}

		$this->assertIsArray( $result, 'Should return array when user has permission.' );

		foreach ( $expected['has_keys'] as $key ) {
			$this->assertArrayHasKey( $key, $result, "Result should contain key '{$key}'." );
		}
	}

	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create(
			[
				'role' => $has_permission ? 'administrator' : 'subscriber',
			]
		);
		wp_set_current_user( $user_id );
	}

	/**
	 * Tests that the registered ability's input_schema includes the new `confirm` property
	 * (finding 3 — GenerateMissingNextgen had no input_schema at all before this issue).
	 */
	public function testInputSchemaIncludesConfirmProperty(): void {
		$ability = wp_get_ability( 'imagify/generate-missing-nextgen' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$input_schema = $ability->get_input_schema();

		$this->assertArrayHasKey( 'confirm', $input_schema['properties'] );
		$this->assertSame( 'boolean', $input_schema['properties']['confirm']['type'] );
	}

	/**
	 * Tests that the registered ability's output_schema status enum includes the new
	 * guard-produced status values.
	 */
	public function testOutputSchemaStatusEnumIncludesGuardStatuses(): void {
		$ability = wp_get_ability( 'imagify/generate-missing-nextgen' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$output_schema = $ability->get_output_schema();
		$enum          = $output_schema['properties']['status']['enum'];

		$this->assertContains( 'confirmation_required', $enum );
		$this->assertContains( 'insufficient_quota', $enum );
		$this->assertContains( 'invalid_api_key', $enum );
	}

	/**
	 * Tests that the credit-confirmation guard's `invalid_api_key` response is returned before
	 * do_execute() ever runs, since this test's WordPress environment (`$useApi = false`) has no
	 * Imagify API key configured — the guard's first step (API key check) always fires first,
	 * regardless of `confirm`.
	 */
	public function testGuardReturnsInvalidApiKeyWhenNoApiKeyConfigured(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/generate-missing-nextgen' );
		$result  = $ability->execute( [ 'confirm' => true ] );

		$this->assertSame( 'invalid_api_key', $result['status'] );
	}
}
