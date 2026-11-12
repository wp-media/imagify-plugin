<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Imagifybeat;

/**
 * Helpers shared by the Imagifybeat integration tests: users, sub-sites and an "Other Media" file.
 *
 * Call tearDownImagifybeat() from tear_down().
 */
trait ImagifybeatTestTrait {
	/**
	 * Custom folders fixture: directory, folder ID and file ID.
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
	 * On multisite, run the rest of the test on a new sub-site.
	 *
	 * @return void
	 */
	protected function switchToSubSite(): void {
		if ( ! is_multisite() ) {
			return;
		}

		switch_to_blog( self::factory()->blog->create() );
		$this->switched_blog = true;
	}

	/**
	 * Create a user with the given role on the current site and log them in. 'super_admin' is an administrator who
	 * is also a super admin on multisite (a plain administrator on a single site).
	 *
	 * @param  string $role The user role, or 'super_admin'.
	 * @return int          The user ID.
	 */
	protected function setSiteUser( string $role ): int {
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
	 * Create an "Other Media" folder holding one image, and return the file ID.
	 *
	 * @return int
	 */
	protected function createCustomFoldersFile(): int {
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
	 * Skip the test unless it runs on the given type of site.
	 *
	 * @param string $site 'single', 'multisite' (Imagify not network-activated), 'network-active', or 'any'.
	 *
	 * @return void
	 */
	protected function skipUnlessSite( string $site ) {
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
	 * Restore the site and remove the "Other Media" file.
	 *
	 * @return void
	 */
	protected function tearDownImagifybeat(): void {
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
	}
}
