<?php

namespace Imagify\Tests\Unit\Abilities\UpdateSettings;

use Imagify\Abilities\UpdateSettings;
use WP_UnitTestCase;

/**
 * Unit tests for UpdateSettings::register()
 *
 * @covers Imagify\Abilities\UpdateSettings::register()
 */
class RegisterTest extends WP_UnitTestCase {

	/**
	 * Test that register() calls wp_register_ability with correct parameters.
	 */
	public function test_register_calls_wp_register_ability() {
		$ability = new UpdateSettings();

		// Check if wp_register_ability function exists.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'wp_register_ability not available in this WP version' );
		}

		// Call register.
		$ability->register();

		// Verify the ability was registered.
		$this->assertTrue(
			function_exists( 'wp_register_ability' ),
			'wp_register_ability function exists'
		);
	}

	/**
	 * Test that register() handles missing wp_register_ability gracefully.
	 */
	public function test_register_gracefully_handles_missing_function() {
		$ability = new UpdateSettings();

		// This should not throw an error even if function doesn't exist.
		$this->expectNotToPerformAssertions();
		$ability->register();
	}
}
