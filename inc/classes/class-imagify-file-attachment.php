<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify Attachment class for custom folders.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_File_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Name of the DB class.
	 *
	 * @var    string
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	const DB_CLASS_NAME = 'Imagify_Files_DB';

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
		if ( is_numeric( $id ) ) {
			$this->id = (int) $id;
			$this->get_row();
		} elseif ( is_array( $id ) || is_object( $id ) ) {
			$this->row = (array) $id;
			$this->id  = $this->row['file_id'];
		} else {
			$this->id = 0;
			$this->reset_row_cache();
		}
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

		return imagify_get_file_backup_path( $this->get_original_path() );
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

		return site_url( '/' ) . imagify_make_file_path_relative( $this->get_raw_backup_path() );
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

		$classname = self::DB_CLASS_NAME;
		$data      = array_merge( $classname::get_instance()->get_column_defaults(), $this->get_row() );

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

		// All DB columns that have `null` as default value, are Imagify data.
		$classname       = self::DB_CLASS_NAME;
		$instance        = $classname::get_instance();
		$column_defaults = $instance->get_column_defaults();
		$imagify_columns = array();

		foreach ( $column_defaults as $column_name => $value ) {
			if ( ! isset( $value ) ) {
				$imagify_columns[ $column_name ] = null;
			}
		}

		$instance->update( $this->id, $imagify_columns );
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
			$filepath = $this->get_backup_path();

			if ( ! $filepath ) {
				$filepath = $this->get_original_path();
				$filepath = $filepath && imagify_get_filesystem()->exists( $filepath ) ? $filepath : false;
			}

			$size = $filepath ? imagify_get_filesystem()->size( $filepath ) : 0;
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
			$filepath = $this->get_original_path();
			$filepath = $filepath && imagify_get_filesystem()->exists( $filepath ) ? $filepath : false;
			$size     = $filepath ? imagify_get_filesystem()->size( $filepath ) : 0;
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

		$data = self::merge_intersect( $this->get_row(), array(
			'original_size'      => 0,
			'optimized_size'     => false,
			'percent'            => 0,
			'status'             => false,
			'error'              => false,
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

		return self::merge_intersect( $stats, $default );
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
	 * Fills statistics data with values from $data array.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $data     The statistics data.
	 * @param  object $response The API response.
	 * @param  int    $url      The attachment URL. Not used here.
	 * @param  string $size     The attachment size key. Not used here.
	 * @return bool|array False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $url = null, $size = null ) {
		$data = is_array( $data ) ? $data : array();

		$data = self::merge_intersect( $data, array(
			'width'          => 0,
			'height'         => 0,
			'original_size'  => 0,
			'original_hash'  => '',
			'optimized_size' => null,
			'optimized_hash' => null,
			'status'         => null,
			'error'          => null,
		) );

		if ( is_wp_error( $response ) ) {
			$data['error'] = $response->get_error_message();

			if ( false !== strpos( $data['error'], 'This image is already compressed' ) ) {
				$data['status'] = 'already_optimized';
			} else {
				$data['status'] = 'error';
			}

			return $data;
		}

		$response = (object) array_merge( array(
			'original_size' => 0,
			'new_size'      => 0,
		), (array) $response );

		$data = array_merge( $data, array(
			'original_size'  => (int) $response->original_size,
			'optimized_size' => (int) $response->new_size,
			'optimized_hash' => md5_file( $this->get_original_path() ),
			'status'         => 'success',
			'error'          => null,
		) );

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
	 * @return array $optimized_data     The optimization data.
	 */
	public function optimize( $optimization_level = null, $metadata = null ) {
		// Check if the file extension is allowed.
		if ( ! $this->is_mime_type_supported() ) {
			return;
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		// To avoid issue with "original_size" at 0.
		if ( 0 === $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && ( $this->get_optimization_level() === $optimization_level ) ) {
			return;
		}

		// Get file path, md5, width, and height for original image.
		$original_path   = $this->get_original_path();
		$original_md5    = md5_file( $original_path );
		$attachment_size = @getimagesize( $attachment_path );
		$original_width  = isset( $attachment_size[0] ) ? (int) $attachment_size[0] : 0;
		$original_height = isset( $attachment_size[1] ) ? (int) $attachment_size[1] : 0;

		/**
		 * Fires before optimizing a file.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int $id The file ID.
		*/
		do_action( 'before_imagify_optimize_file', $this->id );

		set_transient( 'imagify-file-async-in-progress-' . $this->id, true, 10 * MINUTE_IN_SECONDS );

		// Optimize the original size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'file',
			'original_size'      => $this->get_original_size( false ),
			'backup_path'        => $this->get_raw_backup_path(),
		) );

		$data = $this->fill_data( array(
			'width'              => $original_width,
			'height'             => $original_height,
			'original_hash'      => $original_md5,
			'optimization_level' => $optimization_level,
		), $response );

		$classname = self::DB_CLASS_NAME;
		$classname::get_instance()->update( $this->id, $data );

		if ( 'success' !== $data['status'] ) {
			delete_transient( 'imagify-file-async-in-progress-' . $this->id );
			return;
		}

		$optimized_data = $this->get_data();

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param int   $id              The attachment ID.
		 * @param array $optimized_data  The optimization data.
		 */
		do_action( 'after_imagify_optimize_file', $this->id, $optimized_data );

		delete_transient( 'imagify-file-async-in-progress-' . $this->id );

		return $optimized_data;
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return void
	 */
	public function restore() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_mime_type_supported() ) {
			return;
		}

		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return;
		}

		$backup_path = $this->get_backup_path();
		$file_path   = $this->get_original_path();
		$filesystem  = imagify_get_filesystem();

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
		$filesystem->copy( $backup_path, $file_path, true );
		imagify_chmod_file( $file_path );

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
}
