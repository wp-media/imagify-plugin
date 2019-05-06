<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify Attachment class for custom folders.
 *
 * @since  1.7
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_File_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	const VERSION = '1.1';

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $db_class_name = 'Imagify_Files_DB';

	/**
	 * Tell if the optimization status is network-wide.
	 *
	 * @var    bool
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $optimization_state_network_wide = true;

	/**
	 * The constructor.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int|array|object $id Thefile ID.
	 */
	public function __construct( $id = 0 ) {
		imagify_deprecated_class( get_class( $this ), '1.9', '\\Imagify\\Optimization\\Process\\CustomFolders( $id )' );

		if ( is_numeric( $id ) ) {
			$this->id = (int) $id;
			$this->get_row();
		} elseif ( is_array( $id ) || is_object( $id ) ) {
			$prim_key  = $this->get_row_db_instance()->get_primary_key();
			$this->row = (array) $id;
			$this->id  = $this->row[ $prim_key ];
		} else {
			$this->invalidate_row();
		}

		$this->filesystem                   = Imagify_Filesystem::get_instance();
		$this->optimization_state_transient = 'imagify-file-async-in-progress-' . $this->id;
	}

	/**
	 * Get the original file path.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_original_path() {
		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return '';
		}

		return Imagify_Files_Scan::remove_placeholder( $row['path'] );
	}

	/**
	 * Get the original file URL.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_original_url() {
		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return '';
		}

		return Imagify_Files_Scan::remove_placeholder( $row['path'], 'url' );
	}

	/**
	 * Get the backup file path, even if the file doesn't exist.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_backup_path() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return Imagify_Custom_Folders::get_file_backup_path( $this->get_original_path() );
	}

	/**
	 * Get the backup URL.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_backup_url() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		return site_url( '/' ) . $this->filesystem->make_path_relative( $this->get_raw_backup_path() );
	}

	/**
	 * Get the optimization data.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_data() {
		if ( ! $this->is_valid() ) {
			return array();
		}

		$data = array_merge( $this->get_row_db_instance()->get_column_defaults(), $this->get_row() );

		unset( $data['file_id'] );
		return $data;
	}

	/**
	 * Get the optimization level.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int|bool
	 */
	public function get_optimization_level() {
		$row = $this->get_row();
		return isset( $row['optimization_level'] ) && is_int( $row['optimization_level'] ) ? $row['optimization_level'] : false;
	}

	/**
	 * Get the file optimization status (success, already_optimized, or error).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_status() {
		$row = $this->get_row();
		return ! empty( $row['status'] ) ? $row['status'] : '';
	}

	/**
	 * Get width and height of the original image.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_dimensions() {
		$row = $this->get_row();

		return array(
			'width'  => ! empty( $row['width'] )  ? $row['width']  : 0,
			'height' => ! empty( $row['height'] ) ? $row['height'] : 0,
		);
	}

	/**
	 * Get the attachment error if there is one.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The message error
	 */
	public function get_optimized_error() {
		$row = $this->get_row();
		return ! empty( $row['error'] ) && is_string( $row['error'] ) ? trim( $row['error'] ) : '';
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_optimized_sizes_count() {
		return $this->get_status() === 'success' ? 1 : 0;
	}

	/**
	 * Delete the data related to optimization.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_imagify_data() {
		if ( ! $this->is_valid() ) {
			return;
		}

		$this->update_row( $this->get_reset_imagify_data() );
	}

	/**
	 * Tell if the current file extension is supported.
	 * If it's in the DB, it's supported.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_extension_supported() {
		return $this->is_valid();
	}

	/**
	 * Tell if the current file mime type is supported.
	 * If it's in the DB, it's supported.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_mime_type_supported() {
		return $this->is_valid();
	}

	/**
	 * Tell if the current attachment has the required WP metadata.
	 * Well, these are not attachments, so...
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_required_metadata() {
		return $this->is_valid();
	}

	/**
	 * Get the original file size.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_original_size( $human_format = true, $decimals = 2 ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$row = $this->get_row();

		if ( ! empty( $row['original_size'] ) ) {
			$size = $row['original_size'];
		} else {
			// Check for the backup file first.
			$file_path = $this->get_backup_path();

			if ( ! $file_path ) {
				$file_path = $this->get_original_path();
				$file_path = $file_path && $this->filesystem->exists( $file_path ) ? $file_path : false;
			}

			$size = $file_path ? $this->filesystem->size( $file_path ) : 0;
		}

		if ( $human_format ) {
			return imagify_size_format( (int) $size, $decimals );
		}

		return (int) $size;
	}

	/**
	 * Get the optimized file size.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_optimized_size( $human_format = true, $decimals = 2 ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$row  = $this->get_row();

		if ( ! empty( $row['optimized_size'] ) ) {
			$size = $row['optimized_size'];
		} else {
			$file_path = $this->get_original_path();
			$file_path = $file_path && $this->filesystem->exists( $file_path ) ? $file_path : false;
			$size      = $file_path ? $this->filesystem->size( $file_path ) : 0;
		}

		if ( $human_format ) {
			return imagify_size_format( (int) $size, $decimals );
		}

		return (int) $size;
	}

	/**
	 * Get the optimized attachment size.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_saving_percent() {
		if ( ! $this->is_valid() ) {
			return round( (float) 0, 2 );
		}

		$row = $this->get_row();

		if ( ! empty( $row['percent'] ) ) {
			return $row['percent'] / 100;
		}

		$original_size = $this->get_original_size( false );

		if ( ! $original_size ) {
			return round( (float) 0, 2 );
		}

		$optimized_size = $this->get_optimized_size( false );

		if ( ! $optimized_size ) {
			return round( (float) 0, 2 );
		}

		return round( ( $original_size - $optimized_size ) / $original_size * 100, 2 );
	}

	/**
	 * Get the overall optimized size (all thumbnails).
	 * And since we don't have thumbnails...
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_overall_saving_percent() {
		return $this->get_saving_percent();
	}

	/**
	 * Get the statistics of the file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size  The thumbnail slug. Not used here.
	 * @param  string $key   The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = null, $key = null ) {
		if ( ! isset( $key ) ) {
			$key = $size;
		}

		if ( ! $this->is_valid() ) {
			return isset( $key ) ? '' : array();
		}

		$data = imagify_merge_intersect( $this->get_row(), array(
			'original_size'  => 0,
			'optimized_size' => false,
			'percent'        => 0,
			'status'         => false,
			'error'          => false,
		) );

		$data['success'] = 'success' === $data['status'];

		if ( $data['status'] ) {
			unset( $data['status'], $data['error'] );

			if ( empty( $data['percent'] ) && $data['original_size'] && $data['optimized_size'] ) {
				$data['percent'] = round( ( $data['original_size'] - $data['optimized_size'] ) / $data['original_size'] * 100, 2 );
			} elseif ( ! empty( $data['percent'] ) ) {
				$data['percent'] = $data['percent'] / 100;
			}
		} else {
			unset( $data['status'], $data['original_size'], $data['optimized_size'], $data['percent'] );
		}

		if ( isset( $key ) ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : '';
		}

		return $data;
	}

	/**
	 * Get the global statistics data or a specific one.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $key The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = null ) {
		if ( ! $this->is_valid() ) {
			return isset( $key ) ? '' : array();
		}

		$stats   = $this->get_size_data( $key );
		$default = array(
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		);

		return imagify_merge_intersect( $stats, $default );
	}

	/**
	 * Since these files are not resized, this method is not needed.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function update_metadata_size() {
		return false;
	}

	/**
	 * Get the unoptimized sizes for a specific attachment.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_unoptimized_sizes() {
		return array();
	}

	/**
	 * Get default values used to reset Imagify data.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function get_reset_imagify_data() {
		static $column_defaults;

		if ( ! isset( $column_defaults ) ) {
			$column_defaults = $this->get_row_db_instance()->get_column_defaults();

			// All DB columns that have `null` as default value, are Imagify data.
			foreach ( $column_defaults as $column_name => $value ) {
				if ( 'hash' === $column_name || 'modified' === $column_name ) {
					continue;
				}

				if ( isset( $value ) ) {
					unset( $column_defaults[ $column_name ] );
				}
			}
		}

		$imagify_columns = $column_defaults;

		// Also set the new file hash.
		$file_path = $this->get_original_path();

		if ( $file_path && $this->filesystem->exists( $file_path ) ) {
			$imagify_columns['hash'] = md5_file( $file_path );
		}

		return $imagify_columns;
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $data     The statistics data.
	 * @param  object $response The API response.
	 * @param  string $size     The attachment size key. Not used here.
	 * @return bool|array       False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $size = null ) {
		$data = is_array( $data ) ? $data : array();
		$data = imagify_merge_intersect( $data, $this->get_reset_imagify_data() );

		if ( is_wp_error( $response ) ) {
			// Error or already optimized.
			$data['error'] = $response->get_error_message();

			if ( false !== strpos( $data['error'], 'This image is already compressed' ) ) {
				$data['status'] = 'already_optimized';
			} else {
				$data['status'] = 'error';
			}

			return $data;
		}

		// Success.
		$old_data        = $this->get_data();
		$original_size   = $old_data['original_size'];
		$data['percent'] = 0;
		$data['status']  = 'success';

		if ( ! empty( $response->original_size ) && ! $original_size ) {
			$data['original_size'] = (int) $response->original_size;
			$original_size         = $data['original_size'];
		}

		if ( ! empty( $response->new_size ) ) {
			$data['optimized_size'] = (int) $response->new_size;
		} else {
			$file_path = $this->get_original_path();
			$file_path = $file_path && $this->filesystem->exists( $file_path ) ? $file_path : false;

			$data['optimized_size'] = $file_path ? $this->filesystem->size( $file_path ) : 0;
		}

		if ( $original_size && $data['optimized_size'] ) {
			$data['percent'] = round( ( $original_size - $data['optimized_size'] ) / $original_size * 10000 );
		}

		return $data;
	}

	/**
	 * Optimize the file with Imagify.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int   $optimization_level The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata           The attachment meta data. Not used here.
	 * @return bool|object               True on success (status success or already_optimized). A WP_Error object on failure.
	 */
	public function optimize( $optimization_level = null, $metadata = null ) {
		// Check if the file extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		$optimization_level = is_numeric( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		// Check if the image is already optimized.
		if ( $this->is_optimized() && ( $this->get_optimization_level() === $optimization_level ) ) {
			return new WP_Error( 'same_optimization_level', __( 'This file is already optimized with this level.', 'imagify' ) );
		}

		/**
		 * Fires before optimizing a file.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int $id The file ID.
		*/
		do_action( 'before_imagify_optimize_file', $this->id );

		$this->set_running_status();

		// Optimize the image.
		$response = do_imagify( $this->get_original_path(), array(
			'optimization_level' => $optimization_level,
			'context'            => 'File',
			'original_size'      => $this->get_original_size( false ),
			'backup_path'        => $this->get_raw_backup_path(),
		) );

		// Fill the data.
		$data = $this->fill_data( array(
			'optimization_level' => $optimization_level,
		), $response );

		// Save the data.
		$this->update_row( $data );

		if ( is_wp_error( $response ) ) {
			$this->delete_running_status();

			if ( 'error' === $data['status'] ) {
				return $response;
			}

			// Already optimized.
			return true;
		}

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int   $id              The attachment ID.
		 * @param array $optimized_data  The optimization data.
		 */
		do_action( 'after_imagify_optimize_file', $this->id, $this->get_data() );

		$this->delete_running_status();

		return true;
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|object True on success (status success or already_optimized). A WP_Error object on failure.
	 */
	public function restore() {
		// Check if the file extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		$backup_path = $this->get_backup_path();

		// Stop the process if there is no backup file to restore.
		if ( ! $backup_path ) {
			return new WP_Error( 'source_doesnt_exist', __( 'The backup file does not exist.', 'imagify' ) );
		}

		$file_path  = $this->get_original_path();

		if ( ! $file_path ) {
			return new WP_Error( 'empty_path', __( 'The file path is empty.', 'imagify' ) );
		}

		/**
		 * Fires before restoring a file.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int $id The file ID.
		*/
		do_action( 'before_imagify_restore_file', $this->id );

		// Create the original image from the backup.
		$this->filesystem->copy( $backup_path, $file_path, true );
		$this->filesystem->chmod_file( $file_path );

		// Remove old optimization data.
		$this->delete_imagify_data();

		/**
		 * Fires after restoring a file.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int $id The file ID.
		*/
		do_action( 'after_imagify_restore_file', $this->id );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DB ROW ================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Invalidate the row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array The row
	 */
	public function invalidate_row() {
		// Since the ID doesn't exist in any other table (not a Post ID, not a NGG gallery ID), it must be reset.
		$this->id  = 0;
		$this->row = array();
		return $this->row;
	}
}
