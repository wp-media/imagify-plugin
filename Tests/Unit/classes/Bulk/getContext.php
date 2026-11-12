<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Bulk\Bulk::get_context
 * @covers \Imagify\Bulk\Bulk::get_request_param
 *
 * @group  Bulk
 */
class Test_GetContext extends TestCase {
	/**
	 * Original $_GET and $_POST, restored after each test.
	 *
	 * @var array
	 */
	private $superglobals = [];

	public function setUp(): void {
		parent::setUp();

		$this->superglobals = [ $_GET, $_POST ];

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ) {
				return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
	}

	public function tearDown(): void {
		[ $_GET, $_POST ] = $this->superglobals;

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$_GET  = $config['get'];
		$_POST = $config['post'];

		$this->assertSame( $expected, ( new Bulk() )->get_context( ...$config['args'] ) );
	}
}
