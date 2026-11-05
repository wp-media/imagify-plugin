<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat\Core;

use Imagify\Imagifybeat\Actions;
use Imagify\Imagifybeat\Core;
use WP_Error;
use WPMedia\PHPUnit\Integration\AjaxTestCase;

/**
 * Tests Imagify\Imagifybeat\Core::core_handler() and the capability checks of its listeners.
 *
 * @covers \Imagify\Imagifybeat\Core::core_handler
 * @covers \Imagify\Imagifybeat\Core::current_user_can_use_imagifybeat
 * @covers \Imagify\Imagifybeat\Actions::add_requirements_to_response
 * @covers \Imagify\Imagifybeat\Actions::add_bulk_optimization_stats_to_response
 * @covers \Imagify\Imagifybeat\Actions::add_bulk_optimization_status_to_response
 * @covers \Imagify\Imagifybeat\Actions::add_options_optimization_status_to_response
 * @covers \Imagify\Imagifybeat\Actions::add_library_optimization_status_to_response
 * @covers \Imagify\Imagifybeat\Actions::add_custom_folders_optimization_status_to_response
 *
 * @group  Imagifybeat
 */
class CoreHandlerTest extends AjaxTestCase {
	/**
	 * AJAX action handled by the callback under test.
	 *
	 * @var string
	 */
	protected $action = 'imagifybeat';

	/**
	 * Custom folders fixture: folder ID, file ID and directory.
	 *
	 * @var array
	 */
	private $custom_folders_fixture = [];

	/**
	 * Whether the test switched to a sub-site.
	 *
	 * @var bool
	 */
	private $switched_blog = false;

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

