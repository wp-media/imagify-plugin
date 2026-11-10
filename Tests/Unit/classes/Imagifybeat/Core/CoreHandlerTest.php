<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Core;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify\Imagifybeat\Core;
use Imagify\Tests\Unit\classes\Imagifybeat\Stubs\ImagifybeatMocksTrait;
use Imagify\Tests\Unit\TestCase;
use RuntimeException;

/**
 * Tests for \Imagify\Imagifybeat\Core::core_handler().
 *
 * @covers \Imagify\Imagifybeat\Core::core_handler
 * @group  Imagifybeat
 */
class CoreHandlerTest extends TestCase {
	use ImagifybeatMocksTrait;

	/**
	 * Clean up the request superglobal.
	 */
	protected function tearDown(): void {
		$_POST = [];

		parent::tearDown();
	}

	/**
	 * Test: the response sent, and the Imagifybeat hooks fired before it. No nonce is checked or created for a user
	 * who is rejected.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs, the $_POST data and the nonce state.
	 * @param array $expected The arguments passed to wp_send_json_error() or wp_send_json(), and how many times each hook fired.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$_POST = $config['post'];
		$error = null;
		$json  = null;

		Functions\when( 'wp_send_json_error' )->alias(
			function ( ...$args ) use ( &$error ) {
				$error = $args;
				throw new RuntimeException( 'Response sent.' );
			}
		);
		Functions\when( 'wp_send_json' )->alias(
			function ( $response ) use ( &$json ) {
				unset( $response['server_time'] );
				$json = $response;
				throw new RuntimeException( 'Response sent.' );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\expect( 'wp_create_nonce' )->never();

		if ( [ null, 403 ] === $expected['error'] ) {
			Functions\expect( 'wp_verify_nonce' )->never();
		} else {
			Functions\when( 'wp_verify_nonce' )->justReturn( $config['nonce_state'] );
		}

		try {
			Core::get_instance()->core_handler();
		} catch ( RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame( $expected['error'], $error );
		$this->assertSame( $expected['json'], $json );
		$this->assertSame(
			$expected['hooks'],
			[
				'imagifybeat_refresh_nonces' => Filters\applied( 'imagifybeat_refresh_nonces' ),
				'imagifybeat_received'       => Filters\applied( 'imagifybeat_received' ),
				'imagifybeat_send'           => Filters\applied( 'imagifybeat_send' ),
				'imagifybeat_tick'           => Actions\did( 'imagifybeat_tick' ),
			]
		);
	}
}
