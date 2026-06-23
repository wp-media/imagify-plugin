<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetNextgenCoverage;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GetNextgenCoverage::register().
 *
 * @covers \Imagify\Abilities\GetNextgenCoverage::register
 * @group  MCP
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() is a no-op when wp_register_ability does not exist (WP < 6.9).
	 *
	 * Must run first — Brain Monkey cannot undefine a function once stubbed, so
	 * wp_register_ability must be absent when this test runs.
	 */
	public function testNoOpWhenWpRegisterAbilityAbsent(): void {
		$this->expectNotToPerformAssertions();

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );
		$ability->register();
	}

	/**
	 * Tests that register() registers the ability with the correct slug.
	 *
	 * Brain Monkey's Functions\when/expect stubs functions into the PHP process
	 * permanently, so wp_register_ability is always defined after the first stub.
	 * We assert the positive path (function present → called once with correct slug).
	 */
	public function testRegistersAbilityWithCorrectSlug(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-nextgen-coverage',
				Mockery::type( 'array' )
			);

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );
		$ability->register();
	}

	/**
	 * Tests that the ability is registered with mcp.public = true in meta.
	 */
	public function testRegistersAbilityWithMcpPublicMeta(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-nextgen-coverage',
				Mockery::on(
					function ( $args ) {
						return isset( $args['meta']['mcp']['public'] ) && true === $args['meta']['mcp']['public'];
					}
				)
			);

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );
		$ability->register();
	}

	/**
	 * Tests that the ability is registered with show_in_rest = true in meta.
	 */
	public function testRegistersAbilityWithShowInRest(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-nextgen-coverage',
				Mockery::on(
					function ( $args ) {
						return isset( $args['meta']['show_in_rest'] ) && true === $args['meta']['show_in_rest'];
					}
				)
			);

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );
		$ability->register();
	}

	/**
	 * Tests that the ability is registered in the 'imagify' category.
	 */
	public function testRegistersAbilityInImagifyCategory(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/get-nextgen-coverage',
				Mockery::on(
					function ( $args ) {
						return isset( $args['category'] ) && 'imagify' === $args['category'];
					}
				)
			);

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );
		$ability->register();
	}
}