		add_filter( 'pre_http_request', [ $this, 'blockHttpRequest' ] );
	}

	/**
	 * Clean up the request superglobals.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'blockHttpRequest' ] );

		unset( $_POST['_nonce'], $_POST['data'], $_POST['screen_id'] );

		delete_transient( 'imagify_missing_next_gen_total' );

		if ( $this->switched_blog ) {
			restore_current_blog();
			$this->switched_blog = false;
		}

		if ( $this->custom_folders_fixture ) {
			\Imagify_Files_DB::get_instance()->delete( $this->custom_folders_fixture['file_id'] );
			\Imagify_Folders_DB::get_instance()->delete( $this->custom_folders_fixture['folder_id'] );
			wp_delete_file( $this->custom_folders_fixture['dir'] . 'pixel.png' );
			rmdir( $this->custom_folders_fixture['dir'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			$this->custom_folders_fixture = [];
		}

		parent::tear_down();
	}

	/**
	 * Prevent any outgoing HTTP request (the requirements listener checks the API status).
	 *
	 * @return WP_Error
	 */
	public function blockHttpRequest() {
		return new WP_Error( 'http_request_blocked', 'Blocked in tests.' );
	}

	/**
	 * A Subscriber sending an invalid nonce must not get a fresh one.
	 *
	 * @return void
	 */
	public function testShouldNotRefreshNonceForSubscriber(): void {
		$this->setUser( 'subscriber' );

		$_POST['_nonce'] = 'x';

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );
		$this->assertFalse( $response->success );
		$this->assertObjectNotHasProperty( 'imagifybeat_nonce', $response );
		$this->assertObjectNotHasProperty( 'nonces_expired', $response );
	}

	/**
	 * A Subscriber holding a valid nonce must not get any listener data.
	 *
	 * @return void
	 */
	public function testShouldNotReturnDataForSubscriberWithValidNonce(): void {
		$this->setUser( 'subscriber' );

		$_POST['_nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		$_POST['data']   = $this->getAllListenersData( 1 );

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );
		$this->assertFalse( $response->success );
		$this->assertObjectNotHasProperty( 'server_time', $response );

		foreach ( array_keys( $_POST['data'] ) as $imagifybeat_id ) {
			$this->assertObjectNotHasProperty( $imagifybeat_id, $response );
		}
	}

	/**
	 * An administrator sending an expired nonce still gets a fresh one.
	 *
	 * @return void
	 */
	public function testShouldRefreshNonceForAdministrator(): void {
		$this->setUser( 'administrator' );

		$_POST['_nonce'] = 'x';

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );
		$this->assertTrue( $response->nonces_expired );
		$this->assertIsString( $response->imagifybeat_nonce );
		$this->assertNotEmpty( $response->imagifybeat_nonce );
	}

	/**
	 * An administrator still gets the settings and bulk optimization data.
	 *
	 * @return void
	 */
	public function testShouldReturnAdminDataForAdministrator(): void {
		$this->setUser( 'administrator' );

		$_POST['_nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		$_POST['data']   = [
			'imagify_requirements'            => 1,
			'imagify_bulk_optimization_stats' => [ 'library|wp' ],
		];

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'server_time', $response );
		$this->assertObjectHasProperty( 'imagify_requirements', $response );
		$this->assertObjectHasProperty( 'key_is_valid', $response->imagify_requirements );
		$this->assertObjectHasProperty( 'imagify_bulk_optimization_stats', $response );
	}

	/**
	 * An author still gets the Media Library status of their own media, and nothing else.
	 *
	 * @return void
	 */
	public function testShouldReturnLibraryStatusForOwnMediaOnlyToAuthor(): void {
		$author_id = $this->setUser( 'author' );
		$other_id  = self::factory()->user->create( [ 'role' => 'author' ] );

		$own_media   = self::factory()->attachment->create(
			[
				'post_author'    => $author_id,
				'post_mime_type' => 'image/jpeg',
			]
		);
		$other_media = self::factory()->attachment->create(
			[
				'post_author'    => $other_id,
				'post_mime_type' => 'image/jpeg',
			]
		);
		$missing_id  = $other_media + 1000;

		// Claim every media is locked: none is, so each one is reported as "changed".
		$_POST['_nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		$_POST['data']   = $this->getAllListenersData( $own_media, $other_media, $missing_id );

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );
		$this->assertObjectHasProperty( 'server_time', $response, 'An author must still be able to use Imagifybeat.' );

		// Media Library polling keeps working for the author's own media.
		$this->assertObjectHasProperty( 'imagify_library_optimization_status', $response );
		$library = (array) $response->imagify_library_optimization_status;
		$this->assertArrayHasKey( 'wp_' . $own_media, $library );
		$this->assertArrayNotHasKey( 'wp_' . $other_media, $library, 'Media the user cannot optimize must not be reported.' );
		$this->assertArrayNotHasKey( 'wp_' . $missing_id, $library, 'Missing media must not be distinguishable from forbidden media.' );

		// Admin-only data is not returned.
		$this->assertObjectNotHasProperty( 'imagify_requirements', $response );
		$this->assertObjectNotHasProperty( 'imagify_bulk_optimization_stats', $response );
		$this->assertObjectNotHasProperty( 'imagify_bulk_optimization_status', $response );
		$this->assertObjectNotHasProperty( 'imagify_options_optimization_status', $response );
		$this->assertObjectNotHasProperty( 'imagify_custom_folders_optimization_status', $response );
	}

	/**
	 * While another user's media is being optimized, probing it must look exactly like probing a missing media.
	 *
	 * @return void
	 */
	public function testShouldNotRevealLockedMediaOfOtherUsers(): void {
		$this->setUser( 'author' );

		$other_id    = self::factory()->user->create( [ 'role' => 'author' ] );
		$other_media = self::factory()->attachment->create(
			[
				'post_author'    => $other_id,
				'post_mime_type' => 'image/jpeg',
			]
		);
		$missing_id  = $other_media + 1000;

		set_transient( sprintf( 'imagify_%1$s_%2$s_process_locked', 'wp', $other_media ), 1 );

		foreach ( [ 0, 1 ] as $is_locked ) {
			$probes = [];

			foreach ( [ $other_media, $missing_id ] as $media_id ) {
				$response = $this->callImagifybeat(
					[
						'imagify_library_optimization_status' => [ 'wp' => [ '_' . $media_id => $is_locked ] ],
					]
				);

				$this->assertObjectNotHasProperty( 'imagify_library_optimization_status', $response );

				unset( $response->server_time );
				$probes[] = wp_json_encode( $response );
			}

			$this->assertSame( $probes[0], $probes[1], 'A locked media of another user must not be distinguishable from a missing media.' );
		}
	}

	/**
	 * A contributor passes the handler check (they can edit posts) but gets no admin-only data.
	 *
	 * @return void
	 */
	public function testShouldNotReturnAdminDataToContributor(): void {
		$this->setUser( 'contributor' );

		$response = $this->callImagifybeat( $this->getAllListenersData( 1 ) );

		$this->assertObjectHasProperty( 'server_time', $response );

		foreach ( array_keys( $this->getAllListenersData( 1 ) ) as $imagifybeat_id ) {
			$this->assertObjectNotHasProperty( $imagifybeat_id, $response );
		}
	}

	/**
	 * Invalid contexts sent to the bulk and settings listeners are ignored, without PHP notices.
	 *
	 * @return void
	 */
	public function testShouldIgnoreInvalidContextsForAdministrator(): void {
		$this->setUser( 'administrator' );

		set_transient( 'imagify_missing_next_gen_total', 5 );

		$invalid_groups = [
			'not-an-array',
			[ 'groupID' => 'library' ],
			[ 'context' => [ 'wp' ] ],
			[ 'context' => '' ],
			[ 'context' => 'bogus' ],
			[ 'context' => '../../wp-config' ],
		];

		// Only invalid contexts: nothing is returned.
		$response = $this->callImagifybeat(
			[
				'imagify_bulk_optimization_status' => $invalid_groups,
				'imagify_bulk_optimization_stats'  => [ 'nopipe', 'a|bogus', [ 'library|wp' ] ],
			]
		);

		$this->assertObjectHasProperty( 'server_time', $response );
		$this->assertObjectNotHasProperty( 'imagify_bulk_optimization_status', $response );
		$this->assertObjectNotHasProperty( 'imagify_bulk_optimization_stats', $response );

		// Mixed with a valid context: only the valid one is used.
		$invalid_groups[] = [
			'groupID' => 'library',
			'context' => 'wp',
		];

		$response = $this->callImagifybeat(
			[
				'imagify_bulk_optimization_status'    => $invalid_groups,
				'imagify_options_optimization_status' => [ 'wp', [ 'wp' ], '', 'bogus' ],
			]
		);

		$this->assertObjectHasProperty( 'imagify_bulk_optimization_status', $response );
		$this->assertSame( [ 'wp' ], array_keys( (array) $response->imagify_bulk_optimization_status->groups_data ) );
		$this->assertObjectHasProperty( 'imagify_options_optimization_status', $response );
		$this->assertSame( 5, $response->imagify_options_optimization_status->total );

		delete_transient( 'imagify_missing_next_gen_total' );
	}

	/**
	 * The "Other Media" listener only handles custom folders files.
	 *
	 * @return void
	 */
	public function testShouldIgnoreOtherContextsInCustomFoldersListener(): void {
		$admin_id = $this->setUser( 'administrator' );
		$media_id = self::factory()->attachment->create(
			[
				'post_author'    => $admin_id,
				'post_mime_type' => 'image/jpeg',
			]
		);

		$response = $this->callImagifybeat(
			[
				'imagify_custom_folders_optimization_status' => [ 'wp' => [ '_' . $media_id => 1 ] ],
			]
		);

		$this->assertObjectHasProperty( 'server_time', $response );
		$this->assertObjectNotHasProperty( 'imagify_custom_folders_optimization_status', $response );
	}

	/**
	 * "Other Media" file data is returned to an administrator, and to nobody below.
	 *
	 * @return void
	 */
	public function testShouldReturnCustomFoldersDataToAdministratorOnly(): void {
		$file_id = $this->createCustomFoldersFile();
		$data    = [
			'imagify_custom_folders_optimization_status' => [ 'custom-folders' => [ '_' . $file_id => 1 ] ],
		];

		foreach ( [ 'author', 'editor' ] as $role ) {
			$this->setUser( $role );

			$response = $this->callImagifybeat( $data );

			$this->assertObjectNotHasProperty( 'imagify_custom_folders_optimization_status', $response, "An {$role} must not get custom folders data." );
		}

		$this->setUser( 'administrator' );

		$response = $this->callImagifybeat( $data );
		$columns  = (array) $response->imagify_custom_folders_optimization_status;

		$this->assertArrayHasKey( 'custom-folders_' . $file_id, $columns );
		$this->assertStringContainsString( 'pixel.png', $columns[ 'custom-folders_' . $file_id ]->title );
	}

	/**
	 * Each listener answers the users who can open the screen that polls it, the same way on a single site and on
	 * a sub-site. On multisite, the request is made on a sub-site.
	 *
	 * @dataProvider configTestData
	 *
	 * @param  array $config   The site type (see skipUnlessSite()) and the user role ('super_admin' is an
	 *                         administrator who is also a super admin on multisite).
	 * @param  array $expected Whether the user can use Imagifybeat at all, and the probes that return data.
	 * @return void
	 */
	public function testShouldReturnListenerDataAsConfigured( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$file_id = $this->createCustomFoldersFile();

		if ( is_multisite() ) {
			switch_to_blog( self::factory()->blog->create() );
			$this->switched_blog = true;
		}

		$user_id  = $this->setSiteUser( $config['role'] );
		$media_id = self::factory()->attachment->create(
			[
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
			]
		);

		// The settings page progress bar only polls while next-gen images are being generated.
		set_transient( 'imagify_missing_next_gen_total', 5 );

		$probes = [
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

		$allowed = [];

		foreach ( $probes as $probe => $data ) {
			$response = $this->callImagifybeat( $data );

			if ( ! $expected['can_use'] ) {
				$this->assertFalse( $response->success, "Probe {$probe}: the user must be rejected." );
				$this->assertObjectNotHasProperty( 'server_time', $response );
				continue;
			}

			$this->assertObjectHasProperty( 'server_time', $response, "Probe {$probe}: the user must be able to use Imagifybeat." );

			if ( property_exists( $response, key( $data ) ) ) {
				$allowed[] = $probe;
			}
		}

		$this->assertSame( $expected['allowed'], $allowed );
	}

	/**
	 * Send an Imagifybeat request with a valid nonce for the current user.
	 *
	 * @param  array $data The Imagifybeat data.
	 * @return object      The decoded response.
	 */
	private function callImagifybeat( array $data ) {
		$this->_last_response = '';

		$_POST['_nonce'] = wp_create_nonce( 'imagifybeat-nonce' );
		$_POST['data']   = $data;

		$response = $this->callAjaxAction();

		$this->assertIsObject( $response );

		return $response;
	}

	/**
	 * Create an "Other Media" folder holding one image, and return the file ID.
	 *
	 * @return int
	 */
	private function createCustomFoldersFile(): int {
		$dir = trailingslashit( imagify_get_filesystem()->get_main_upload_basedir() ) . 'imagifybeat-test/';
		wp_mkdir_p( $dir );
		// A 1x1 transparent PNG.
		file_put_contents( $dir . 'pixel.png', base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=' ) ); // phpcs:ignore

		$folder_id = (int) \Imagify_Folders_DB::get_instance()->insert(
			[
				'path'   => \Imagify_Files_Scan::add_placeholder( $dir ),
				'active' => 1,
			]
		);
		$file_id   = (int) \Imagify_Custom_Folders::insert_file(
			[
				'folder_id' => $folder_id,
				'file_path' => $dir . 'pixel.png',
			]
		);

		$this->custom_folders_fixture = [
			'dir'       => $dir,
			'folder_id' => $folder_id,
			'file_id'   => $file_id,
		];

		$this->assertGreaterThan( 0, $file_id, 'The custom folders file fixture could not be created.' );

		return $file_id;
	}

	/**
	 * Create a user with the given role on the current site and log them in. Unlike setUser(), an administrator is
	 * only a site administrator: 'super_admin' is an administrator who is also a super admin (on multisite).
	 *
	 * @param  string $role The user role, or 'super_admin'.
	 * @return int          The user ID.
	 */
	private function setSiteUser( string $role ): int {
		$site_role = 'super_admin' === $role ? 'administrator' : $role;
		$user_id   = self::factory()->user->create( [ 'role' => $site_role ] );

		if ( is_multisite() ) {
			add_user_to_blog( get_current_blog_id(), $user_id, $site_role );

			if ( 'super_admin' === $role ) {
				grant_super_admin( $user_id );
			}
		}

		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Skip the test unless it runs on the given type of site.
	 *
	 * @param  string $site 'single', 'multisite' (Imagify not network-activated), 'network-active', or 'any'.
	 * @return void
	 */
	private function skipUnlessSite( string $site ) {
		if ( 'any' === $site ) {
			return;
		}

		$current = 'single';

		if ( is_multisite() ) {
			$current = imagify_is_active_for_network() ? 'network-active' : 'multisite';
		}

		if ( $site !== $current ) {
			$this->markTestSkipped( "Runs on a {$site} site only." );
		}
	}

	/**
	 * Create a user with the given role and log them in.
	 *
	 * @param  string $role The user role.
	 * @return int          The user ID.
	 */
	private function setUser( string $role ): int {
		$user_id = self::factory()->user->create( [ 'role' => $role ] );

		if ( is_multisite() && 'administrator' === $role ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Build an Imagifybeat payload that asks every listener for data.
	 *
	 * @param  int ...$media_ids IDs of the media to ask a status for.
	 * @return array
	 */
	private function getAllListenersData( int ...$media_ids ): array {
		$statuses = [];

		foreach ( $media_ids as $media_id ) {
			$statuses[ '_' . $media_id ] = 1;
		}

		return [
			'imagify_requirements'                       => 1,
			'imagify_bulk_optimization_stats'            => [ 'library|wp', 'custom-folders|custom-folders' ],
			'imagify_bulk_optimization_status'           => [
				[
					'groupID' => 'library',
					'context' => 'wp',
				],
			],
			'imagify_options_optimization_status'        => [ 'wp', 'custom-folders' ],
			'imagify_library_optimization_status'        => [ 'wp' => $statuses ],
			'imagify_custom_folders_optimization_status' => [ 'custom-folders' => $statuses ],
		];
	}
}
