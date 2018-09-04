<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that scans the custom folders to keep files in sync in the database.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Cron_Sync_Files extends Imagify_Abstract_Cron {

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
	 * @access protected
	 */
	protected $event_name = 'imagify_sync_files';

	/**
	 * Cron recurrence.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_recurrence = 'daily';

	/**
	 * Cron time.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_time = '02:00';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The event action.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function do_event() {
		global $wpdb;

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

		@set_time_limit( 0 );

		/**
		 * Get the folders from DB.
		 */
		$folders = Imagify_Custom_Folders::get_folders();

		if ( ! $folders ) {
			return;
		}

		Imagify_Custom_Folders::synchronize_files_from_folders( $folders );
	}
}
