<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\Notices\Notices;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\AdapterNotice::display().
 *
 * @covers \Imagify\MCP\AdapterNotice::display
 * @group  MCP
 */
class Test_Display extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Skips the tests when an MCP Adapter is loaded or the Abilities API is missing.
	 */
	public function set_up() {
		parent::set_up();

		if ( class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			$this->markTestSkipped( 'An MCP Adapter is loaded, so the notice is never displayed.' );
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API (WordPress 6.9+) is not available.' );
		}
	}

	/**
	 * Tests the notice output for the current user.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   User role, user meta and dismissal state.
	 * @param array $expected Strings the output must and must not contain.
	 */
	public function testShouldDisplayExpected( $config, $expected ): void {
		$user_id = self::factory()->user->create( [ 'role' => $config['role'] ] );
		wp_set_current_user( $user_id );

		foreach ( $config['meta'] as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		if ( $config['dismissed'] ) {
			Notices::dismiss_notice( 'mcp-adapter', $user_id );
		}

		ob_start();
		do_action( 'admin_notices' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$output = (string) ob_get_clean();

		foreach ( $expected['contains'] as $string ) {
			$this->assertStringContainsString( $string, $output );
		}

		foreach ( $expected['not_contains'] as $string ) {
			$this->assertStringNotContainsString( $string, $output );
		}
	}
}
