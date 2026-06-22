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
}
