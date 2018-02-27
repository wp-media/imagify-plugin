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
 * Delete a custom file.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param array $args An array of arguments.
 *                    At least: 'file_id'. At best (less queries): 'file_id', 'file_path' (or 'path' for the placeholder), and 'backup_path'.
 */
function imagify_delete_custom_file( $args = array() ) {
	$args = array_merge( array(
		'file_id'     => 0,
		'file_path'   => '',
		'path'        => '',
		'backup_path' => '',
		'file'        => false,
	), $args );

	$filesystem = imagify_get_filesystem();
	$file       = $args['file'] && is_a( $args['file'], 'Imagify_File_Attachment' ) ? $args['file'] : false;

	// The file.
	if ( ! $args['file_path'] && $args['path'] ) {
		$args['file_path'] = Imagify_Files_Scan::remove_placeholder( $args['path'] );
	}

	if ( ! $args['file_path'] && $args['file_id'] ) {
		$file = $file ? $file : get_imagify_attachment( 'File', $args['file_id'], 'delete_custom_file' );
		$args['file_path'] = $file->get_original_path();
	}

	if ( $args['file_path'] && $filesystem->exists( $args['file_path'] ) ) {
		$filesystem->delete( $args['file_path'] );
	}

	// The backup file.
	if ( ! $args['backup_path'] && $args['file_path'] ) {
		$args['backup_path'] = imagify_get_file_backup_path( $args['file_path'] );
	}

	if ( ! $args['backup_path'] && $args['file_id'] ) {
		$file = $file ? $file : get_imagify_attachment( 'File', $args['file_id'], 'delete_custom_file' );
		$args['backup_path'] = $file->get_raw_backup_path();
	}

	if ( $args['backup_path'] && $filesystem->exists( $args['backup_path'] ) ) {
		$filesystem->delete( $args['backup_path'] );
	}

	// In the database.
	if ( $file ) {
		$file->delete_row();
	} else {
		Imagify_Files_DB::get_instance()->delete( $args['file_id'] );
	}
}

/**
 * Check if a file has been modified, and update the database accordingly.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  object $file             An Imagify_File_Attachment object.
 * @param  bool   $is_folder_active Tell if the folder is active.
 * @return int|bool|object          The file ID if modified. False if not modified. A WP_Error object if the entry has been removed from the database.
 *                                  The entry is removed from the database if:
 *                                  - The file doesn't exist anymore.
 *                                  - Or if its folder is not active and: the file has been modified, or the file is not optimized by Imagify, or the file is orphan (its folder is not in the database anymore).
 */
