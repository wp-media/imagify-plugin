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
 * `wp_register_ability` is defined via php-stubs in the unit test environment,
 * so the function_exists() guard always passes.
 *
 * @covers \Imagify\Abilities\BulkOptimize::register
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
				'imagify/bulk-optimize',
				Mockery::on(
					function ( $config ) use ( &$captured ) {
						$captured = $config;

						return is_array( $config );
					}
				)
			);

		$ability = new BulkOptimize( Mockery::mock( BulkOptimizerInterface::class ) );
		$ability->register();

		$this->assertSame( 'imagify', $captured['category'] );
		$this->assertSame( [ $ability, 'execute' ], $captured['execute_callback'] );
		$this->assertSame( [ $ability, 'check_permissions' ], $captured['permission_callback'] );
		$this->assertSame( [ 'context' ], $captured['input_schema']['required'] );
		$this->assertArrayHasKey( 'confirm', $captured['input_schema']['properties'] );
		$this->assertSame(
			[ 'scheduled', 'error', 'confirmation_required', 'insufficient_quota', 'invalid_api_key' ],
			$captured['output_schema']['properties']['status']['enum']
		);
		$this->assertTrue( $captured['meta']['show_in_rest'] );
		$this->assertTrue( $captured['meta']['mcp']['public'] );
		$this->assertFalse( $captured['meta']['annotations']['destructive'] );
	}
}
