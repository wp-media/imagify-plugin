<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'upgrader_post_install', 'imagify_sync_theme_plugin_files_on_update', IMAGIFY_INT_MAX, 3 );
/**
 * Sync files after a theme or plugin has been updated.
 *
 * @since  1.7
 * @author Grégory Viguier
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

	$folders_db = Imagify_Folders_DB::get_instance();
	$files_db   = Imagify_Files_DB::get_instance();

	if ( ! $folders_db->can_operate() || ! $files_db->can_operate() ) {
		return;
	}

	if ( ! imagify_valid_key() ) {
		return;
	}

	$user = new Imagify_User();

	if ( $user->is_over_quota() ) {
		return;
	}

	$folder_path = trailingslashit( $result['destination'] );

	if ( Imagify_Files_Scan::is_path_forbidden( $folder_path ) ) {
		// This theme or plugin must not be optimized.
		return $response;
	}

	// Get the related folder.
	$placeholder = Imagify_Files_Scan::add_placeholder( $folder_path );
	$folder      = Imagify_Folders_DB::get_instance()->get_in( 'path', $placeholder );

	if ( ! $folder ) {
		// This theme or plugin is not in the database.
		return $response;
	}

	// Sync the folder files.
	Imagify_Custom_Folders::synchronize_files_from_folders( array(
		$folder['folder_id'] => array(
			'folder_id'   => $folder['folder_id'],
			'path'        => $placeholder,
			'active'      => $folder['active'],
			'folder_path' => $folder_path,
		),
	) );

	return $response;
}
