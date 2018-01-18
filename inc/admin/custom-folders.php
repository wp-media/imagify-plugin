<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'upgrader_post_install', 'imagify_sync_theme_plugin_files_on_update', IMAGIFY_INT_MAX, 3 );
/**
 * Filters the installation response after the installation has finished.
 *
 * @since 2.8.0
 *
 * @param bool  $response   Installation response.
 * @param array $hook_extra Extra arguments passed to hooked filters.
 * @param array $result     Installation result data.
 */
function imagify_sync_theme_plugin_files_on_update( $response, $hook_extra, $result ) {
	global $wpdb;

	if ( ( empty( $hook_extra['plugin'] ) && empty( $hook_extra['theme'] ) ) || empty( $result['destination'] ) ) {
		return $response;
	}

	$folder_path = trailingslashit( $result['destination'] );

	if ( Imagify_Files_Scan::is_path_forbidden( $folder_path, false ) ) {
		// This theme or plugin must not be optimized.
		return $response;
	}

	$placeholder = Imagify_Files_Scan::add_placeholder( $folder_path );
	$folder_id   = Imagify_Folders_DB::get_instance()->get_active_folders_column_in( 'folder_id', 'path', $placeholder );

	if ( ! $folder_id ) {
		// This theme or plugin is not "active".
		return $response;
	}

	$folder_id = reset( $folder_id );
	$folder    = array(
		'folder_id'   => $folder_id,
		'path'        => $placeholder,
		'active'      => 1,
		'folder_path' => $folder_path,
	);

	/**
	 * Get the files from DB, and from the folder.
	 */
	$files = imagify_get_files_from_folders( array( $folder_id => $folder ), array( 'insert_files_as_modified' => true ) );

	if ( ! $files ) {
		// This theme or plugin doesn't have images.
		return $response;
	}

	$files_db    = Imagify_Files_DB::get_instance();
	$files_table = $files_db->get_table_name();
	$files_key   = esc_sql( $files_db->get_primary_key() );
	$file_ids    = array();

	foreach ( $files as $file_data ) {
		$file_ids[] = $file_data[ $files_key ];
	}

	$file_ids = Imagify_DB::prepare_values_list( $file_ids );
	$results  = $wpdb->get_results( "SELECT * FROM $files_table WHERE $files_key IN ( $file_ids ) ORDER BY $files_key;", ARRAY_A ); // WPCS: unprepared SQL ok.

	if ( ! $results ) {
		// WAT?!
		return $response;
	}

	// Finally, refresh the files data.
	foreach ( $results as $file ) {
		$file = get_imagify_attachment( 'File', $file, 'sync_theme_plugin_files_on_update' );
		imagify_refresh_file_modified( $file );
	}

	return $response;
}
