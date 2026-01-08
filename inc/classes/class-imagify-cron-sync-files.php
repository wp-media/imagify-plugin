<?php

use Imagify\Traits\InstanceGetterTrait;

/**
 * Class that scans the custom folders to keep files in sync in the database.
 *
 * @since  1.7
 */
class Imagify_Cron_Sync_Files extends Imagify_Abstract_Cron {
	use InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.0';

	/**
	 * Cron name.
	 *
	 * @var    string
	 * @since  1.7
	 */
	protected $event_name = 'imagify_sync_files';

	/**
	 * Cron recurrence.
	 *
	 * @var   string
	 * @since 1.7
	 */
	protected $event_recurrence = 'daily';

	/**
	 * Cron time.
	 *
	 * @var   string
	 * @since 1.7
	 */
	protected $event_time = '02:00';


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The event action.
	 *
	 * @since 1.7
	 */
	public function do_event() {
		$folders_db = Imagify_Folders_DB::get_instance();
		$files_db   = Imagify_Files_DB::get_instance();

		if ( ! $folders_db->can_operate() || ! $files_db->can_operate() ) {
			return;
		}

		if ( ! Imagify_Requirements::is_api_key_valid() ) {
			return;
		}

		if ( Imagify_Requirements::is_over_quota() ) {
			return;
		}

		$this->set_no_time_limit();

		/**
		 * Get the folders from DB.
		 */
		$folders = Imagify_Custom_Folders::get_folders();

		if ( ! $folders ) {
			return;
		}

		Imagify_Custom_Folders::synchronize_files_from_folders( $folders );
	}

	/**
	 * Attempts to set no limit to the PHP timeout for time intensive processes.
	 *
	 * @return void
	 */
	protected function set_no_time_limit() {
		if (
			function_exists( 'set_time_limit' )
			&&
			false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' )
			&& ! ini_get( 'safe_mode' ) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
}
