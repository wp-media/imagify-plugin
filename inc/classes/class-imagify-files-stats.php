<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class handling stats related to "custom folders optimization".
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Files_Stats {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';


	/** ----------------------------------------------------------------------------------------- */
	/** COUNT FILES ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Count number of images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The number of images.
	 */
	public static function count_all_files( $folder_type = 'all' ) {
		global $wpdb;

		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the number of images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count   Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_count = apply_filters( 'imagify_count_files', false, $folder_type );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( $folder_type, 'all' );
	}

	/**
	 * Count number of images in custom folders with an error.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The number of images.
	 */
	public static function count_error_files( $folder_type = 'all' ) {
		global $wpdb;

		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the number of images in custom folders with an error.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count   Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_count = apply_filters( 'imagify_count_error_files', false, $folder_type );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( $folder_type, 'error' );
	}

	/**
	 * Count number of optimized images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The number of images.
	 */
	public static function count_optimized_files( $folder_type = 'all' ) {
		global $wpdb;

		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the number of optimized images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count   Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_count = apply_filters( 'imagify_count_optimized_files', false, $folder_type );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( $folder_type, 'optimized' );
	}

	/**
	 * Count number of unoptimized images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The number of images.
	 */
	public static function count_unoptimized_files( $folder_type = 'all' ) {
		global $wpdb;

		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the number of unoptimized images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count   Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_count = apply_filters( 'imagify_count_unoptimized_files', false, $folder_type );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( $folder_type, 'unoptimized' );
	}

	/**
	 * Count number of images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @param  string $status      The status of these folders: all, success, already_optimized, optimized, error, none, unoptimized.
	 *                             "none" if for files without status.
	 *                             "optimized" regroups "success" and "already_optimized".
	 *                             "unoptimized" regroups "error" and "none".
	 * @return int                 The number of images.
	 */
	public static function count_files( $folder_type = 'all', $status = 'all' ) {
		global $wpdb;
		static $count = array();

		$folder_type = self::validate_folder_type( $folder_type );
		$status      = self::validate_status( $status );
		$key         = $folder_type . '|' . $status;

		if ( isset( $count[ $key ] ) ) {
			return $count[ $key ];
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$count[ $key ] = 0;
			return $count[ $key ];
		}

		switch ( $status ) {
			case 'all':
				$status = '';
				break;

			case 'none':
				$status = 'status IS NULL';
				break;

			case 'optimized':
				$status = "status IN ('success','already_optimized')";
				break;

			case 'unoptimized':
				$status = "( status = 'error' OR status IS NULL )";
				break;

			default:
				// "success", "already_optimized", "error".
				$status = "status = '$status'";
		}

		$table_name = $files_db->get_table_name();

		switch ( $folder_type ) {
			case 'all':
				$status = $status ? "WHERE $status" : '';

				$count[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT COUNT( file_id ) FROM $table_name $status"
				);

				return $count[ $key ];

			case 'themes':
				$themes = self::get_theme_folders();
				$status = $status ? "AND $status" : '';

				$count[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT COUNT( file_id ) FROM $table_name WHERE folder_id IN ($themes) $status"
				);

				return $count[ $key ];

			case 'plugins':
				$plugins = self::get_plugin_folders();
				$status  = $status ? "AND $status" : '';

				$count[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT COUNT( file_id ) FROM $table_name WHERE folder_id IN ($plugins) $status"
				);

				return $count[ $key ];

			case 'custom-folders':
				$themes     = self::get_theme_folders();
				$plugins    = self::get_plugin_folders();
				$folder_ids = trim( $themes . ',' . $plugins, ',' );
				$status     = $status ? "AND $status" : '';

				$count[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT COUNT( file_id ) FROM $table_name WHERE folder_id NOT IN ($folder_ids) $status"
				);

				return $count[ $key ];
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PERCENTS ================================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Count percent of optimized images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The percent of optimized images.
	 */
	public static function percent_optimized_files() {
		/**
		 * Filter the percent of optimized images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $percent Default is false. Provide an integer.
		 */
		$percent = apply_filters( 'imagify_percent_optimized_files', false );

		if ( false !== $percent ) {
			return (int) $percent;
		}

		$total_files           = self::count_all_files();
		$total_optimized_files = self::count_optimized_files();

		if ( ! $total_files || ! $total_optimized_files ) {
			return 0;
		}

		return min( round( 100 * $total_optimized_files / $total_files ), 100 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GET FILE SIZES ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Sum up all optimized sizes of all successfully optimized files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The sizes sum in bytes.
	 */
	public static function get_optimized_size( $folder_type = 'all' ) {
		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the optimized sizes of all successfully optimized files.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_size    Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_size = apply_filters( 'imagify_get_optimized_files_size', false, $folder_type );

		if ( false !== $pre_size ) {
			return (int) $pre_size;
		}

		return self::get_size( $folder_type, 'optimized' );
	}

	/**
	 * Sum up all original sizes of all successfully optimized files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return int                 The sizes sum in bytes.
	 */
	public static function get_original_size( $folder_type = 'all' ) {
		$folder_type = self::validate_folder_type( $folder_type );

		/**
		 * Filter the original sizes of all successfully optimized files.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_size    Default is false. Provide an integer.
		 * @param string   $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
		 */
		$pre_size = apply_filters( 'imagify_get_original_files_size', false, $folder_type );

		if ( false !== $pre_size ) {
			return (int) $pre_size;
		}

		return self::get_size( $folder_type, 'original' );
	}

	/**
	 * Sum up all (optimized|original) sizes of all successfully optimized files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @param  string $type        "optimized" or "original".
	 * @return int                 The sizes sum in bytes.
	 */
	public static function get_size( $folder_type = 'all', $type = null ) {
		global $wpdb;
		static $sizes = array();

		$folder_type = self::validate_folder_type( $folder_type );
		$type        = 'optimized' === $type ? 'optimized_size' : 'original_size';
		$key         = $folder_type . '|' . $type;

		if ( isset( $sizes[ $key ] ) ) {
			return $sizes[ $key ];
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$sizes[ $key ] = 0;
			return $sizes[ $key ];
		}

		$table_name = $files_db->get_table_name();

		switch ( $folder_type ) {
			case 'all':
				$sizes[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT SUM( $type ) FROM $table_name WHERE status = 'success'"
				);

				return $sizes[ $key ];

			case 'themes':
				$themes = self::get_theme_folders();

				$sizes[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT SUM( $type ) FROM $table_name WHERE folder_id IN ($themes) AND status = 'success'"
				);

				return $sizes[ $key ];

			case 'plugins':
				$plugins = self::get_plugin_folders();

				$sizes[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT SUM( $type ) FROM $table_name WHERE folder_id IN ($plugins) AND status = 'success'"
				);

				return $sizes[ $key ];

			case 'custom-folders':
				$themes     = self::get_theme_folders();
				$plugins    = self::get_plugin_folders();
				$folder_ids = trim( $themes . ',' . $plugins, ',' );

				$sizes[ $key ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
					"SELECT SUM( $type ) FROM $table_name WHERE folder_id NOT IN ($folder_ids) AND status = 'success'"
				);

				return $sizes[ $key ];
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Validate a type of folder.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder_type The type of folder we want stats from: all, themes, plugins, custom-folders.
	 * @return string              Fallback to 'all' if the type is not valid.
	 */
	public static function validate_folder_type( $folder_type ) {
		$folder_types = array(
			'all'            => 1,
			'themes'         => 1,
			'plugins'        => 1,
			'custom-folders' => 1,
		);

		return isset( $folder_types[ $folder_type ] ) ? $folder_type : 'all';
	}

	/**
	 * Validate a status.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $status The status of these folders: all, success, already_optimized, optimized, error, none, unoptimized.
	 *                        "none" if for files without status.
	 *                        "optimized" regroups "success" and "already_optimized".
	 *                        "unoptimized" regroups "error" and "none".
	 * @return string         Fallback to 'all' if the status is not valid.
	 */
	public static function validate_status( $status = 'all' ) {
		$statuses = array(
			'all'               => 1,
			'success'           => 1,
			'already_optimized' => 1,
			'error'             => 1,
			'none'              => 1,
			'optimized'         => 1,
			'unoptimized'       => 1,
		);

		return isset( $statuses[ $status ] ) ? $status : 'all';
	}

	/**
	 * Get installed theme folders.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string A list of folder IDs, ready to be used in a "IN" SQL clause.
	 */
	public static function get_theme_folders() {
		static $folders;

		if ( isset( $folders ) ) {
			return $folders;
		}

		$themes = imagify_get_theme_folders();

		if ( ! $themes ) {
			$folders = '0';
			return $folders;
		}

		$themes  = array_keys( $themes );
		$folders = Imagify_Folders_DB::get_instance()->get_column_in( 'folder_id', 'path', $themes );
		$folders = $folders ? implode( ',', $folders ) : '0';

		return $folders;
	}

	/**
	 * Get installed plugin folders.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string A list of folder IDs, ready to be used in a "IN" SQL clause.
	 */
	public static function get_plugin_folders() {
		static $folders;

		if ( isset( $folders ) ) {
			return $folders;
		}

		$plugins = imagify_get_plugin_folders();

		if ( ! $plugins ) {
			$folders = '0';
			return $folders;
		}

		$plugins = array_keys( $plugins );
		$folders = Imagify_Folders_DB::get_instance()->get_column_in( 'folder_id', 'path', $plugins );
		$folders = $folders ? implode( ',', $folders ) : '0';

		return $folders;
	}
}
