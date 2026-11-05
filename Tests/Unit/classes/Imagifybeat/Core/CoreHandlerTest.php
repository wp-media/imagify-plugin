<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Imagifybeat\Core;

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
	 * Test: the error sent, and that no nonce is checked or created before it.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The allowed "context:describer" pairs and the $_POST data.
	 * @param array $expected The arguments passed to wp_send_json_error().
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->mockContexts( $config['allowed'] );

		$_POST = $config['post'];
		$sent  = null;

		Functions\when( 'wp_send_json_error' )->alias(
			function ( ...$args ) use ( &$sent ) {
				$sent = $args;
				throw new RuntimeException( 'Response sent.' );
			}
		);
		Functions\expect( 'wp_verify_nonce' )->never();
		Functions\expect( 'wp_create_nonce' )->never();

		try {
			Core::get_instance()->core_handler();
		} catch ( RuntimeException $e ) {
			unset( $e );
		}

		$this->assertSame( $expected, $sent );
	}
}
