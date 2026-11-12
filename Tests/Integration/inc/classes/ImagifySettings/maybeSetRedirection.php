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

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		unset( $_POST['_wpnonce'], $_POST['submit-goto-bulk'], $_REQUEST['_wp_http_referer'] );
	}

	public function tear_down() {
		unset( $_POST['_wpnonce'], $_POST['submit-goto-bulk'], $_REQUEST['_wp_http_referer'] );

		parent::tear_down();
	}

	public function testShouldRedirectToBulkWhenNonceIsValid() {
		$_POST['_wpnonce']         = wp_create_nonce( 'imagify-options' );
		$_POST['submit-goto-bulk'] = '1';

		$this->assertSame( [ 'new' ], Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] ) );
		$this->assertSame(
			esc_url_raw( get_admin_url( get_current_blog_id(), 'upload.php?page=imagify-bulk-optimization' ) ),
			$_REQUEST['_wp_http_referer']
		);
	}

	public function testShouldNotRedirectWithoutTheBulkButton() {
		$_POST['_wpnonce'] = wp_create_nonce( 'imagify-options' );

		Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] );

		$this->assertArrayNotHasKey( '_wp_http_referer', $_REQUEST );
	}

	public function testShouldNotRedirectWithoutNonce() {
		$_POST['submit-goto-bulk'] = '1';

		$this->assertSame( [ 'new' ], Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] ) );
		$this->assertArrayNotHasKey( '_wp_http_referer', $_REQUEST );
	}

	public function testShouldNotRedirectWithNonceForAnotherAction() {
		$_POST['_wpnonce']         = wp_create_nonce( 'another-action' );
		$_POST['submit-goto-bulk'] = '1';

		$this->assertSame( [ 'new' ], Imagify_Settings::get_instance()->maybe_set_redirection( [ 'new' ], [ 'old' ] ) );
		$this->assertArrayNotHasKey( '_wp_http_referer', $_REQUEST );
	}
}
