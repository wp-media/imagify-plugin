<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\AdapterNotice;

use Brain\Monkey\Functions;
use Imagify\MCP\AdapterNotice;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\MCP\AdapterNotice::should_display().
 *
 * @covers \Imagify\MCP\AdapterNotice::should_display
 * @group  MCP
 */
class Test_ShouldDisplay extends TestCase {

	/**
	 * Tests should_display() against the configTestData fixture.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The environment to simulate.
	 * @param bool  $expected Whether the notice must be displayed.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$notice = Mockery::mock( AdapterNotice::class )->makePartial();
		$notice->shouldAllowMockingProtectedMethods();
		$notice->shouldReceive( 'is_abilities_api_available' )->andReturn( $config['abilities_api'] );
		$notice->shouldReceive( 'is_adapter_loaded' )->andReturn( $config['adapter_loaded'] );

		$context = Mockery::mock();
		$context->shouldReceive( 'current_user_can' )
			->with( 'manage' )
			->andReturn( $config['manage'] );

		Functions\when( 'current_user_can' )->alias(
			function ( $capability ) use ( $config ) {
				return 'install_plugins' === $capability && $config['install_plugins'];
			}
		);
		Functions\when( 'imagify_get_context' )->justReturn( $context );
		Functions\when( 'get_current_user_id' )->justReturn( $config['user_id'] );
		Functions\when( 'get_current_blog_id' )->justReturn( $config['blog_id'] );
		Functions\when( 'get_user_meta' )->alias(
			function ( $user_id, $key = '', $single = false ) use ( $config ) {
				if ( '' === $key ) {
					// WordPress returns every key, each holding a list of raw values.
					return array_map(
						function ( $value ) {
							return [ $value ];
						},
						$config['meta']
					);
				}

				return $config['meta'][ $key ] ?? ( $single ? '' : [] );
			}
		);

		$this->assertSame( $expected, $notice->should_display() );
	}
}
