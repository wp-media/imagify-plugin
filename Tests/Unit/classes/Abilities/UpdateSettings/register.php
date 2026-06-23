<?php

namespace Imagify\Tests\Unit\Abilities\UpdateSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Unit tests for UpdateSettings::register()
 *
 * @covers Imagify\Abilities\UpdateSettings::register()
 */
class RegisterTest extends TestCase {

	/**
	 * Test that register() calls wp_register_ability with correct parameters.
	 */
	public function test_register_calls_wp_register_ability() {
		$ability = new UpdateSettings();

		// Mock wp_register_ability.
		Functions\expect( 'function_exists' )
			->once()
			->with( 'wp_register_ability' )
			->andReturn( true );

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/update-settings',
				\Mockery::on( function( $args ) {
					return isset( $args['label'] ) && isset( $args['execute_callback'] ) &&
						isset( $args['permission_callback'] ) && isset( $args['meta'] );
				} )
			);

		$ability->register();
	}

	/**
	 * Test that register() handles missing wp_register_ability gracefully.
	 */
	public function test_register_gracefully_handles_missing_function() {
		$ability = new UpdateSettings();

		Functions\expect( 'function_exists' )
			->once()
			->with( 'wp_register_ability' )
			->andReturn( false );

		// Should not throw an error.
		$ability->register();
		$this->assertTrue( true );
	}
}
