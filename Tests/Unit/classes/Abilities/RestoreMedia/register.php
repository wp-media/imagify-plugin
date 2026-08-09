<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::register().
 *
 * `wp_register_ability` is defined via php-stubs in the unit test environment,
 * so the function_exists() guard always passes.
 *
 * @covers \Imagify\Abilities\RestoreMedia::register
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
				'imagify/restore-media',
				Mockery::on(
					function ( $config ) use ( &$captured ) {
						$captured = $config;

						return is_array( $config );
					}
				)
			);

		$ability = new RestoreMedia();
		$ability->register();

		$this->assertSame( 'imagify', $captured['category'] );
		$this->assertSame( [ $ability, 'execute' ], $captured['execute_callback'] );
		$this->assertSame( [ $ability, 'check_permissions' ], $captured['permission_callback'] );
		// media_id is now optional — callers may pass media_id, media_filename, or media_url.
		$this->assertArrayNotHasKey( 'required', $captured['input_schema'] );
		$this->assertArrayHasKey( 'media_id', $captured['input_schema']['properties'] );
		$this->assertArrayHasKey( 'media_filename', $captured['input_schema']['properties'] );
		$this->assertArrayHasKey( 'media_url', $captured['input_schema']['properties'] );
		$this->assertSame( [ 'success', 'error' ], $captured['output_schema']['properties']['status']['enum'] );
		$this->assertTrue( $captured['meta']['show_in_rest'] );
		$this->assertTrue( $captured['meta']['mcp']['public'] );
		$this->assertTrue( $captured['meta']['annotations']['destructive'] );
	}
}
