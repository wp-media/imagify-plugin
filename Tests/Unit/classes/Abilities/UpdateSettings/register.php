<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\UpdateSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\UpdateSettings::register().
 *
 * @covers \Imagify\Abilities\UpdateSettings::register
 * @group  UpdateSettings
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

		$ability = new UpdateSettings();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability once with the correct slug when
	 * the function exists, and that annotations contain readonly=false and idempotent=true.
	 */
	public function testRegistersWithCorrectSlugAndAnnotations(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/update-settings',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['annotations'] )
							&& false === ( $args['annotations']['readonly'] ?? true )
							&& true === ( $args['annotations']['idempotent'] ?? false );
					}
				)
			);

		$ability = new UpdateSettings();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability with meta.mcp.public=true.
	 */
	public function testRegistersWithMcpPublicTrue(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/update-settings',
				\Mockery::on(
					function ( $args ) {
						return true === ( $args['meta']['mcp']['public'] ?? false );
					}
				)
			);

		$ability = new UpdateSettings();
		$ability->register();
	}
}
