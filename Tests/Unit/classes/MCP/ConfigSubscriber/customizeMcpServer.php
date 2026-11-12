<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\ConfigSubscriber;

use Brain\Monkey\Functions;
use Imagify\MCP\ConfigSubscriber;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\ConfigSubscriber::customize_mcp_server().
 *
 * @covers \Imagify\MCP\ConfigSubscriber::customize_mcp_server
 * @group  MCP
 */
class Test_CustomizeMcpServer extends TestCase {

	/**
	 * Tests that customize_mcp_server() sets server_name to 'imagify-plugin'.
	 */
	public function testSetsServerName(): void {
		Functions\when( '__' )->returnArg();

		$subscriber = new ConfigSubscriber();
		$result     = $subscriber->customize_mcp_server( [] );

		$this->assertSame( 'imagify-plugin', $result['server_name'] );
	}

	/**
	 * Tests that customize_mcp_server() sets a non-empty server_description.
	 */
	public function testSetsServerDescription(): void {
		Functions\when( '__' )->returnArg();

		$subscriber = new ConfigSubscriber();
		$result     = $subscriber->customize_mcp_server( [] );

		$this->assertArrayHasKey( 'server_description', $result );
		$this->assertNotEmpty( $result['server_description'] );
	}

	/**
	 * Tests that customize_mcp_server() preserves existing keys such as server_id and server_route.
	 */
	public function testPreservesExistingKeys(): void {
		Functions\when( '__' )->returnArg();

		$input = [
			'server_id'    => 'mcp-adapter-default-server',
			'server_route' => 'mcp/mcp-adapter-default-server',
			'tools'        => [ 'discover-abilities', 'get-ability-info', 'execute-ability' ],
			'custom_key'   => 'custom_value',
		];

		$subscriber = new ConfigSubscriber();
		$result     = $subscriber->customize_mcp_server( $input );

		$this->assertSame( 'mcp-adapter-default-server', $result['server_id'] );
		$this->assertSame( 'mcp/mcp-adapter-default-server', $result['server_route'] );
		$this->assertSame( $input['tools'], $result['tools'] );
		$this->assertSame( 'custom_value', $result['custom_key'] );
	}

	/**
	 * Tests that customize_mcp_server() does NOT override server_id or server_route.
	 */
	public function testDoesNotOverrideServerIdOrRoute(): void {
		Functions\when( '__' )->returnArg();

		$input = [
			'server_id'    => 'original-server-id',
			'server_route' => 'original/route',
		];

		$subscriber = new ConfigSubscriber();
		$result     = $subscriber->customize_mcp_server( $input );

		$this->assertSame( 'original-server-id', $result['server_id'] );
		$this->assertSame( 'original/route', $result['server_route'] );
	}
}
