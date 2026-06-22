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
	 * Tests that register() is a no-op when wp_register_ability is not available (WP < 6.9).
	 *
	 * Must run first — Brain Monkey cannot undefine a PHP function once stubbed, so this
	 * test must execute before any other test in this class stubs wp_register_ability.
	 */
	public function testNoOpsWhenWpRegisterAbilityNotAvailable(): void {
		// Do not stub wp_register_ability — function_exists() returns false here.
		$this->expectNotToPerformAssertions();

		$ability = new GetSettings();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability once with the correct slug when
	 * the function exists.
	 */
	public function testCallsWpRegisterAbilityWithCorrectSlugWhenFunctionExists(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-settings',
				\Mockery::type( 'array' )
			);

		$ability = new GetSettings();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability with execute_callback,
	 * permission_callback, and show_in_rest=true in the args array.
	 */
	public function testCallsWpRegisterAbilityWithRequiredCallbacksAndShowInRest(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-settings',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['execute_callback'] )
							&& isset( $args['permission_callback'] )
							&& isset( $args['meta']['show_in_rest'] )
							&& true === $args['meta']['show_in_rest'];
					}
				)
			);

		$ability = new GetSettings();
		$ability->register();
	}
}
