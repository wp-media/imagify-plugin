<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifyAdminAjaxPost;

use Imagify;
use Imagify_Admin_Ajax_Post;
use WPMedia\PHPUnit\Integration\AjaxTestCase;
use WPMedia\PHPUnit\Integration\HttpRequestTrait;

/**
 * Tests Imagify_Admin_Ajax_Post::imagify_signup_callback().
 *
 * @covers Imagify_Admin_Ajax_Post::imagify_signup_callback
 *
 * @uses   imagify_die()
 * @uses   add_imagify_user()
 *
 * @group  ImagifyAdminAjaxPost
 * @group  Signup
 */
class Test_ImagifySignupCallback extends AjaxTestCase {
	use HttpRequestTrait;

	/**
	 * AJAX action handled by the callback under test.
	 *
	 * @var string
	 */
	protected $action = 'imagify_signup';

	/**
	 * Outgoing HTTP requests the callback attempted, recorded before mocking.
	 *
	 * @var array[]
	 */
	private $sent_requests = [];

	/**
	 * Valid signup nonce for the current user.
	 *
	 * @var string
	 */
	private $nonce = '';

	/**
	 * Set up an administrator, a valid nonce, and HTTP interception.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// _handleAjax() fires admin_init, which would trigger WordPress' update
		// checks against WordPress.org; keep those out of the test's HTTP log.
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		// The signup action hooks on wp_doing_ajax(), which the AJAX test case
		// now simulates; register them for the simulated request.
		Imagify_Admin_Ajax_Post::get_instance()->init();

		$this->nonce = wp_create_nonce( 'imagify-signup' );

		$this->sent_requests = [];

		add_filter( 'pre_http_request', [ $this, 'recordRequest' ], 1, 3 );

		// Block and answer every request according to the fixture.
		$this->setup_http();
	}

	/**
	 * Stop mocking HTTP and clean up the request superglobals.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->tear_down_http();
		remove_filter( 'pre_http_request', [ $this, 'recordRequest' ], 1 );

		unset( $_GET['email'], $_GET['imagifysignupnonce'], $_REQUEST['imagifysignupnonce'] );

		parent::tear_down();
	}

	/**
	 * Records outgoing requests before the mocking trait answers them.
	 *
	 * Hooked ahead of HttpRequestTrait::http_callback(), which has no way to expose
	 * the arguments; returning null lets the recorded request continue on to the
	 * fixture-driven mock - or get blocked when no fixture entry exists.
	 *
	 * @param  mixed  $response Preemptive response.
	 * @param  array  $args     Request arguments.
	 * @param  string $url      Requested URL.
	 * @return null
	 */
	public function recordRequest( $response, $args, $url ) {
		$this->sent_requests[] = [
			'url'  => $url,
			'body' => isset( $args['body'] ) ? $args['body'] : null,
		];

		return null;
	}

	/**
	 * Runs the signup for the submitted address and checks the outcome.
	 *
	 * Regression guard for #1065: the JS used to concatenate the raw address into
	 * the AJAX query string, so PHP decoded "+" to a space and sanitize_email()
	 * then stripped it - creating accounts for addresses nobody typed.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array $config   Row configuration.
	 * @param  array $expected Expected outcome.
	 * @return void
	 */
	public function testShouldHandleSignupEmailAsConfigured( $config, $expected ): void {
		if ( ! $expected['is_error'] ) {
			$this->config['http'][ Imagify::API_ENDPOINT . 'users/' ] = [
				'body'     => '{"id":1234}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		}

		$_GET['email']              = $config['email'];
		$_GET['imagifysignupnonce'] = $this->nonce;

		$response = $this->callAjaxAction();

		if ( $expected['is_error'] ) {
			$this->assertFalse( $response->success );
			$this->assertSame( $expected['error_message'], $response->data, 'A rejected address must report why.' );
			$this->assertCount( 0, $this->sent_requests, 'No account must be created for a rejected address.' );

			return;
		}

		$this->assertTrue( $response->success );
		$this->assertCount( 1, $this->sent_requests, 'The signup request should have been attempted once.' );
		$this->assertStringContainsString(
			$expected['body_contains'],
			is_array( $this->sent_requests[0]['body'] )
				? wp_json_encode( $this->sent_requests[0]['body'] )
				: (string) $this->sent_requests[0]['body'],
			'The address must reach the API exactly as submitted.'
		);
	}
}
