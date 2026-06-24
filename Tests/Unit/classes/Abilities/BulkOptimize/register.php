<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\BulkOptimize;

use Brain\Monkey\Functions;
use Imagify\Abilities\BulkOptimize;
use Imagify\Bulk\BulkOptimizerInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\BulkOptimize::register().
 *
 * @covers \Imagify\Abilities\BulkOptimize::register
 * @group  BulkOptimize
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() is a no-op when wp_register_ability is not available (WP < 6.9).
	 *
	 * Must run first — Brain Monkey cannot undefine a PHP function once stubbed, so this
	 * test must execute before any other test in this class stubs wp_register_ability.
	 *
	 * CONSTRAINT: Brain Monkey's Functions\expect() / Functions\when() eval()s a stub into
	 * existence for the entire PHP process lifetime. Once wp_register_ability is defined by
	 * a subsequent test, function_exists() will always return true. This test must run first
	 * (alphabetical order means "testNoOps..." < "testCalls...") to exercise the guard branch.
	 */
	public function testNoOpsWhenWpRegisterAbilityNotAvailable(): void {
		// Do not stub wp_register_ability — function_exists() returns false here.
		$this->expectNotToPerformAssertions();

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
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
				'imagify/bulk-optimize',
				\Mockery::type( 'array' )
			);

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
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

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
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

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$ability->register();

		$this->assertIsArray( $captured['execute_callback'] );
		$this->assertSame( $ability, $captured['execute_callback'][0] );
		$this->assertSame( 'execute', $captured['execute_callback'][1] );

		$this->assertIsArray( $captured['permission_callback'] );
		$this->assertSame( $ability, $captured['permission_callback'][0] );
		$this->assertSame( 'check_permissions', $captured['permission_callback'][1] );
	}

	/**
	 * Tests that register() includes context as required with enum values in the input schema.
	 */
	public function testInputSchemaHasContextAsRequired(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$ability->register();

		$this->assertContains( 'context', $captured['input_schema']['required'] );
		$this->assertContains( 'wp', $captured['input_schema']['properties']['context']['enum'] );
		$this->assertContains( 'custom-folders', $captured['input_schema']['properties']['context']['enum'] );
	}

	/**
	 * Tests that register() includes optimization_level as optional integer with min 0 and max 2 in the input schema.
	 */
	public function testInputSchemaHasOptimizationLevelAsOptional(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing(
				function ( $slug, $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$ability->register();

		$this->assertSame( 'integer', $captured['input_schema']['properties']['optimization_level']['type'] );
		$this->assertSame( 0, $captured['input_schema']['properties']['optimization_level']['minimum'] );
		$this->assertSame( 2, $captured['input_schema']['properties']['optimization_level']['maximum'] );
		$this->assertNotContains( 'optimization_level', $captured['input_schema']['required'] );
	}

	/**
	 * Tests that register() includes status, context, and error_message in the output schema.
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

		$bulk    = Mockery::mock( BulkOptimizerInterface::class );
		$ability = new BulkOptimize( $bulk );
		$ability->register();

		$this->assertArrayHasKey( 'status', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'context', $captured['output_schema']['properties'] );
		$this->assertArrayHasKey( 'error_message', $captured['output_schema']['properties'] );
	}
}
