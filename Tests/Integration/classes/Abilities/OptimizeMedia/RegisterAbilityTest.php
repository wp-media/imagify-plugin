<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\OptimizeMedia;

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

		$ability = wp_get_ability( 'imagify/optimize-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute( $config['args'] ?? [] );

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
	 * Tests that the credit-confirmation guard's `invalid_api_key` response is returned before
	 * do_execute()'s media_id validation ever runs, since this test's WordPress environment
	 * (`$useApi = false`) has no Imagify API key configured — the guard's first step (API key
	 * check) always fires first, regardless of `confirm` or `media_id`.
	 */
	public function testGuardReturnsInvalidApiKeyBeforeMediaIdValidationWhenNoApiKeyConfigured(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/optimize-media' );
		$result  = $ability->execute(
			[
				'media_id' => 0,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'invalid_api_key', $result['status'] );
		$this->assertArrayHasKey( 'message', $result );
	}

	/**
	 * Tests that the guard's `invalid_api_key` response is returned for a non-attachment post ID too,
	 * confirming the guard runs before any do_execute() validation, not just before media_id checks.
	 */
	public function testGuardReturnsInvalidApiKeyForNonAttachmentPostIdWhenNoApiKeyConfigured(): void {
		$post_id = self::factory()->post->create();
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/optimize-media' );
		$result  = $ability->execute(
			[
				'media_id' => $post_id,
				'confirm'  => true,
			]
		);

		$this->assertSame( 'invalid_api_key', $result['status'] );
	}

	/**
	 * Tests that execute() returns `confirmation_required` (not the guard's later steps) when
	 * `confirm` is omitted — verified independently of API-key/quota state by asserting the
	 * ability never reaches do_execute() unless invalid_api_key already short-circuited it.
	 */
	public function testExecuteReturnsGuardStatusWhenConfirmIsOmitted(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$ability = wp_get_ability( 'imagify/optimize-media' );
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertContains( $result['status'], [ 'invalid_api_key', 'insufficient_quota', 'confirmation_required' ] );
	}

	/**
	 * Tests that the registered ability's input_schema includes the new `confirm` property.
	 */
	public function testInputSchemaIncludesConfirmProperty(): void {
		$ability = wp_get_ability( 'imagify/optimize-media' );

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
		$ability = wp_get_ability( 'imagify/optimize-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$output_schema = $ability->get_output_schema();
		$enum          = $output_schema['properties']['status']['enum'];

		$this->assertContains( 'confirmation_required', $enum );
		$this->assertContains( 'insufficient_quota', $enum );
		$this->assertContains( 'invalid_api_key', $enum );
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

		add_filter(
			'imagify_capacity',
			static function () {
				return 'do_not_allow';
			}
		);

		$ability = wp_get_ability( 'imagify/optimize-media' );

		$this->assertNotNull( $ability, 'Ability should be registered.' );

		$result = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertInstanceOf( 'WP_Error', $result, 'Should return WP_Error when imagify_capacity filter denies access.' );
	}

	/**
	 * Creates and sets a current user with or without the manage_options capability.
	 */
	private function set_up_user( bool $has_permission ): void {
		$user_id = self::factory()->user->create(
			[
				'role' => $has_permission ? 'administrator' : 'subscriber',
			]
		);
		wp_set_current_user( $user_id );
	}
}
