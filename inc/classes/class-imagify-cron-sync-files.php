<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that scans the "active" custom folders to keep files in sync in the database.
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

		if ( ! imagify_valid_key() ) {
			return;
		}

		$user = new Imagify_User();

		if ( $user->is_over_quota() ) {
			return;
		}

		@set_time_limit( 0 );

		/**
		 * Get the folders from DB.
		 */
		$folders_db = Imagify_Folders_DB::get_instance();
		$files_db   = Imagify_Files_DB::get_instance();

		if ( ! $folders_db->can_operate() || ! $files_db->can_operate() ) {
			return;
		}

		$folders = imagify_get_folders_from_type( 'all', array(
			'active' => true,
		) );

		if ( ! $folders ) {
			return;
		}

		/**
		 * Get the files from DB, and from the folders.
		 */
		$files = imagify_get_files_from_folders( $folders, array(
			'insert_files_as_modified' => true,
		) );

		if ( ! $files ) {
			return;
		}

		$files_table = $files_db->get_table_name();
		$files_key   = $files_db->get_primary_key();
		$file_ids    = wp_list_pluck( $files, $files_key );
		$file_ids    = Imagify_DB::prepare_values_list( $file_ids );
		$files_key   = esc_sql( $files_key );
		$results     = $wpdb->get_results( "SELECT * FROM $files_table WHERE $files_key IN ( $file_ids ) ORDER BY $files_key;", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $results ) {
			// WAT?!
			return;
		}

		// Finally, refresh the files data.
		foreach ( $results as $file ) {
			$file = get_imagify_attachment( 'File', $file, 'sync_all_files_cron' );
			imagify_refresh_file_modified( $file );
		}
	}
}
