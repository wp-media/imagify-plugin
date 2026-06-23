<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\OptimizeMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\OptimizeMedia::register().
 *
 * @covers \Imagify\Abilities\OptimizeMedia::register
 * @group  OptimizeMedia
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

		$ability = new OptimizeMedia();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability once with the correct slug.
	 */
	public function testCallsWpRegisterAbilityWithCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/optimize-media',
				\Mockery::type( 'array' )
			);

		$ability = new OptimizeMedia();
		$ability->register();
	}

	/**
	 * Tests that register() sets show_in_rest and mcp.public meta flags.
	 */
	public function testSetsMetaFlags(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new OptimizeMedia();
		$ability->register();

		$this->assertTrue( $captured['meta']['show_in_rest'] );
		$this->assertTrue( $captured['meta']['mcp']['public'] );
	}

	/**
	 * Tests that register() wires execute_callback and permission_callback to the instance.
	 */
	public function testWiresCallbacks(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new OptimizeMedia();
		$ability->register();

		$this->assertIsArray( $captured['execute_callback'] );
		$this->assertSame( $ability, $captured['execute_callback'][0] );
		$this->assertSame( 'execute', $captured['execute_callback'][1] );

		$this->assertIsArray( $captured['permission_callback'] );
		$this->assertSame( $ability, $captured['permission_callback'][0] );
		$this->assertSame( 'check_permissions', $captured['permission_callback'][1] );
	}

	/**
	 * Tests that register() includes media_id as a required integer in the input schema.
	 */
	public function testInputSchemaHasMediaIdAsRequiredInteger(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new OptimizeMedia();
		$ability->register();

		$this->assertSame( 'integer', $captured['input_schema']['properties']['media_id']['type'] );
		$this->assertContains( 'media_id', $captured['input_schema']['required'] );
	}

	/**
	 * Tests that register() includes optimization_level as optional integer in the input schema.
	 */
	public function testInputSchemaHasOptimizationLevel(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new OptimizeMedia();
		$ability->register();

		$this->assertSame( 'integer', $captured['input_schema']['properties']['optimization_level']['type'] );
		$this->assertSame( 0, $captured['input_schema']['properties']['optimization_level']['minimum'] );
		$this->assertSame( 2, $captured['input_schema']['properties']['optimization_level']['maximum'] );
		$this->assertNotContains( 'optimization_level', $captured['input_schema']['required'] );
	}

	/**
	 * Tests that register() includes status, original_size, optimized_size, savings_percent, and error_message in the output schema.
	 */
	public function testOutputSchemaHasExpectedKeys(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new OptimizeMedia();
		$ability->register();

		$this->assertArrayHasKey( 'status', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'original_size', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'optimized_size', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'savings_percent', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'error_message', $captured['output_schema']['properties'] );
	}
}
