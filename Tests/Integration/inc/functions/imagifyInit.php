<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\Functions;

use Imagify\Tests\Integration\TestCase;
use ReflectionProperty;
use WPMedia\MCP\OAuth\Auth\SecretManager;
use WPMedia\MCP\OAuth\Bootstrap;

/**
 * Tests the MCP OAuth boot gate of imagify_init().
 *
 * @covers ::imagify_init
 * @group  MCP
 */
class Test_ImagifyInit extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Skips the tests when an MCP Adapter is loaded, as the gate is then legitimately open.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( class_exists( 'WP\MCP\Core\McpAdapter' ) ) {
			$this->markTestSkipped( 'An MCP Adapter is loaded, so the MCP OAuth library is booted.' );
		}
	}

	/**
	 * Tests that the MCP OAuth library is neither instantiated nor wired without an MCP Adapter.
	 */
	public function testShouldNotBootMcpOAuthWithoutAdapter(): void {
		$instance = new ReflectionProperty( Bootstrap::class, 'instance' );
		$instance->setAccessible( true );

		$this->assertFalse( $instance->isInitialized(), 'Bootstrap::instance() must not be called without an MCP Adapter.' );
		$this->assertFalse( has_action( 'init', [ SecretManager::class, 'ensure_secret' ] ) );
		$this->assertFalse( has_action( 'mcp_adapter_init' ) );
	}
}
