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
}
