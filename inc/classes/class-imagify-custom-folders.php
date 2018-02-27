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
	 * Get folders from the DB.
	 *
	 * @since  1.7
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
