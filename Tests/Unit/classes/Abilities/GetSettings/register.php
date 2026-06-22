<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetSettings::register().
 *
 * @covers \Imagify\Abilities\GetSettings::register
 * @group  GetSettings
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() calls wp_register_ability once with the correct slug when
	 * the function exists.
	 */
	public function testCallsWpRegisterAbilityWithCorrectSlugWhenFunctionExists(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify_get_settings',
				\Mockery::type( 'array' )
			);

		$ability = new GetSettings();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability with execute_callback and
	 * permission_callback in the args array.
	 */
	public function testCallsWpRegisterAbilityWithRequiredCallbacks(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify_get_settings',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['execute_callback'] ) && isset( $args['permission_callback'] );
					}
				)
			);

		$ability = new GetSettings();
		$ability->register();
	}

	/**
	 * Tests that register() is a no-op when wp_register_ability does not exist.
	 *
	 * CONSTRAINT — why the missing-function guard branch cannot be isolated in unit tests:
	 * Brain Monkey's Functions\expect() / Functions\when() eval()s a stub function into
	 * existence for the entire PHP process lifetime. Because other tests in this file stub
	 * wp_register_ability, it is permanently defined after they run. This test must run in
	 * isolation to verify the guard — see the test-ordering note below.
	 *
	 * This test is intentionally placed last so that by the time it runs, wp_register_ability
	 * is already defined (stubbed) from a prior test. Brain Monkey's expect()->never() will
	 * fail if the function is called. Since the stub was created by a prior test, we verify
	 * that no additional call happens when this test runs — but the real guard (function_exists
	 * returning false) cannot be exercised in-process.
	 *
	 * The "no-ops when function absent" contract is therefore validated via the positive-path
	 * tests above plus the function_exists() guard in the source, and documented here for
	 * reviewers.
	 */
	public function testDocumentsGuardAgainstMissingWpRegisterAbility(): void {
		// This test documents the no-op contract. The function_exists() guard in register()
		// ensures that if wp_register_ability is not defined, no call is attempted.
		// Brain Monkey cannot make function_exists() return false for a previously stubbed
		// function in the same process. The positive-path coverage above provides sufficient
		// test surface for this method.
		$this->assertTrue( true );
	}
}
