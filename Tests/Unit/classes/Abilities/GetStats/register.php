<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetStats;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetStats;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetStats::register().
 *
 * @covers \Imagify\Abilities\GetStats::register
 * @group  MCP
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() is a no-op when wp_register_ability does not exist.
	 *
	 * Must run first — Brain Monkey cannot undefine a function once stubbed, so
	 * wp_register_ability must be absent when this test runs.
	 */
	public function testNoOpWhenWpRegisterAbilityAbsent(): void {
		$this->expectNotToPerformAssertions();

		$ability = new GetStats();
		$ability->register();
	}

	/**
	 * Tests that register() calls wp_register_ability with the correct slug.
	 */
	public function testRegistersCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-stats',
				\Mockery::type( 'array' )
			);

		$ability = new GetStats();
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
				function ( string $slug, array $args ) use ( &$captured ) {
					$captured = $args;
				}
			);

		$ability = new GetStats();
		$ability->register();

		$this->assertTrue( $captured['meta']['show_in_rest'] );
		$this->assertTrue( $captured['meta']['mcp']['public'] );
	}
}
