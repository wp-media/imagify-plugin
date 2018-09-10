<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'upgrader_post_install', 'imagify_sync_theme_plugin_files_on_update', IMAGIFY_INT_MAX, 3 );
/**
 * Sync files right after a theme or plugin has been updated.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  bool  $response   Installation response.
 * @param  array $hook_extra Extra arguments passed to hooked filters.
 * @param  array $result     Installation result data.
 * @return bool
 */
function imagify_sync_theme_plugin_files_on_update( $response, $hook_extra, $result ) {
	global $wpdb;

	if ( ( empty( $hook_extra['plugin'] ) && empty( $hook_extra['theme'] ) ) || empty( $result['destination'] ) ) {
		return $response;
	}

	$folders_to_sync = get_site_transient( 'imagify_themes_plugins_to_sync' );
	$folders_to_sync = is_array( $folders_to_sync ) ? $folders_to_sync : array();

	$folders_to_sync[] = $result['destination'];

	set_site_transient( 'imagify_themes_plugins_to_sync', $folders_to_sync, DAY_IN_SECONDS );

	return $response;
}

add_action( 'admin_init', 'imagify_sync_theme_plugin_files_after_update' );
/**
 * Sync files after some themes or plugins have been updated.
 *
 * @since  1.7.1.2
 * @author Grégory Viguier
 */
function imagify_sync_theme_plugin_files_after_update() {
	global $wpdb;

	$folders_to_sync = get_site_transient( 'imagify_themes_plugins_to_sync' );

	if ( ! $folders_to_sync || ! is_array( $folders_to_sync ) ) {
		return;
	}

	delete_site_transient( 'imagify_themes_plugins_to_sync' );

	$folders_db = Imagify_Folders_DB::get_instance();
	$files_db   = Imagify_Files_DB::get_instance();

	if ( ! $folders_db->can_operate() || ! $files_db->can_operate() ) {
		return;
	}

	foreach ( $folders_to_sync as $folder_path ) {
		$folder_path = trailingslashit( $folder_path );

		if ( Imagify_Files_Scan::is_path_forbidden( $folder_path ) ) {
			// This theme or plugin must not be optimized.
			continue;
		}

		// Get the related folder.
		$placeholder = Imagify_Files_Scan::add_placeholder( $folder_path );
		$folder      = Imagify_Folders_DB::get_instance()->get_in( 'path', $placeholder );

		if ( ! $folder ) {
			// This theme or plugin is not in the database.
			continue;
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
	}
}