function imagify_refresh_file_modified( $file, $is_folder_active = null ) {
	global $wpdb;

	$file_path   = $file->get_original_path();
	$backup_path = $file->get_backup_path();
	$filesystem  = imagify_get_filesystem();
	$modified    = false;

	if ( ! $file_path || ! $filesystem->exists( $file_path ) ) {
		/**
		 * The file doesn't exist anymore.
		 */
		if ( $backup_path ) {
			// Delete the backup file.
			$filesystem->delete( $backup_path );
		}

		// Get the folder ID before removing the row.
		$folder_id = $file->get_row();
		$folder_id = $folder_id['folder_id'];

		// Remove the entry from the database.
		$file->delete_row();

		// Remove the corresponding folder if inactive and have no files left.
		Imagify_Custom_Folders::remove_empty_inactive_folders( $folder_id );

		return new WP_Error( 'no-file', __( 'The file was missing or its path could not be retrieved from the database. The entry has been deleted from the database.', 'imagify' ) );
	}

	/**
	 * The file still exists.
	 */
	$old_data = $file->get_row();
	$new_data = array();

	// Folder ID.
	if ( $old_data['folder_id'] ) {
		$folder = wp_cache_get( 'custom_folder_' . $old_data['folder_id'], 'imagify' );

		if ( false === $folder ) {
			// The folder is not in the cache.
			$folder = Imagify_Folders_DB::get_instance()->get( $old_data['folder_id'] );
			$folder = $folder ? $folder : 0;
		}

		if ( ! $folder ) {
			// The folder is not in the database anymore.
			$old_data['folder_id'] = 0;
			$new_data['folder_id'] = 0;
		}
	} else {
		$folder = 0;
	}

	// Hash + modified.
	$current_hash = md5_file( $file_path );

	if ( ! $old_data['hash'] ) {
		$new_data['modified'] = 0;
	} else {
		$new_data['modified'] = (int) ! hash_equals( $old_data['hash'], $current_hash );
	}

	// The file is modified or is not optimized.
	if ( $new_data['modified'] || ! $file->is_optimized() ) {
		if ( ! isset( $is_folder_active ) ) {
			$is_folder_active = $folder && $folder['active'];
		}

		// Its folder is not active: remove the entry from the database and delete the backup.
		if ( ! $is_folder_active ) {
			if ( $backup_path ) {
				// Delete the backup file.
				$filesystem->delete( $backup_path );
			}

			// Remove the entry from the database.
			$file->delete_row();

			// Remove the corresponding folder if inactive and have no files left.
			Imagify_Custom_Folders::remove_empty_inactive_folders( $folder_id );

			return new WP_Error( 'folder-not-active', __( 'The file has been modified or was not optimized: its folder not being selected in the settings, the entry has been deleted from the database.', 'imagify' ) );
		}
	}

	$new_data['hash'] = $current_hash;

	// The file is modified.
	if ( $new_data['modified'] ) {
		// Delete all optimization data and update file data.
		$modified  = true;
		$mime_type = ! empty( $old_data['mime_type'] ) ? $old_data['mime_type'] : imagify_get_mime_type_from_file( $file_path );

		if ( strpos( $mime_type, 'image/' ) === 0 ) {
			$size = @getimagesize( $file_path );
		} else {
			$size = false;
		}

		$new_data = array_merge( $new_data, array(
			'file_date'          => imagify_get_file_date( $file_path ),
			'width'              => $size && isset( $size[0] ) ? $size[0] : 0,
			'height'             => $size && isset( $size[1] ) ? $size[1] : 0,
			'original_size'      => $filesystem->size( $file_path ),
			'optimized_size'     => null,
			'percent'            => null,
			'optimization_level' => null,
			'status'             => null,
			'error'              => null,
		) );

		if ( $backup_path ) {
			// Delete the backup of the previous file.
			$filesystem->delete( $backup_path );
		}
	} else {
		// Update file data to make sure nothing is missing.
		$path      = $backup_path ? $backup_path : $file_path;
		$mime_type = ! empty( $old_data['mime_type'] ) ? $old_data['mime_type'] : imagify_get_mime_type_from_file( $path );
		$file_date = ! empty( $old_data['file_date'] ) && '0000-00-00 00:00:00' !== $old_data['file_date'] ? $old_data['file_date'] : imagify_get_file_date( $path );

		if ( strpos( $mime_type, 'image/' ) === 0 ) {
			$size = @getimagesize( $path );
		} else {
			$size = false;
		}

		$new_data = array_merge( $new_data, array(
			'file_date'     => $file_date,
			'width'         => $size && isset( $size[0] ) ? $size[0] : 0,
			'height'        => $size && isset( $size[1] ) ? $size[1] : 0,
			'original_size' => $filesystem->size( $path ),
		) );
	}

	// Save the new data.
	$old_data = array_intersect_key( $old_data, $new_data );
	ksort( $old_data );
	ksort( $new_data );

	if ( $old_data !== $new_data ) {
		$file->update_row( $new_data );
	}

	return $modified ? $file->get_id() : false;
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

	// Caching the folders will prevent unecessary SQL queries in imagify_refresh_file_modified().
	foreach ( $folders as $folder_id => $folder ) {
		wp_cache_set( 'custom_folder_' . $folder_id, $folder, 'imagify' );
	}

	// Finally, refresh the files data.
	foreach ( $results as $file ) {
		$file      = $files_db->cast_row( $file );
		$folder_id = $file['folder_id'];
		$file      = get_imagify_attachment( 'File', $file, 'synchronize_files_from_folders' );

		imagify_refresh_file_modified( $file, $folders[ $folder_id ]['active'] );
	}

	foreach ( $folders as $folder_id => $folder ) {
		wp_cache_delete( 'custom_folder_' . $folder_id, 'imagify' );
	}
}
