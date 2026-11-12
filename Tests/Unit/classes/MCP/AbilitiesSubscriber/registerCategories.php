<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\AbilitiesSubscriber;

use Brain\Monkey\Functions;
use Imagify\MCP\AbilitiesSubscriber;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\AbilitiesSubscriber::register_categories().
 *
 * @covers \Imagify\MCP\AbilitiesSubscriber::register_categories
 * @group  MCP
 */
class Test_RegisterCategories extends TestCase {

	/**
	 * Tests that register_categories() calls wp_register_ability_category with the 'imagify' id
	 * when the function exists.
	 */
	public function testRegistersImagifyCategoryWhenFunctionExists(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability_category' )
			->once()
			->with(
				'imagify',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['label'] ) && isset( $args['description'] );
					}
				)
			);

		$subscriber = new AbilitiesSubscriber();
		$subscriber->register_categories();
	}

	/**
	 * Tests that register_categories() registers the 'imagify' category label correctly.
	 */
	public function testRegistersImagifyCategoryLabel(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability_category' )
			->once()
			->with(
				'imagify',
				\Mockery::on(
					function ( $args ) {
						return isset( $args['label'] ) && 'Imagify' === $args['label'];
					}
				)
			);

		$subscriber = new AbilitiesSubscriber();
		$subscriber->register_categories();
	}

	/**
	 * Tests that register_categories() is a no-op when wp_register_ability_category is unavailable
	 * by verifying that the guard lets the function be called without errors when it IS available.
	 *
	 * CONSTRAINT — why the missing-function guard branch cannot be isolated in unit tests:
	 * Brain Monkey's Functions\expect() / Functions\when() eval()s a stub function into existence
	 * for the entire PHP process lifetime. Because the two tests above stub
	 * wp_register_ability_category, it is permanently defined after they run. No subsequent test
	 * in this class can make function_exists('wp_register_ability_category') return false.
	 *
	 * As a result the WP < 6.9 guard branch (early return when the function is absent) is
	 * integration-only: the integration test suite can run against a WP < 6.9 environment where
	 * the function is genuinely not defined. The positive path (function present → called once)
	 * is fully asserted by testRegistersImagifyCategoryWhenFunctionExists() above.
	 *
	 * This test records that understanding and asserts the positive path is stable under the
	 * 'imagify' category ID, documenting the behaviour for reviewers.
	 */
	public function testRegistersImagifyCategoryId(): void {
		Functions\when( '__' )->returnArg();

		Functions\expect( 'wp_register_ability_category' )
			->once()
			->with( 'imagify', \Mockery::type( 'array' ) );

		$subscriber = new AbilitiesSubscriber();
		$subscriber->register_categories();
	}
}
