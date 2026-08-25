<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyAdminAjaxPost;

use Imagify_Admin_Ajax_Post;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Admin_Ajax_Post::imagify_signup_callback
 *
 * @uses   imagify_die()
 * @uses   add_imagify_user()
 *
 * @group  ImagifyAdminAjaxPost
 * @group  Signup
 */
class Test_ImagifySignupCallback extends TestCase {
	/**
	 * Whether to use the Imagify API in these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Bodies of the HTTP requests the plugin tried to send.
	 *
	 * @var array
	 */
	private $sent_bodies = [];

	/**
	 * Valid signup nonce for the current user.
	 *
	 * @var string
	 */
	private $nonce = '';

	/**
	 * Set up an administrator, a valid nonce, and an HTTP interceptor.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->nonce = wp_create_nonce( 'imagify-signup' );

		$this->sent_bodies = [];

		// Never leave the box: record what would have been sent, then fail the call.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args ) {
				$this->sent_bodies[] = isset( $args['body'] ) ? $args['body'] : null;

				return new \WP_Error( 'test_blocked', 'Blocked by the test suite.' );
			},
			10,
			2
		);
	}

	/**
	 * Clean up the request superglobals.
	 *
	 * @return mixed
	 */
	public function tear_down() {
		unset( $_GET['email'], $_GET['imagifysignupnonce'], $_REQUEST['imagifysignupnonce'] );

		return parent::tear_down();
	}

	/**
	 * Run the callback, returning the imagify_die() message it produced.
	 *
	 * @param  string $email The submitted address.
	 * @return string
	 */
	private function run_callback( string $email ): string {
		$_GET['email'] = $email;
		// check_ajax_referer() reads $_REQUEST, and PHP does not rebuild $_REQUEST
		// when $_GET is written at runtime, so both have to be set here.
		$_GET['imagifysignupnonce']     = $this->nonce;
		$_REQUEST['imagifysignupnonce'] = $this->nonce;

		try {
			Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback();
		} catch ( \WPDieException $e ) {
			return (string) $e->getMessage();
		}

		return '';
	}

	/**
	 * A "+" alias must reach the API exactly as the user typed it.
	 *
	 * Regression guard for #1065: the JS used to concatenate the raw address into
	 * the AJAX query string, so PHP decoded the "+" to a space and sanitize_email()
	 * then stripped that space - signing the user up as "hannamay21@..." instead.
	 */
	public function testShouldSendAPlusAliasToTheApiUnchanged() {
		$this->run_callback( 'hanna+may21@wp-media.me' );

		$this->assertCount( 1, $this->sent_bodies, 'The signup request should have been attempted.' );
		$this->assertStringContainsString(
			'hanna+may21@wp-media.me',
			is_array( $this->sent_bodies[0] ) ? wp_json_encode( $this->sent_bodies[0] ) : (string) $this->sent_bodies[0],
			'The "+" alias must survive intact all the way to the API request.'
		);
	}

	/**
	 * Dots and underscores must also survive untouched.
	 */
	public function testShouldSendDotsAndUnderscoresToTheApiUnchanged() {
		$this->run_callback( 'film.simleu_21@gmail.com' );

		$this->assertCount( 1, $this->sent_bodies );
		$this->assertStringContainsString(
			'film.simleu_21@gmail.com',
			is_array( $this->sent_bodies[0] ) ? wp_json_encode( $this->sent_bodies[0] ) : (string) $this->sent_bodies[0]
		);
	}

	/**
	 * An address sanitization would alter must be rejected, not silently corrected.
	 *
	 * This is the failure mode behind #1065: sanitize_email() strips the space from
	 * "hanna may21@wp-media.me", producing the perfectly valid "hannamay21@..." - so
	 * without this guard an account is created for an address nobody typed and the
	 * confirmation mail goes to a mailbox the user cannot read.
	 */
	public function testShouldRejectAnAddressSanitizationWouldAlter() {
		$message = $this->run_callback( 'hanna may21@wp-media.me' );

		$this->assertSame( 'Not a valid email address.', $message );
		$this->assertCount( 0, $this->sent_bodies, 'No account must be created for a mangled address.' );
	}

	/**
	 * Surrounding whitespace is trimmed rather than rejected.
	 */
	public function testShouldTrimSurroundingWhitespaceInsteadOfRejecting() {
		$this->run_callback( '  tzinkeh@yahoo.no  ' );

		$this->assertCount( 1, $this->sent_bodies, 'A pasted address with stray spaces should still be accepted.' );
		$this->assertStringContainsString(
			'tzinkeh@yahoo.no',
			is_array( $this->sent_bodies[0] ) ? wp_json_encode( $this->sent_bodies[0] ) : (string) $this->sent_bodies[0]
		);
	}

	/**
	 * A genuinely invalid address is still rejected.
	 */
	public function testShouldRejectAnInvalidAddress() {
		$message = $this->run_callback( 'not-an-email' );

		$this->assertSame( 'Not a valid email address.', $message );
		$this->assertCount( 0, $this->sent_bodies );
	}
}
