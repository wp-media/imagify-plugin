<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'upgrader_post_install', 'imagify_sync_theme_plugin_files_on_update', IMAGIFY_INT_MAX, 3 );
/**
 * Filters the installation response after the installation has finished.
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

	imagify_synchronize_files_from_folders( array(
		$folder_id => array(
			'folder_id'   => $folder_id,
			'path'        => $placeholder,
			'active'      => 1,
			'folder_path' => $folder_path,
		),
	) );

	return $response;
}
