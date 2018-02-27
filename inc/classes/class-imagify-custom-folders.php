<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
	const VERSION = '1.0';

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
			$args['file_date'] = imagify_get_file_date( $args['file_path'] );
		}

		if ( empty( $args['mime_type'] ) ) {
			$args['mime_type'] = imagify_get_mime_type_from_file( $args['file_path'] );
		}

		if ( ( empty( $args['width'] ) || empty( $args['height'] ) ) && strpos( $args['mime_type'], 'image/' ) === 0 ) {
			$file_size      = @getimagesize( $args['file_path'] );
			$args['width']  = $file_size && isset( $file_size[0] ) ? $file_size[0] : 0;
			$args['height'] = $file_size && isset( $file_size[1] ) ? $file_size[1] : 0;
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
	 * Get folders from the DB.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args A list of arguments to tell more precisely what to fetch:
	 *                         - bool $active True to fetch only "active" folders (checked in the settings). False to fetch only folders that are not "active".
	 * @return array       An array of arrays containing the following keys:
	 *                         - int    $folder_id   The folder ID.
	 *                         - string $path        The folder path, with placeholder.
	 *                         - int    $active      1 if the folder should be optimized. 0 otherwize.
	 *                         - string $folder_path The real absolute folder path.
	 *                     Example:
	 *                         Array(
	 *                             [7] => Array(
	 *                                 [folder_id] => 7
	 *                                 [path] => {{ABSPATH}}/custom-path/
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
	 *                                    [path]               => {{ABSPATH}}/custom-path/image-1.jpg
	 *                                    [optimization_level] => null
	 *                                    [status]             => null
	 *                                    [file_path]          => /absolute/path/to/custom-path/image-1.jpg
	 *                                ),
	 *                                [_3] => Array(
	 *                                    [file_id]            => 3
	 *                                    [folder_id]          => 7
	 *                                    [path]               => {{ABSPATH}}/custom-path/image-2.jpg
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
						$file_backup_path = imagify_get_file_backup_path( $row_fields['file_path'] );

						if ( ! $file_backup_path || ! $filesystem->exists( $file_backup_path ) ) {
							// Don't try to re-optimize if there is no backup file.
							continue;
						}
					}
				}

				if ( ! $filesystem->exists( $row_fields['file_path'] ) ) {
					// If the file doesn't exist: remove all traces of it and bail out.
					imagify_delete_custom_file( array(
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
							$file_backup_path = imagify_get_file_backup_path( $row_fields['file_path'] );

							if ( ! $file_backup_path || ! $filesystem->exists( $file_backup_path ) ) {
								// Don't try to re-optimize if there is no backup file.
								continue;
							}
						}
					}

					if ( ! $filesystem->exists( $row_fields['file_path'] ) ) {
						// If the file doesn't exist: remove all traces of it and bail out.
						imagify_delete_custom_file( array(
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
		global $wpdb;

		$folders_table = Imagify_Folders_DB::get_instance()->get_table_name();

		$wpdb->query( "UPDATE $folders_table SET active = 0 WHERE active = 1" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Dectivate folders that are not selected.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array|object|string $selected_paths A list of "placeholdered" paths.
	 */
	public static function deactivate_not_selected_folders( $selected_paths ) {
		global $wpdb;

		$folders_table = Imagify_Folders_DB::get_instance()->get_table_name();

		if ( is_array( $selected_paths ) || is_object( $selected_paths ) ) {
			$selected_paths = Imagify_DB::prepare_values_list( $selected_paths );
		}

		// Remove the active status from the folders that are not selected.
		$wpdb->query( "UPDATE $folders_table SET active = 0 WHERE active = 1 AND path NOT IN ( $selected_paths )" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Activate folders that are selected.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array|object $selected_paths A list of "placeholdered" paths.
	 * @return array                        An array of paths of folders that are not in the DB.
	 */
	public static function activate_selected_folders( $selected_paths ) {
		global $wpdb;

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

			if ( Imagify_Files_Scan::is_path_forbidden( $full_path ) ) {
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
		$folder_ids  = $folders_db->get_inactive_folders_column( $folders_key );

		if ( ! $folder_ids ) {
			return;
		}

		$files_table = Imagify_Files_DB::get_instance()->get_table_name();
		$folder_ids  = Imagify_DB::prepare_values_list( $folder_ids );

		$wpdb->query( "DELETE FROM $files_table WHERE folder_id IN ( $folder_ids ) AND ( status != 'success' OR status IS NULL )" ); // WPCS: unprepared SQL ok.
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
		$has_abspath       = false;

		foreach ( $active_folders as $i => $active_folder ) {
			$active_folders[ $i ] = $folders_db->cast_row( $active_folder );
			$active_folder_ids[]  = $active_folders[ $i ][ $folders_key ];

			if ( '{{ABSPATH}}/' === $active_folders[ $i ]['path'] ) {
				$has_abspath = true;
			}
		}

		// Files not in active folders.
		$active_folder_ids = Imagify_DB::prepare_values_list( $active_folder_ids );
		$inactive_files    = $wpdb->get_results( "SELECT $files_key_esc, path FROM $files_table WHERE folder_id NOT IN ( $active_folder_ids )", ARRAY_A ); // WPCS: unprepared SQL ok.

		if ( ! $inactive_files ) {
			return;
		}

		$file_ids_by_folder = array();
		$active_folders     = self::sort_folders( $active_folders, true );

		foreach ( $inactive_files as $inactive_file ) {
			$inactive_file              = $files_db->cast_row( $inactive_file );
			$inactive_file['full_path'] = Imagify_Files_Scan::remove_placeholder( $inactive_file['path'] );

			if ( $has_abspath ) {
				$inactive_file['dirname'] = trailingslashit( dirname( $inactive_file['full_path'] ) );
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

				if ( '{{ABSPATH}}/' === $active_folder['path'] ) {
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
			if ( '{{ABSPATH}}/' === $placeholder_path ) {
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
