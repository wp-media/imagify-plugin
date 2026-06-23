<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetMediaStatus;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetMediaStatus::register().
 *
 * @covers \Imagify\Abilities\GetMediaStatus::register
 * @group  MCP
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() registers the correct ability slug.
	 *
	 * Note: in the unit-test environment wp_register_ability is defined via
	 * php-stubs, so the function_exists() guard always passes.
	 */
	public function testRegistersCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-media-status',
				\Mockery::type( 'array' )
			);

		$ability = new GetMediaStatus();
		$ability->register();
	}

	/**
	 * Tests that register() sets mcp public meta flag to true.
	 */
	public function testSetsPublicMetaFlag(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing( function ( $slug, $args ) use ( &$captured ) {
				$captured = $args;
			} );

		$ability = new GetMediaStatus();
		$ability->register();

		$this->assertTrue( $captured['meta']['mcp']['public'] );
		$this->assertTrue( $captured['meta']['show_in_rest'] );
	}

	/**
	 * Tests that register() wires execute_callback to the execute method.
	 */
	public function testWiresExecuteCallback(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing( function ( $slug, $args ) use ( &$captured ) {
				$captured = $args;
			} );

		$ability = new GetMediaStatus();
		$ability->register();

		$this->assertIsArray( $captured['execute_callback'] );
		$this->assertSame( $ability, $captured['execute_callback'][0] );
		$this->assertSame( 'execute', $captured['execute_callback'][1] );
	}

	/**
	 * Tests that register() wires permission_callback to check_permissions.
	 */
	public function testWiresPermissionCallback(): void {
		Functions\when( '__' )->returnArg();

		$captured = null;
		Functions\expect( 'wp_register_ability' )
			->once()
			->andReturnUsing( function ( $slug, $args ) use ( &$captured ) {
				$captured = $args;
			} );

		$ability = new GetMediaStatus();
		$ability->register();

		$this->assertIsArray( $captured['permission_callback'] );
		$this->assertSame( $ability, $captured['permission_callback'][0] );
		$this->assertSame( 'check_permissions', $captured['permission_callback'][1] );
	}
}
