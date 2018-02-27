<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get the path to the backups directory (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return string Path to the backups directory.
 */
function imagify_get_files_backup_dir_path() {
	static $backup_dir;

	if ( isset( $backup_dir ) ) {
		return $backup_dir;
	}

	$backup_dir = imagify_get_abspath() . 'imagify-backup/';

	/**
	 * Filter the backup directory path (custom folders).
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 *
	 * @param string $backup_dir The backup directory path.
	*/
	$backup_dir = apply_filters( 'imagify_files_backup_directory', $backup_dir );
	$backup_dir = trailingslashit( wp_normalize_path( $backup_dir ) );

	return $backup_dir;
}

/**
 * Tell if the folder containing the backups is writable (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return bool
 */
function imagify_files_backup_dir_is_writable() {
	if ( ! get_imagify_backup_dir_path() ) {
		return false;
	}

	$filesystem     = imagify_get_filesystem();
	$has_backup_dir = wp_mkdir_p( imagify_get_files_backup_dir_path() );

	return $has_backup_dir && $filesystem->is_writable( imagify_get_files_backup_dir_path() );
}

/**
 * Get the backup path of a specific file (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  string $file_path The file path.
 * @return string|bool       The backup path. False on failure.
 */
function imagify_get_file_backup_path( $file_path ) {
	$file_path  = wp_normalize_path( (string) $file_path );
	$abspath    = imagify_get_abspath();
	$backup_dir = imagify_get_files_backup_dir_path();

	if ( ! $file_path ) {
		return false;
	}

	return str_replace( $abspath, $backup_dir, $file_path );
}

/**
 * Check if files inside the given folders have been modified, and update the database accordingly.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param array $folders A list of folders. See Imagify_Custom_Folders::get_folders() for the format.
 */
function imagify_synchronize_files_from_folders( $folders ) {
	global $wpdb;
	/**
	 * Get the files from DB, and from the folder.
	 */
	$files = Imagify_Custom_Folders::get_files_from_folders( $folders, array(
		'return_only_old_files' => true,
	) );

	if ( ! $files ) {
		// This folder doesn't have (new) images.
		return;
	}

	$files_db      = Imagify_Files_DB::get_instance();
	$files_table   = $files_db->get_table_name();
	$files_key     = $files_db->get_primary_key();
	$files_key_esc = esc_sql( $files_key );
	$file_ids      = wp_list_pluck( $files, $files_key );
	$file_ids      = Imagify_DB::prepare_values_list( $file_ids );
	$results       = $wpdb->get_results( "SELECT * FROM $files_table WHERE $files_key IN ( $file_ids ) ORDER BY $files_key_esc;", ARRAY_A ); // WPCS: unprepared SQL ok.

	if ( ! $results ) {
		// WAT?!
		return;
	}

	// Caching the folders will prevent unecessary SQL queries in Imagify_Custom_Folders::refresh_file().
	foreach ( $folders as $folder_id => $folder ) {
		wp_cache_set( 'custom_folder_' . $folder_id, $folder, 'imagify' );
	}

	// Finally, refresh the files data.
	foreach ( $results as $file ) {
		$file      = $files_db->cast_row( $file );
		$folder_id = $file['folder_id'];
		$file      = get_imagify_attachment( 'File', $file, 'synchronize_files_from_folders' );

		Imagify_Custom_Folders::refresh_file( $file, $folders[ $folder_id ]['active'] );
	}

	foreach ( $folders as $folder_id => $folder ) {
		wp_cache_delete( 'custom_folder_' . $folder_id, 'imagify' );
	}
}
