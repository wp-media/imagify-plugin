<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\Admin;

use Imagify\Tests\Integration\TestCase;

/**
 * Tests the MCP option migration of _imagify_new_upgrade().
 *
 * @covers ::_imagify_new_upgrade
 * @group  MCP
 */
class Test_ImagifyNewUpgradeMcp extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Loads the upgrader, it is only loaded in the admin.
	 */
	public function set_up() {
		parent::set_up();

		require_once IMAGIFY_PATH . 'inc/admin/upgrader.php';
	}

	/**
	 * Resets the MCP option.
	 */
	public function tear_down() {
		\Imagify_Options::get_instance()->delete( 'mcp' );

		parent::tear_down();
	}

	/**
	 * Tests that MCP is enabled for sites upgrading from a version without the setting.
	 */
	public function testShouldEnableMcpWhenUpgradingFromOlderVersion(): void {
		\Imagify_Options::get_instance()->delete( 'mcp' );

		_imagify_new_upgrade( '2.3.5', '2.3.5' );

		$this->assertSame( 1, \Imagify_Options::get_instance()->get( 'mcp' ) );
	}

	/**
	 * Tests that an already saved choice is kept, for example by another site of the network.
	 */
	public function testShouldKeepDisabledMcpWhenAlreadySaved(): void {
		\Imagify_Options::get_instance()->set( 'mcp', 0 );

		_imagify_new_upgrade( '2.3.5', '2.3.5' );

		$this->assertSame( 0, \Imagify_Options::get_instance()->get( 'mcp' ) );
	}
}
