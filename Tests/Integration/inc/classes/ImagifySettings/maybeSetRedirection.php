<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifySettings;

use Imagify_Settings;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Settings::maybe_set_redirection
 *
 * @group  ImagifySettings
 */
class Test_MaybeSetRedirection extends TestCase {
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Original $_POST and $_REQUEST, restored after each test.
	 *
	 * @var array
	 */
	private $superglobals = [];

	public function set_up() {
		parent::set_up();

		$this->superglobals = [ $_POST, $_REQUEST ];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tear_down() {
		[ $_POST, $_REQUEST ] = $this->superglobals;

		parent::tear_down();
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		$_POST    = [];
		$_REQUEST = [];

		if ( null !== $config['nonce'] ) {
			$_POST['_wpnonce'] = wp_create_nonce( $config['nonce'] );
		}

		if ( $config['submit_goto_bulk'] ) {
			$_POST['submit-goto-bulk'] = '1';
		}

		$this->assertSame( [ 'new' ], Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] ) );

		if ( null === $expected['redirect'] ) {
			$this->assertArrayNotHasKey( '_wp_http_referer', $_REQUEST );
		} else {
			$this->assertSame(
				esc_url_raw( get_admin_url( get_current_blog_id(), $expected['redirect'] ) ),
				$_REQUEST['_wp_http_referer']
			);
		}
	}
}
