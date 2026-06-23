<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetAccount;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetAccount;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetAccount::register().
 *
 * @covers \Imagify\Abilities\GetAccount::register
 * @group  GetAccount
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

		$ability = new GetAccount();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability once with the correct slug when
	 * the function exists.
	 */
	public function testCallsWpRegisterAbilityWithCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-account',
				\Mockery::type( 'array' )
			);

		$ability = new GetAccount();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability with the required callbacks
	 * and the meta flags needed for REST and MCP exposure.
	 */
	public function testCallsWpRegisterAbilityWithRequiredCallbacksAndMetaFlags(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-account',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['execute_callback'] )
							&& isset( $args['permission_callback'] )
							&& true === ( $args['meta']['show_in_rest'] ?? false )
							&& true === ( $args['meta']['mcp']['public'] ?? false );
					}
				)
			);

		$ability = new GetAccount();
		$ability->register();
	}
}
