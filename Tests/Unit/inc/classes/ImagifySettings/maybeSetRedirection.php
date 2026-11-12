<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\ImagifySettings;

use Brain\Monkey\Functions;
use Imagify_Settings;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers Imagify_Settings::maybe_set_redirection
 *
 * @group  ImagifySettings
 */
class Test_MaybeSetRedirection extends TestCase {
	/**
	 * Original $_POST and $_REQUEST, restored after each test.
	 *
	 * @var array
	 */
	private $superglobals = [];

	public function setUp(): void {
		parent::setUp();

		$this->superglobals = [ $_POST, $_REQUEST ];

		Functions\when( 'imagify_is_active_for_network' )->justReturn( false );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		// In this suite a nonce is the name of the action it was created for.
		Functions\when( 'wp_verify_nonce' )->alias(
			function ( $nonce, $action ) {
				return $nonce === $action ? 1 : false;
			}
		);
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
		Functions\when( 'get_admin_url' )->alias(
			function ( $blog_id, $path ) {
				return 'http://example.org/wp-admin/' . $path;
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg();
	}

	public function tearDown(): void {
		[ $_POST, $_REQUEST ] = $this->superglobals;

		parent::tearDown();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$_POST    = [];
		$_REQUEST = [];

		if ( null !== $config['nonce'] ) {
			$_POST['_wpnonce'] = $config['nonce'];
		}

		if ( $config['submit_goto_bulk'] ) {
			$_POST['submit-goto-bulk'] = '1';
		}

		$this->assertSame( [ 'new' ], Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] ) );

		if ( null === $expected['redirect'] ) {
			$this->assertArrayNotHasKey( '_wp_http_referer', $_REQUEST );
		} else {
			$this->assertStringEndsWith( $expected['redirect'], $_REQUEST['_wp_http_referer'] );
		}
	}
}
