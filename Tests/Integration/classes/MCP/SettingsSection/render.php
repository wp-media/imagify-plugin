<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\SettingsSection;

use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\SettingsSection::render().
 *
 * @covers \Imagify\MCP\SettingsSection::render
 * @group  MCP
 */
class Test_Render extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Skips the tests when the Abilities API is missing.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API (WordPress 6.9+) is not available.' );
		}
	}

	/**
	 * Resets the MCP option.
	 */
	public function tear_down() {
		\Imagify_Options::get_instance()->delete( 'mcp' );

		parent::tear_down();
	}

	/**
	 * Tests the MCP option output on the settings page.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The MCP option value.
	 * @param array $expected Strings the output must and must not contain.
	 */
	public function testShouldRenderExpected( $config, $expected ): void {
		\Imagify_Options::get_instance()->set( 'mcp', $config['mcp'] );

		ob_start();
		do_action( 'imagify_settings_after_lossless' );
		// Keep only the MCP block, the analytics opt-in prints on the same hook.
		$output = (string) strstr( (string) ob_get_clean(), 'imagify-mcp-option' );

		foreach ( $expected['contains'] as $string ) {
			$this->assertStringContainsString( $string, $output );
		}

		foreach ( $expected['not_contains'] as $string ) {
			$this->assertStringNotContainsString( $string, $output );
		}
	}
}
