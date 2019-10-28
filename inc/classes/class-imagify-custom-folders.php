<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that regroups things about "custom folders".
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Custom_Folders {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1';


	/** ----------------------------------------------------------------------------------------- */
	/** BACKUP FOLDER =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the path to the backups directory (custom folders).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string Path to the backups directory.
	 */
	public static function get_backup_dir_path() {
		static $backup_dir;

		if ( isset( $backup_dir ) ) {
			return $backup_dir;
		}

		$filesystem = imagify_get_filesystem();
		$backup_dir = $filesystem->get_site_root() . 'imagify-backup/';

		/**
		 * Filter the backup directory path (custom folders).
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param string $backup_dir The backup directory path.
		*/
		$backup_dir = apply_filters( 'imagify_files_backup_directory', $backup_dir );
		$backup_dir = $filesystem->normalize_dir_path( $backup_dir );

		return $backup_dir;
	}

	/**
	 * Tell if the folder containing the backups is writable (custom folders).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public static function backup_dir_is_writable() {
		return imagify_get_filesystem()->make_dir( self::get_backup_dir_path() );
	}

	/**
	 * Get the backup path of a specific file (custom folders).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string|bool       The backup path. False on failure.
	 */
	public static function get_file_backup_path( $file_path ) {
		$file_path  = wp_normalize_path( (string) $file_path );
		$site_root  = imagify_get_filesystem()->get_site_root();
		$backup_dir = self::get_backup_dir_path();

		if ( ! $file_path ) {
			return false;
		}

		return preg_replace( '@^' . preg_quote( $site_root, '@' ) . '@', $backup_dir, $file_path );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SINGLE FILE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Insert a file into the DB.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args An array of arguments to pass to Imagify_Files_DB::insert(). Required values are 'folder_id' and ( 'path' or 'file_path').
	 * @return int         The file ID on success. 0 on failure.
	 */
	public static function insert_file( $args = array() ) {
		if ( empty( $args['folder_id'] ) ) {
			return 0;
		}

		if ( empty( $args['path'] ) ) {
			if ( empty( $args['file_path'] ) ) {
				return 0;
			}

			$args['path'] = Imagify_Files_Scan::add_placeholder( $args['file_path'] );
		}

		if ( empty( $args['file_path'] ) ) {
			$args['file_path'] = Imagify_Files_Scan::remove_placeholder( $args['path'] );
		}

		$filesystem = imagify_get_filesystem();

		if ( ! $filesystem->is_readable( $args['file_path'] ) ) {
			return 0;
		}

		if ( empty( $args['file_date'] ) || '0000-00-00 00:00:00' === $args['file_date'] ) {
			$args['file_date'] = $filesystem->get_date( $args['file_path'] );
		}

		if ( empty( $args['mime_type'] ) ) {
			$args['mime_type'] = $filesystem->get_mime_type( $args['file_path'] );
		}

		if ( ( empty( $args['width'] ) || empty( $args['height'] ) ) && strpos( $args['mime_type'], 'image/' ) === 0 ) {
			$file_size      = $filesystem->get_image_size( $args['file_path'] );
			$args['width']  = $file_size ? $file_size['width']  : 0;
			$args['height'] = $file_size ? $file_size['height'] : 0;
		}

		if ( empty( $args['hash'] ) ) {
			$args['hash'] = md5_file( $args['file_path'] );
		}

		if ( empty( $args['original_size'] ) ) {
			$args['original_size'] = (int) $filesystem->size( $args['file_path'] );
		}

		$files_db    = Imagify_Files_DB::get_instance();
		$primary_key = $files_db->get_primary_key();
		unset( $args[ $primary_key ] );

		return $files_db->insert( $args );
	}

	/**
	 * Delete a custom file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $args An array of arguments.
	 *                    At least: 'file_id'. At best: 'file_id', 'file_path' (or 'path' for the placeholder), and 'backup_path'.
	 */
	public static function delete_file( $args = [] ) {
		$args = array_merge( [
			'file_id'     => 0,
			'file_path'   => '',
			'path'        => '',
			'backup_path' => '',
			'process'     => false,
		], $args );

		$filesystem = imagify_get_filesystem();

		// Fill the blanks.
		if ( $args['process'] && $args['process'] instanceof \Imagify\Optimization\Process\ProcessInterface ) {
			$process = $args['process'];
		} else {
			$process = imagify_get_optimization_process( $args['file_id'], 'custom-folders' );
		}

		if ( ! $process->is_valid() ) {
			// You fucked up!
			return;
		}

		if ( ! $args['file_path'] && $args['path'] ) {
			$args['file_path'] = Imagify_Files_Scan::remove_placeholder( $args['path'] );
		}

		if ( ! $args['file_path'] && $args['file_id'] ) {
			$args['file_path'] = $process->get_media()->get_fullsize_path();
		}

		if ( ! $args['backup_path'] && $args['file_path'] ) {
			$args['backup_path'] = self::get_file_backup_path( $args['file_path'] );
		}

		if ( ! $args['backup_path'] && $args['file_id'] ) {
			$args['backup_path'] = $process->get_media()->get_raw_backup_path();
		}

		// Trigger a common hook.
		imagify_trigger_delete_media_hook( $process );

		// The file.
		if ( $args['file_path'] && $filesystem->exists( $args['file_path'] ) ) {
			$filesystem->delete( $args['file_path'] );
		}

		// The backup file.
		if ( $args['backup_path'] && $filesystem->exists( $args['backup_path'] ) ) {
			$filesystem->delete( $args['backup_path'] );
		}

		// Webp.
		$mime_type = $filesystem->get_mime_type( $args['file_path'] );
		$is_image  = $mime_type && strpos( $mime_type, 'image/' ) === 0;
		$webp_path = $is_image ? imagify_path_to_webp( $args['file_path'] ) : false;

		if ( $webp_path && $filesystem->is_writable( $webp_path ) ) {
			$filesystem->delete( $webp_path );
		}

		// In the database.
		$process->get_media()->delete_row();
	}

	/**
	 * Check if a file has been modified, and update the database accordingly.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  ProcessInterface $process          A \Imagify\Optimization\Process\ProcessInterface object.
	 * @param  bool             $is_folder_active Tell if the folder is active.
	 * @return int|bool|object  The file ID if modified. False if not modified. A WP_Error object if the entry has been removed from the database.
	 *                          The entry is removed from the database if:
	 *                          - The file doesn't exist anymore.
	 *                          - Or if its folder is not active and: the file has been modified, or the file is not optimized by Imagify, or the file is orphan (its folder is not in the database anymore).
	 */
	public static function refresh_file( $process, $is_folder_active = null ) {
		global $wpdb;

		if ( ! $process->is_valid() ) {
			return new \WP_Error( 'invalid_media', __( 'This media is not valid.', 'imagify' ) );
		}

		$filesystem = imagify_get_filesystem();
		$media      = $process->get_media();
		$file_path  = $media->get_fullsize_path();
		$mime_type  = $filesystem->get_mime_type( $file_path );
		$is_image   = $mime_type && strpos( $mime_type, 'image/' ) === 0;
		$webp_path  = $is_image ? imagify_path_to_webp( $file_path ) : false;
		$has_webp   = $webp_path && $filesystem->is_writable( $webp_path );
		$modified   = false;

		if ( ! $file_path || ! $filesystem->exists( $file_path ) ) {
			/**
			 * The file doesn't exist anymore.
			 */
			// Delete the backup file.
			$process->delete_backup();

			// Get the folder ID before removing the row.
			$folder_id = $media->get_row();
			$folder_id = $folder_id['folder_id'];

			// Remove the entry from the database.
			$media->delete_row();

			// Remove the corresponding folder if inactive and have no files left.
			self::remove_empty_inactive_folders( $folder_id );

			// Delete the webp version.
			if ( $has_webp ) {
				$filesystem->delete( $webp_path );
			}

			return new WP_Error( 'no-file', __( 'The file was missing or its path could not be retrieved from the database. The entry has been deleted from the database.', 'imagify' ) );
		}

		/**
		 * The file still exists.
		 */
		$old_data = $media->get_row();
		$new_data = [];

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
		if ( $new_data['modified'] || ! $process->get_data()->is_optimized() ) {
			if ( ! isset( $is_folder_active ) ) {
				$is_folder_active = $folder && $folder['active'];
			}

			// Its folder is not active: remove the entry from the database and delete the backup.
			if ( ! $is_folder_active ) {
				// Delete the backup file.
				$process->delete_backup();

				// Remove the entry from the database.
				$media->delete_row();

				// Remove the corresponding folder if inactive and have no files left.
				if ( $old_data['folder_id'] ) {
					self::remove_empty_inactive_folders( $old_data['folder_id'] );
				}

				// Delete the webp version.
				if ( $has_webp ) {
					$filesystem->delete( $webp_path );
				}

				return new WP_Error( 'folder-not-active', __( 'The file has been modified or was not optimized: its folder not being selected in the settings, the entry has been deleted from the database.', 'imagify' ) );
			}
		}

		$new_data['hash'] = $current_hash;

		// The file is modified.
		if ( $new_data['modified'] ) {
			// Delete all optimization data and update file data.
			$modified  = true;
			$mime_type = ! empty( $old_data['mime_type'] ) ? $old_data['mime_type'] : $filesystem->get_mime_type( $file_path );

			if ( $is_image ) {
				$size = $filesystem->get_image_size( $file_path );

				// Delete the webp version.
				if ( $has_webp ) {
					$filesystem->delete( $webp_path );
				}
			} else {
				$size = false;
			}

			$new_data = array_merge( $new_data, [
				'file_date'          => $filesystem->get_date( $file_path ),
				'width'              => $size ? $size['width']  : 0,
				'height'             => $size ? $size['height'] : 0,
				'original_size'      => $filesystem->size( $file_path ),
				'optimized_size'     => null,
				'percent'            => null,
				'optimization_level' => null,
				'status'             => null,
				'error'              => null,
				'data'               => [],
			] );

			// Delete the backup of the previous file.
			$process->delete_backup();
		} else {
			// Update file data to make sure nothing is missing.
			$backup_path = $media->get_backup_path();
			$path        = $backup_path ? $backup_path : $file_path;
			$mime_type   = ! empty( $old_data['mime_type'] ) ? $old_data['mime_type'] : $filesystem->get_mime_type( $path );
			$file_date   = ! empty( $old_data['file_date'] ) && '0000-00-00 00:00:00' !== $old_data['file_date'] ? $old_data['file_date'] : $filesystem->get_date( $path );

			if ( $is_image ) {
				$size = $filesystem->get_image_size( $path );
			} else {
				$size = false;
			}

			$new_data = array_merge( $new_data, [
				'file_date'     => $file_date,
				'width'         => $size ? $size['width']  : 0,
				'height'        => $size ? $size['height'] : 0,
				'original_size' => $filesystem->size( $path ),
			] );

			// Webp.
			$webp_size = 'full' . $process::WEBP_SUFFIX;

			if ( $has_webp && empty( $old_data['data'][ $webp_size ]['success'] ) ) {
				$webp_file_size = $filesystem->size( $webp_path );

				$old_data['data'][ $webp_size ] = [
					'success'        => true,
					'original_size'  => $new_data['original_size'],
					'optimized_size' => $webp_file_size,
					'percent'        => round( ( ( $new_data['original_size'] - $webp_file_size ) / $new_data['original_size'] ) * 100, 2 ),
				];
			}
		}

		// Save the new data.
		$old_data = array_intersect_key( $old_data, $new_data );
		ksort( $old_data );
		ksort( $new_data );

		if ( $old_data !== $new_data ) {
			$media->update_row( $new_data );
		}

		return $modified ? $media->get_id() : false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** FOLDERS AND FILES ======================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get folders from the DB.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args A list of arguments to tell more precisely what to fetch:
	 *                         - bool $active True to fetch only "active" folders (checked in the settings). False to fetch only folders that are not "active".
	 * @return array       An array of arrays containing the following values:
	 *                         - int    $folder_id   The folder ID.
	 *                         - string $path        The folder path, with placeholder.
	 *                         - int    $active      1 if the folder should be optimized. 0 otherwize.
	 *                         - string $folder_path The real absolute folder path.
	 *                     Example:
	 *                         Array(
	 *                             [7] => Array(
	 *                                 [folder_id] => 7
	 *                                 [path] => {{ROOT}}/custom-path/
	 *                                 [active] => 1
	 *                                 [folder_path] => /absolute/path/to/custom-path/
	 *                             )
	 *                             [13] => Array(
	 *                                 [folder_id] => 13
	 *                                 [path] => {{CONTENT}}/another-custom-path/
	 *                                 [active] => 1
	 *                                 [folder_path] => /absolute/path/to/wp-content/another-custom-path/
	 *                             )
	 *                         )
	 */
	public static function get_folders( $args = array() ) {
		global $wpdb;

		$folders_db    = Imagify_Folders_DB::get_instance();
		$folders_table = $folders_db->get_table_name();
		$primary_key   = $folders_db->get_primary_key();
		$where_active  = '';

		if ( isset( $args['active'] ) ) {
			if ( $args['active'] ) {
				$args['active'] = true;
				$where_active   = 'WHERE active = 1';
			} else {
				$args['active'] = false;
				$where_active   = 'WHERE active = 0';
			}
		}

		// Get the folders from the DB.
		$results = $wpdb->get_results( "SELECT * FROM $folders_table $where_active;", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $results || ! is_array( $results ) ) {
			return array();
		}

		// Cast results, add absolute paths.
		$folders = array();

		foreach ( $results as $row_fields ) {
			// Cast the row.
			$row_fields = $folders_db->cast_row( $row_fields );

			// Add the absolute path.
			$row_fields['folder_path'] = Imagify_Files_Scan::remove_placeholder( $row_fields['path'] );

			// Add the row to the list.
			$folders[ $row_fields[ $primary_key ] ] = $row_fields;
		}

		return $folders;
	}

	/**
	 * Get files belonging to the given folders.
	 * Files are scanned from the folders, then:
	 *     - If a file doesn't exist in the DB, it is added (maybe, depending on arguments provided).
	 *     - If a file is in the DB, but with a wrong folder_id, it is fixed.
	 *     - If a file doesn't exist, it is removed from the database and its backup is deleted.
	 *
	 * @since  1.7
	 * @access public
	 * @see    Imagify_Custom_Folders::get_folders()
	 * @author Grégory Viguier
	 *
	 * @param  array $folders An array of arrays containing at least the keys 'folder_path' and 'active'. See Imagify_Custom_Folders::get_folders() for the format.
	 * @param  array $args    A list of arguments to tell more precisely what to fetch:
	 *                            - int  $optimization_level        If set with an integer, only files that needs to be optimized to this level will be returned (the status is also checked).
	 *                            - bool $return_only_old_files     True to return only files that have not been newly inserted.
	 *                            - bool $add_inactive_folder_files When true: if a file is not in the database and its folder is not "active", it is added to the DB. Default false: new files are not added to the database if the folder is not active.
	 * @return array          A list of files in the following format:
	 *                            Array(
	 *                                [_2] => Array(
	 *                                    [file_id]            => 2
	 *                                    [folder_id]          => 7
	 *                                    [path]               => {{ROOT}}/custom-path/image-1.jpg
	 *                                    [optimization_level] => null
	 *                                    [status]             => null
	 *                                    [file_path]          => /absolute/path/to/custom-path/image-1.jpg
	 *                                ),
	 *                                [_3] => Array(
	 *                                    [file_id]            => 3
	 *                                    [folder_id]          => 7
	 *                                    [path]               => {{ROOT}}/custom-path/image-2.jpg
	 *                                    [optimization_level] => 2
	 *                                    [status]             => success
	 *                                    [file_path]          => /absolute/path/to/custom-path/image-2.jpg
	 *                                ),
	 *                                [_6] => Array(
	 *                                    [file_id]            => 6
	 *                                    [folder_id]          => 13
	 *                                    [path]               => {{CONTENT}}/another-custom-path/image-1.jpg
	 *                                    [optimization_level] => 0
	 *                                    [status]             => error
	 *                                    [file_path]          => /absolute/path/to/wp-content/another-custom-path/image-1.jpg
	 *                                ),
	 *                            )
	 *                            The fields 'optimization_level' and 'status' are set only if the argument 'optimization_level' was set.
	 */
	public static function get_files_from_folders( $folders, $args = array() ) {
		global $wpdb;

		if ( ! $folders ) {
			return array();
		}

		$filesystem     = imagify_get_filesystem();
		$files_db       = Imagify_Files_DB::get_instance();
		$files_table    = $files_db->get_table_name();
		$files_key      = $files_db->get_primary_key();
		$files_key_esc  = esc_sql( $files_key );

		$optimization              = isset( $args['optimization_level'] ) && is_numeric( $args['optimization_level'] );
		$no_new_files              = ! empty( $args['return_only_old_files'] );
		$add_inactive_folder_files = ! empty( $args['add_inactive_folder_files'] );

		/**
		 * Scan folders for files. $files_from_scan will be in the following format:
		 * Array(
		 *     [7] => Array(
		 *         [/absolute/path/to/custom-path/image-1.jpg] => 0
		 *         [/absolute/path/to/custom-path/image-2.jpg] => 1
		 *     )
		 *     [13] => Array(
		 *         [/absolute/path/to/wp-content/another-custom-path/image-1.jpg] => 0
		 *         [/absolute/path/to/wp-content/another-custom-path/image-2.jpg] => 1
		 *         [/absolute/path/to/wp-content/another-custom-path/image-3.jpg] => 2
		 *     )
		 * )
		 */
		$files_from_scan = array();

		foreach ( $folders as $folder_id => $folder ) {
			$files_from_scan[ $folder_id ] = Imagify_Files_Scan::get_files_from_folder( $folder['folder_path'] );

			if ( is_wp_error( $files_from_scan[ $folder_id ] ) ) {
				unset( $files_from_scan[ $folder_id ] );
			}
		}

		$files_from_scan = array_map( 'array_flip', $files_from_scan );

		/**
		 * Get the files from DB. $files_from_db will be in the same format as the function output.
		 */
		$already_optimized = array();
		$folder_ids        = array_keys( $folders );
		$files_from_db     = array_fill_keys( $folder_ids, array() );
		$folder_ids        = Imagify_DB::prepare_values_list( $folder_ids );
		$select_fields     = "$files_key_esc, folder_id, path" . ( $optimization ? ', optimization_level, status' : '' );

		if ( $optimization ) {
			$orderby = "
				CASE status
					WHEN 'already_optimized' THEN 3
					WHEN 'error' THEN 2
					ELSE 1
				END ASC,
				$files_key_esc DESC";
		} else {
			$orderby = "folder_id, $files_key_esc";
		}

		$results = $wpdb->get_results( "SELECT $select_fields FROM $files_table WHERE folder_id IN ( $folder_ids ) ORDER BY $orderby;", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( $results ) {
			$wpdb->flush();

			foreach ( $results as $i => $row_fields ) {
				// Cast the row.
				$row_fields = $files_db->cast_row( $row_fields );

				// Add the absolute path.
				$row_fields['file_path'] = Imagify_Files_Scan::remove_placeholder( $row_fields['path'] );

				// Remove the file from the scan.
				unset( $files_from_scan[ $row_fields['folder_id'] ][ $row_fields['file_path'] ] );

				if ( $optimization ) {
					if ( 'error' !== $row_fields['status'] && $row_fields['optimization_level'] === $args['optimization_level'] ) {
						// Try the same level only if the status is an error.
						continue;
					}

					if ( 'already_optimized' === $row_fields['status'] && $row_fields['optimization_level'] >= $args['optimization_level'] ) {
						// If the image is already compressed, optimize only if the requested level is higher.
						continue;
					}

					if ( 'success' === $row_fields['status'] && $args['optimization_level'] !== $row_fields['optimization_level'] ) {
						$file_backup_path = self::get_file_backup_path( $row_fields['file_path'] );

						if ( ! $file_backup_path || ! $filesystem->exists( $file_backup_path ) ) {
							// Don't try to re-optimize if there is no backup file.
							continue;
						}
					}
				}

				if ( ! $filesystem->exists( $row_fields['file_path'] ) ) {
					// If the file doesn't exist: remove all traces of it and bail out.
					self::delete_file( array(
						'file_id'   => $row_fields[ $files_key ],
						'file_path' => $row_fields['file_path'],
					) );
					continue;
				}

				if ( $optimization && 'already_optimized' === $row_fields['status'] ) {
					$already_optimized[ '_' . $row_fields[ $files_key ] ] = 1;
				}

				// Add the row to the list.
				$files_from_db[ $row_fields['folder_id'] ][ '_' . $row_fields[ $files_key ] ] = $row_fields;
			}
		}

		unset( $results );
		$files_from_scan = array_filter( $files_from_scan );

		// Make sure files from the scan are not already in the DB with another folder (shouldn't be possible, but, you know...).
		if ( $files_from_scan ) {
			$folders_by_placeholder = array();

			foreach ( $files_from_scan as $folder_id => $folder_files ) {
				foreach ( $folder_files as $file_path => $i ) {
					$placeholder = Imagify_Files_Scan::add_placeholder( $file_path );

					$folders_by_placeholder[ $placeholder ]      = $folder_id;
					$files_from_scan[ $folder_id ][ $file_path ] = $placeholder;
				}
			}

			$placeholders  = Imagify_DB::prepare_values_list( array_keys( $folders_by_placeholder ) );
			$select_fields = "$files_key_esc, folder_id, path" . ( $optimization ? ', optimization_level, status' : '' );

			$results = $wpdb->get_results( "SELECT $select_fields FROM $files_table WHERE path IN ( $placeholders ) ORDER BY folder_id, $files_key_esc;", ARRAY_A ); // WPCS: unprepared SQL ok.

			if ( $results ) {
				// Damn...
				$wpdb->flush();

				foreach ( $results as $i => $row_fields ) {
					// Cast the row.
					$row_fields    = $files_db->cast_row( $row_fields );
					$old_folder_id = $row_fields['folder_id'];

					// Add the absolute path.
					$row_fields['file_path'] = Imagify_Files_Scan::remove_placeholder( $row_fields['path'] );

					// Set the new folder ID.
					$row_fields['folder_id'] = $folders_by_placeholder[ $row_fields['path'] ];

					// Remove the file from everywhere.
					unset(
						$files_from_db[ $old_folder_id ][ '_' . $row_fields[ $files_key ] ],
						$files_from_scan[ $old_folder_id ][ $row_fields['file_path'] ],
						$files_from_scan[ $row_fields['folder_id'] ][ $row_fields['file_path'] ]
					);

					if ( $optimization ) {
						if ( 'error' !== $row_fields['status'] && $row_fields['optimization_level'] === $args['optimization_level'] ) {
							// Try the same level only if the status is an error.
							continue;
						}

						if ( 'already_optimized' === $row_fields['status'] && $row_fields['optimization_level'] >= $args['optimization_level'] ) {
							// If the image is already compressed, optimize only if the requested level is higher.
							continue;
						}

						if ( 'success' === $row_fields['status'] && $args['optimization_level'] !== $row_fields['optimization_level'] ) {
							$file_backup_path = self::get_file_backup_path( $row_fields['file_path'] );

							if ( ! $file_backup_path || ! $filesystem->exists( $file_backup_path ) ) {
								// Don't try to re-optimize if there is no backup file.
								continue;
							}
						}
					}

					if ( ! $filesystem->exists( $row_fields['file_path'] ) ) {
						// If the file doesn't exist: remove all traces of it and bail out.
						self::delete_file( array(
							'file_id'   => $row_fields[ $files_key ],
							'file_path' => $row_fields['file_path'],
						) );
						continue;
					}

					// Set the correct folder ID in the DB.
					$success = $files_db->update( $row_fields[ $files_key ], array(
						'folder_id' => $row_fields['folder_id'],
					) );

					if ( $success ) {
						if ( $optimization && 'already_optimized' === $row_fields['status'] ) {
							$already_optimized[ '_' . $row_fields[ $files_key ] ] = 1;
						}

						$files_from_db[ $row_fields['folder_id'] ][ '_' . $row_fields[ $files_key ] ] = $row_fields;
					}
				}
			}

			unset( $results, $folders_by_placeholder );
		}

		$files_from_scan = array_filter( $files_from_scan );

		// Insert the remaining files into the DB.
		if ( $files_from_scan ) {
			foreach ( $files_from_scan as $folder_id => $placeholders ) {
				// Don't add the file to the DB if its folder is not "active".
				if ( ! $add_inactive_folder_files && empty( $folders[ $folder_id ]['active'] ) ) {
					unset( $files_from_scan[ $folder_id ] );
					continue;
				}

				foreach ( $placeholders as $file_path => $placeholder ) {
					$file_id = self::insert_file( array(
						'folder_id' => $folder_id,
						'path'      => $placeholder,
						'file_path' => $file_path,
					) );

					if ( $file_id && ! $no_new_files ) {
						$files_from_db[ $folder_id ][ '_' . $file_id ] = array(
							'file_id'            => $file_id,
							'folder_id'          => $folder_id,
							'path'               => $placeholder,
							'optimization_level' => null,
							'status'             => null,
							'file_path'          => $file_path,
						);
					}
				}

				unset( $files_from_scan[ $folder_id ] );
			}
		}

		$files_from_db = array_filter( $files_from_db );

		if ( ! $files_from_db ) {
			return array();
		}

		$files_from_db = call_user_func_array( 'array_merge', $files_from_db );

		if ( $already_optimized ) {
			// Put the files already optimized at the end of the list.
			$already_optimized = array_intersect_key( $files_from_db, $already_optimized );
			$files_from_db     = array_diff_key( $files_from_db, $already_optimized );
			$files_from_db     = array_merge( $files_from_db, $already_optimized );
		}

		return $files_from_db;
	}

	/**
	 * Check if files inside the given folders have been modified, and update the database accordingly.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $folders A list of folders. See Imagify_Custom_Folders::get_folders() for the format.
	 */
	public static function synchronize_files_from_folders( $folders ) {
		global $wpdb;
		/**
		 * Get the files from DB, and from the folder.
		 */
		$files = self::get_files_from_folders( $folders, array(
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
			$process   = imagify_get_optimization_process( $file, 'custom-folders' );

			self::refresh_file( $process, $folders[ $folder_id ]['active'] );
		}

		foreach ( $folders as $folder_id => $folder ) {
			wp_cache_delete( 'custom_folder_' . $folder_id, 'imagify' );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WHEN SAVING SELECTED FOLDERS ============================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Dectivate all active folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public static function deactivate_all_folders() {
		self::deactivate_not_selected_folders();
	}

	/**
	 * Dectivate folders that are not selected.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array|object|string $selected_paths A list of "placeholdered" paths corresponding to the selected folders.
	 */
	public static function deactivate_not_selected_folders( $selected_paths = array() ) {
		global $wpdb;

		$folders_table = Imagify_Folders_DB::get_instance()->get_table_name();

		if ( $selected_paths ) {
			if ( is_array( $selected_paths ) || is_object( $selected_paths ) ) {
				$selected_paths = Imagify_DB::prepare_values_list( $selected_paths );
			}

			$selected_paths_clause = "AND path NOT IN ( $selected_paths )";
		} else {
			$selected_paths_clause = '';
		}

		// Remove the active status from the folders that are not selected.
		$wpdb->query( "UPDATE $folders_table SET active = 0 WHERE active != 0 $selected_paths_clause" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Activate folders that are selected.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array|object $selected_paths A list of "placeholdered" paths corresponding to the selected folders.
	 * @return array                        An array of paths of folders that are not in the DB.
	 */
	public static function activate_selected_folders( $selected_paths ) {
		global $wpdb;

		if ( ! $selected_paths ) {
			return $selected_paths;
		}

		$folders_db    = Imagify_Folders_DB::get_instance();
		$folders_table = $folders_db->get_table_name();
		$folders_key   = $folders_db->get_primary_key();

		$selected_paths = (array) $selected_paths;
		$selected_in    = Imagify_DB::prepare_values_list( $selected_paths );

		// Get folders that already are in the DB.
		$folders = $wpdb->get_results( "SELECT * FROM $folders_table WHERE path IN ( $selected_in );", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $folders ) {
			return $selected_paths;
		}

		$selected_paths = array_flip( $selected_paths );

		foreach ( $folders as $folder ) {
			$folder = $folders_db->cast_row( $folder );

			if ( Imagify_Files_Scan::placeholder_path_exists( $folder['path'] ) ) {
				if ( ! $folder['active'] ) {
					// Add the active status only if not already set and if the folder exists.
					$folders_db->update( $folder[ $folders_key ], array(
						'active' => 1,
					) );
				}
			} else {
				// Remove the active status if the folder does not exist.
				$folders_db->update( $folder[ $folders_key ], array(
					'active' => 0,
				) );
			}

			// Remove the path from the selected list, so the remaining will be created.
			unset( $selected_paths[ $folder['path'] ] );
		}

		// Paths of folders that are not in the DB.
		return array_flip( $selected_paths );
	}

	/**
	 * Insert folders into the database.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $folders An array of "placeholdered" paths.
	 * @return array          An array of folder IDs.
	 */
	public static function insert_folders( $folders ) {
		if ( ! $folders ) {
			return array();
		}

		$folder_ids = array();
		$filesystem = imagify_get_filesystem();
		$folders_db = Imagify_Folders_DB::get_instance();

		foreach ( $folders as $placeholder ) {
			$full_path = Imagify_Files_Scan::remove_placeholder( $placeholder );
			$full_path = realpath( $full_path );

			if ( ! $full_path || ! $filesystem->is_readable( $full_path ) || ! $filesystem->is_dir( $full_path ) ) {
				continue;
			}

			if ( Imagify_Files_Scan::is_path_forbidden( trailingslashit( $full_path ) ) ) {
				continue;
			}

			$folder_ids[] = $folders_db->insert( array(
				'path'   => $placeholder,
				'active' => 1,
			) );
		}

		return array_filter( $folder_ids );
	}

	/**
	 * Remove files that are in inactive folders and are not optimized.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public static function remove_unoptimized_files_from_inactive_folders() {
		global $wpdb;

		$folders_db  = Imagify_Folders_DB::get_instance();
		$folders_key = $folders_db->get_primary_key();
		$files_table = Imagify_Files_DB::get_instance()->get_table_name();
		$folder_ids  = $folders_db->get_active_folders_column( $folders_key );

		if ( $folder_ids ) {
			$folder_ids = Imagify_DB::prepare_values_list( $folder_ids );

			$wpdb->query( "DELETE FROM $files_table WHERE folder_id NOT IN ( $folder_ids ) AND ( status != 'success' OR status IS NULL )" ); // WPCS: unprepared SQL ok.
		} else {
			$wpdb->query( "DELETE FROM $files_table WHERE status != 'success' OR status IS NULL" ); // WPCS: unprepared SQL ok.
		}
	}

	/**
	 * Reassign inactive files to active folders.
	 * Example:
	 * - Consider the file "/a/b/c/d/file.png".
	 * - The folder "/a/b/c/", previously active, becomes inactive.
	 * - The folder "/a/b/", previously inactive, becomes active.
	 * - The file is reassigned to the folder "/a/b/".
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public static function reassign_inactive_files() {
		global $wpdb;

		$folders_db      = Imagify_Folders_DB::get_instance();
		$folders_table   = $folders_db->get_table_name();
		$folders_key     = $folders_db->get_primary_key();
		$folders_key_esc = esc_sql( $folders_key );

		$files_db      = Imagify_Files_DB::get_instance();
		$files_table   = $files_db->get_table_name();
		$files_key     = $files_db->get_primary_key();
		$files_key_esc = esc_sql( $files_key );

		// All active folders.
		$active_folders = $wpdb->get_results( "SELECT $folders_key_esc, path FROM $folders_table WHERE active = 1;", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $active_folders ) {
			return;
		}

		$active_folder_ids = array();
		$has_site_root     = false;

		foreach ( $active_folders as $i => $active_folder ) {
			$active_folders[ $i ] = $folders_db->cast_row( $active_folder );
			$active_folder_ids[]  = $active_folders[ $i ][ $folders_key ];

			if ( '{{ROOT}}/' === $active_folders[ $i ]['path'] ) {
				$has_site_root = true;
				break;
			}
		}

		// Files not in active folders.
		$active_folder_ids = Imagify_DB::prepare_values_list( $active_folder_ids );
		$inactive_files    = $wpdb->get_results( "SELECT $files_key_esc, path FROM $files_table WHERE folder_id NOT IN ( $active_folder_ids )", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $inactive_files ) {
			return;
		}

		$filesystem         = imagify_get_filesystem();
		$file_ids_by_folder = array();
		$active_folders     = self::sort_folders( $active_folders, true );

		foreach ( $inactive_files as $inactive_file ) {
			$inactive_file              = $files_db->cast_row( $inactive_file );
			$inactive_file['full_path'] = Imagify_Files_Scan::remove_placeholder( $inactive_file['path'] );

			if ( $has_site_root ) {
				$inactive_file['dirname'] = $filesystem->dir_path( $inactive_file['full_path'] );
			}

			foreach ( $active_folders as $active_folder ) {
				$folder_id = $active_folder[ $folders_key ];

				if ( strpos( $inactive_file['full_path'], $active_folder['full_path'] ) !== 0 ) {
					// The file is not in this folder.
					continue;
				}

				if ( ! isset( $file_ids_by_folder[ $folder_id ] ) ) {
					$file_ids_by_folder[ $folder_id ] = array();
				}

				if ( '{{ROOT}}/' === $active_folder['path'] ) {
					// For the site's root: only direct childs.
					if ( $inactive_file['dirname'] === $active_folder['full_path'] ) {
						// This file is in the site's root folder.
						$file_ids_by_folder[ $folder_id ][] = $inactive_file[ $files_key ];
					}
					break;
				}

				// This file is not in the site's root, but still a grand-child of this folder.
				$file_ids_by_folder[ $folder_id ][] = $inactive_file[ $files_key ];
				break;
			}
		}

		$file_ids_by_folder = array_filter( $file_ids_by_folder );

		if ( ! $file_ids_by_folder ) {
			return;
		}

		// Set the new folder ID.
		foreach ( $file_ids_by_folder as $folder_id => $file_ids ) {
			$file_ids = Imagify_DB::prepare_values_list( $file_ids );

			$wpdb->query( "UPDATE $files_table SET folder_id = $folder_id WHERE $files_key_esc IN ( $file_ids )" ); // WPCS: unprepared SQL ok.
		}
	}

	/**
	 * Remove the given folders from the DB if they are inactive and have no files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $folder_ids An array of folder IDs.
	 * @return int               Number of removed folders.
	 */
	public static function remove_empty_inactive_folders( $folder_ids = null ) {
		global $wpdb;

		$folders_db      = Imagify_Folders_DB::get_instance();
		$folders_table   = $folders_db->get_table_name();
		$folders_key     = $folders_db->get_primary_key();
		$folders_key_esc = esc_sql( $folders_key );
		$files_table     = Imagify_Files_DB::get_instance()->get_table_name();

		$folder_ids = array_filter( (array) $folder_ids );

		if ( $folder_ids ) {
			$folder_ids = $folders_db->cast_col( $folder_ids, $folders_key );
			$folder_ids = Imagify_DB::prepare_values_list( $folder_ids );
			$in_clause  = "folders.$folders_key_esc IN ( $folder_ids )";
		} else {
			$in_clause = '1=1';
		}

		// Within the range of given folder IDs, filter the ones that are inactive and have no files.
		$results = $wpdb->get_col( // WPCS: unprepared SQL ok.
			"
			SELECT folders.$folders_key_esc FROM $folders_table AS folders
				LEFT JOIN $files_table AS files ON folders.$folders_key_esc = files.folder_id
			WHERE $in_clause
				AND folders.active != 1
				AND files.folder_id IS NULL"
		);

		if ( ! $results ) {
			return 0;
		}

		$results = $folders_db->cast_col( $results, $folders_key );
		$results = Imagify_DB::prepare_values_list( $results );

		// Remove inactive folders with no files.
		$wpdb->query( "DELETE FROM $folders_table WHERE $folders_key_esc IN ( $results )" ); // WPCS: unprepared SQL ok.

		return (int) $wpdb->rows_affected;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Sort folders by full path.
	 * The row "full_path" is added to each folder.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $folders An array of folders with at least a "path" row.
	 * @param  bool  $reverse Reverse the order.
	 * @return array
	 */
	public static function sort_folders( $folders, $reverse = false ) {
		if ( ! $folders ) {
			return array();
		}

		$keyed_folders = array();
		$keyed_paths   = array();

		foreach ( $folders as $folder ) {
			$folder              = (array) $folder;
			$folder['full_path'] = Imagify_Files_Scan::remove_placeholder( $folder['path'] );

			$keyed_folders[ $folder['path'] ] = $folder;
			$keyed_paths[ $folder['path'] ]   = $folder['full_path'];
		}

		natcasesort( $keyed_paths );

		if ( $reverse ) {
			$keyed_paths = array_reverse( $keyed_paths, true );
		}

		$keyed_folders = array_merge( $keyed_paths, $keyed_folders );

		return array_values( $keyed_folders );
	}

	/**
	 * Remove sub-paths: if 'a/b/' and 'a/b/c/' are in the array, we keep only the "parent" 'a/b/'.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $placeholders A list of "placeholdered" paths.
	 * @return array
	 */
	public static function remove_sub_paths( $placeholders ) {
		sort( $placeholders );

		foreach ( $placeholders as $i => $placeholder_path ) {
			if ( '{{ROOT}}/' === $placeholder_path ) {
				continue;
			}

			if ( ! isset( $prev_path ) ) {
				$prev_path = strtolower( Imagify_Files_Scan::remove_placeholder( $placeholder_path ) );
				continue;
			}

			$placeholder_path = strtolower( Imagify_Files_Scan::remove_placeholder( $placeholder_path ) );

			if ( strpos( $placeholder_path, $prev_path ) === 0 ) {
				unset( $placeholders[ $i ] );
			} else {
				$prev_path = $placeholder_path;
			}
		}

		return $placeholders;
	}
}
