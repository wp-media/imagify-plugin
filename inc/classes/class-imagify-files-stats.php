<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	 * @return int The number of images.
	 */
	public static function count_all_files() {
		/**
		 * Filter the number of images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'all' );
	}

	/**
	 * Count number of images in custom folders with an error.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of images.
	 */
	public static function count_error_files() {
		/**
		 * Filter the number of images in custom folders with an error.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_error_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'error' );
	}

	/**
	 * Count number of images successfully optimized by Imagify in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of images.
	 */
	public static function count_success_files() {
		/**
		 * Filter the number of images successfully optimized by Imagify in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_success_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'success' );
	}

	/**
	 * Count number of optimized images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of images.
	 */
	public static function count_optimized_files() {
		/**
		 * Filter the number of optimized images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_optimized_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'optimized' );
	}

	/**
	 * Count number of unoptimized images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of images.
	 */
	public static function count_unoptimized_files() {
		/**
		 * Filter the number of unoptimized images in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_unoptimized_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'unoptimized' );
	}

	/**
	 * Count number of images without status in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The number of images.
	 */
	public static function count_no_status_files() {
		/**
		 * Filter the number of images without status in custom folders.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_count Default is false. Provide an integer.
		 */
		$pre_count = apply_filters( 'imagify_count_no_status_files', false );

		if ( false !== $pre_count ) {
			return (int) $pre_count;
		}

		return self::count_files( 'none' );
	}

	/**
	 * Count number of images in custom folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $status The status of these folders: all, success, already_optimized, optimized, error, none, unoptimized.
	 *                        "none" if for files without status.
	 *                        "optimized" regroups "success" and "already_optimized".
	 *                        "unoptimized" regroups "error" and "none".
	 * @return int            The number of images.
	 */
	public static function count_files( $status = 'all' ) {
		global $wpdb;
		static $count = array();

		$status = self::validate_status( $status );

		if ( isset( $count[ $status ] ) ) {
			return $count[ $status ];
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$count[ $status ] = 0;
			return $count[ $status ];
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
		$status     = $status ? "WHERE $status" : '';

		$count[ $status ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
			"SELECT COUNT( file_id ) FROM $table_name $status"
		);

		return $count[ $status ];
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
	 * @return int The sizes sum in bytes.
	 */
	public static function get_optimized_size() {
		/**
		 * Filter the optimized sizes of all successfully optimized files.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_size Default is false. Provide an integer.
		 */
		$pre_size = apply_filters( 'imagify_get_optimized_files_size', false );

		if ( false !== $pre_size ) {
			return (int) $pre_size;
		}

		return self::get_size( 'optimized' );
	}

	/**
	 * Sum up all original sizes of all successfully optimized files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The sizes sum in bytes.
	 */
	public static function get_original_size() {
		/**
		 * Filter the original sizes of all successfully optimized files.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int|bool $pre_size Default is false. Provide an integer.
		 */
		$pre_size = apply_filters( 'imagify_get_original_files_size', false );

		if ( false !== $pre_size ) {
			return (int) $pre_size;
		}

		return self::get_size( 'original' );
	}

	/**
	 * Sum up all (optimized|original) sizes of all successfully optimized files.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $type "optimized" or "original".
	 * @return int          The sizes sum in bytes.
	 */
	public static function get_size( $type = null ) {
		global $wpdb;
		static $sizes = array();

		$type = 'optimized' === $type ? 'optimized_size' : 'original_size';

		if ( isset( $sizes[ $type ] ) ) {
			return $sizes[ $type ];
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$sizes[ $type ] = 0;
			return $sizes[ $type ];
		}

		$table_name     = $files_db->get_table_name();
		$sizes[ $type ] = (int) $wpdb->get_var( // WPCS: unprepared SQL ok.
			"SELECT SUM( $type ) FROM $table_name WHERE status = 'success'"
		);

		return $sizes[ $type ];
	}

	/**
	 * Sum up all original sizes.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The sizes sum in bytes.
	 */
	public static function get_overall_original_size() {
		global $wpdb;
		static $size;

		if ( isset( $size ) ) {
			return $size;
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$size = 0;
			return $size;
		}

		$table_name = $files_db->get_table_name();
		$size       = round( $wpdb->get_var( "SELECT SUM( original_size ) FROM $table_name" ) ); // WPCS: unprepared SQL ok.

		return $size;
	}

	/**
	 * Calculate the average size of the images uploaded per month.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int The current average size of images uploaded per month in bytes.
	 */
	public static function calculate_average_size_per_month() {
		global $wpdb;
		static $average;

		if ( isset( $average ) ) {
			return $average;
		}

		$files_db = Imagify_Files_DB::get_instance();

		if ( ! $files_db->can_operate() ) {
			$average = 0;
			return $average;
		}

		$table_name = $files_db->get_table_name();
		$average    = round( $wpdb->get_var( "SELECT AVG( size ) AS average_size_per_month FROM ( SELECT SUM( original_size ) AS size FROM $table_name GROUP BY YEAR( file_date ), MONTH( file_date ) ) AS size_per_month" ) ); // WPCS: unprepared SQL ok.

		return $average;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

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
}
