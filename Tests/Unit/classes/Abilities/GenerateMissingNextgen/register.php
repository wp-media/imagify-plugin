<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::register().
 *
 * `wp_register_ability` is defined via php-stubs in the unit test environment,
 * so the function_exists() guard always passes. `Bulk` is final, so the real
 * singleton is used to satisfy the constructor type hint — register() never
 * touches it.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::register
 * @group  MCP
 */
class Test_Register extends TestCase {

	/**
	 * Tests that register() registers the ability with the expected slug and config.
	 */
	public function testRegistersAbilityWithExpectedConfig(): void {
		Functions\stubTranslationFunctions();

		$captured = null;

		Functions\expect( 'wp_register_ability' )
			->once()
			->with(
				'imagify/generate-missing-nextgen',
				Mockery::on(
					function ( $config ) use ( &$captured ) {
						$captured = $config;

						return is_array( $config );
					}
				)
			);

		$ability = new GenerateMissingNextgen( Bulk::get_instance() );
		$ability->register();

		$this->assertSame( 'imagify', $captured['category'] );
		$this->assertSame( [ $ability, 'execute' ], $captured['execute_callback'] );
		$this->assertSame( [ $ability, 'check_permissions' ], $captured['permission_callback'] );
		$this->assertSame( [ 'scheduled', 'error' ], $captured['output_schema']['properties']['status']['enum'] );
		$this->assertTrue( $captured['meta']['show_in_rest'] );
		$this->assertTrue( $captured['meta']['mcp']['public'] );
		$this->assertTrue( $captured['meta']['annotations']['destructive'] );
	}
}
