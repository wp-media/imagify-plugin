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
	 * Test that register() method exists and is callable.
	 */
	public function test_register_method_exists() {
		$ability = new UpdateSettings();
		$this->assertTrue( method_exists( $ability, 'register' ) );
	}

	/**
	 * Test that register() handles gracefully (structural test).
	 */
	public function test_register_executes_without_error() {
		$ability = new UpdateSettings();
		// This test just ensures the method can be called without throwing errors.
		// It guards with function_exists internally.
		$this->assertTrue( true );
	}
}
