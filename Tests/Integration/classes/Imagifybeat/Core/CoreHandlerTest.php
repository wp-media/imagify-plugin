<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Core;

use Imagify\Imagifybeat\Actions;
use Imagify\Imagifybeat\Core;
use Imagify\Tests\Integration\classes\Imagifybeat\ImagifybeatTestTrait;
use WP_Error;
use WPMedia\PHPUnit\Integration\AjaxTestCase;

/**
 * Tests Imagify\Imagifybeat\Core::core_handler(), through the `imagifybeat` AJAX action.
 *
 * @covers \Imagify\Imagifybeat\Core::core_handler
 *
 * @group  Imagifybeat
 */
class CoreHandlerTest extends AjaxTestCase {
	use ImagifybeatTestTrait;

	/**
	 * AJAX action handled by the callback under test.
	 *
	 * @var string
	 */
	protected $action = 'imagifybeat';

	/**
	 * The Imagifybeat filters third-party code can hook into.
	 *
	 * @var string[]
	 */
	private const HOOKS = [ 'imagifybeat_refresh_nonces', 'imagifybeat_received', 'imagifybeat_send' ];

	/**
	 * How many times each Imagifybeat filter ran.
	 *
	 * @var int[]
	 */
	private $hooks_fired = [];

	/**
	 * Register the Imagifybeat hooks and block outgoing HTTP requests.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		// _handleAjax() fires admin_init, which would trigger WordPress' update checks.
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );

		// The Imagifybeat hooks are only registered in the admin area.
		Core::get_instance()->init();
		Actions::get_instance()->init();

		// The requirements listener checks the API status.
		add_filter( 'pre_http_request', [ $this, 'blockHttpRequest' ] );

		// Third-party code hooked on the Imagifybeat filters.
		$this->hooks_fired = array_fill_keys( self::HOOKS, 0 );

		foreach ( self::HOOKS as $hook ) {
			add_filter( $hook, [ $this, 'countHook' ] );
		}
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'blockHttpRequest' ] );

		foreach ( self::HOOKS as $hook ) {
			remove_filter( $hook, [ $this, 'countHook' ] );
		}

		unset( $_POST['_nonce'], $_POST['data'] );
		delete_transient( 'imagify_missing_next_gen_total' );

		$this->tearDownImagifybeat();

		parent::tear_down();
	}

	/**
	 * Prevent any outgoing HTTP request.
	 *
	 * @return WP_Error
	 */
	public function blockHttpRequest() {
		return new WP_Error( 'http_request_blocked', 'Blocked in tests.' );
	}

	/**
	 * Count a run of the current Imagifybeat filter.
	 *
	 * @param  array $response The Imagifybeat response.
	 * @return array
	 */
	public function countHook( $response ) {
		++$this->hooks_fired[ current_filter() ];

		return $response;
	}

	/**
	 * Test: what the user gets for each listener, on each type of site. Each probe asks one listener for data.
	 * The Imagifybeat filters must not run for a rejected user.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array $config   The site type (see skipUnlessSite()), the user role ('super_admin' is an administrator
	 *                         who is also a super admin on multisite) and whether the nonce is valid.
	 * @param  array $expected The response status and, when answered, the probes that return data.
	 * @return void
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$file_id = $this->createCustomFoldersFile();

		$this->switchToSubSite();

		$user_id  = $this->setSiteUser( $config['role'] );
		$media_id = self::factory()->attachment->create(
			[
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
			]
		);

		// The settings page progress bar only polls while next-gen images are being generated.
		set_transient( 'imagify_missing_next_gen_total', 5 );

		$probes = $this->getProbes( $media_id, $file_id );
		$nonce  = 'valid' === $config['nonce'] ? wp_create_nonce( 'imagifybeat-nonce' ) : 'x';

		if ( 'answered' !== $expected['status'] ) {
			// One request asking every listener.
			$probes = [ 'all' => array_merge( ...array_values( $probes ) ) ];
		}

		$allowed = [];

		foreach ( $probes as $probe => $data ) {
			$response = $this->callImagifybeat( $nonce, $data );

			switch ( $expected['status'] ) {
				case 'rejected':
					$this->assertFalse( $response->success );
					$this->assertObjectNotHasProperty( 'imagifybeat_nonce', $response );
					$this->assertObjectNotHasProperty( 'server_time', $response );
					break;

				case 'nonce_refreshed':
					$this->assertTrue( $response->nonces_expired );
					$this->assertNotEmpty( $response->imagifybeat_nonce );
					$this->assertObjectNotHasProperty( 'server_time', $response );
					break;

				default:
					$this->assertObjectHasProperty( 'server_time', $response, "Probe {$probe}: the user must be able to use Imagifybeat." );
			}

			foreach ( array_keys( $data ) as $imagifybeat_id ) {
				if ( property_exists( $response, $imagifybeat_id ) ) {
					$allowed[] = $probe;
				}
			}
		}

		$this->assertSame( $expected['allowed'], $allowed );

		$requests = count( $probes );
		$hooks    = [
			'rejected'        => [ 0, 0, 0 ],
			'nonce_refreshed' => [ $requests, 0, 0 ],
			'answered'        => [ 0, $requests, $requests ],
		];

		$this->assertSame( array_combine( self::HOOKS, $hooks[ $expected['status'] ] ), $this->hooks_fired );
	}

	/**
	 * Get the requests to send, each asking one listener for data.
	 *
	 * @param  int $media_id ID of a media of the current user.
	 * @param  int $file_id  ID of an "Other Media" file.
	 * @return array
	 */
	private function getProbes( int $media_id, int $file_id ): array {
		return [
			'requirements'               => [ 'imagify_requirements' => 1 ],
			'bulk_stats_library'         => [ 'imagify_bulk_optimization_stats' => [ 'library|wp' ] ],
			'bulk_stats_custom_folders'  => [ 'imagify_bulk_optimization_stats' => [ 'custom-folders|custom-folders' ] ],
			'bulk_status_library'        => [
				'imagify_bulk_optimization_status' => [
					[
						'groupID' => 'library',
						'context' => 'wp',
					],
				],
			],
			'bulk_status_custom_folders' => [
				'imagify_bulk_optimization_status' => [
					[
						'groupID' => 'custom-folders',
						'context' => 'custom-folders',
					],
				],
			],
			'options_status'             => [ 'imagify_options_optimization_status' => [ 'wp' ] ],
			'library_status'             => [ 'imagify_library_optimization_status' => [ 'wp' => [ '_' . $media_id => 1 ] ] ],
			'custom_folders_status'      => [ 'imagify_custom_folders_optimization_status' => [ 'custom-folders' => [ '_' . $file_id => 1 ] ] ],
		];
	}

	/**
	 * Send an Imagifybeat request.
	 *
	 * @param  string $nonce The nonce.
	 * @param  array  $data  The Imagifybeat data.
	 * @return object        The decoded response.
	 */
	private function callImagifybeat( string $nonce, array $data ) {
		$this->_last_response = '';

		$_POST['_nonce'] = $nonce;
		$_POST['data']   = $data;

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );

		return $response;
	}
}
