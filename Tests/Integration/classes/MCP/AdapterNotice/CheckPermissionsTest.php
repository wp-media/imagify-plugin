<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\AdapterNotice;

use Imagify\MCP\AdapterNotice;
use Imagify\Notices\Notices;
use Imagify\Tests\Integration\TestCase;

/**
 * The migration notice reaches the super admins who manage Imagify from the network admin.
 *
 * @covers \Imagify\MCP\AdapterNotice::display
 * @covers \Imagify\MCP\AdapterNotice::get_subscribed_events
 * @group  MCP
 */
class CheckPermissionsTest extends TestCase {

	protected $useApi = false;

	/**
	 * Skips the tests when the Abilities API is missing.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$this->markTestSkipped( 'The Abilities API (WordPress 6.9+) is not available.' );
		}
	}

	public function tear_down() {
		set_current_screen( 'front' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: the notice is also printed on the network admin hook.
	 */
	public function testShouldSubscribeToNetworkAdminNotices(): void {
		$events = AdapterNotice::get_subscribed_events();

		$this->assertSame( 'display', $events['network_admin_notices'] );
		$this->assertSame( 'display', $events['admin_notices'] );
	}

	/**
	 * Test: the notice output in the network admin.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()), whether the user is a super admin, and the
	 *                        dismissal state.
	 * @param array $expected Strings the output must and must not contain.
	 */
	public function testShouldDisplayExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( $config['super_admin'] ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'mcp_refresh_jti_abc123', 'x' );

		if ( $config['dismissed'] ) {
			Notices::dismiss_notice( 'mcp-adapter', $user_id );
		}

		set_current_screen( 'dashboard-network' );
		$this->assertTrue( is_network_admin() );

		// Whatever adapter the environment loads, the notice must see none.
		$notice = new class() extends AdapterNotice {
			protected function is_adapter_loaded(): bool {
				return false;
			}
		};
		add_action( 'network_admin_notices', [ $notice, 'display' ] );

		ob_start();
		do_action( 'network_admin_notices' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.
		$output = (string) ob_get_clean();

		foreach ( $expected['contains'] as $string ) {
			$this->assertStringContainsString( $string, $output );
		}

		foreach ( $expected['not_contains'] as $string ) {
			$this->assertStringNotContainsString( $string, $output );
		}
	}
}
