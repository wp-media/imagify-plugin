<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify Attachment base class.
 *
 * @since 1.0
 */
abstract class Imagify_Abstract_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.2';

	/**
	 * The attachment ID.
	 *
	 * @var    int
	 * @since  1.0
	 * @access public
	 */
	public $id = 0;

	/**
	 * The constructor.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  int $id The attachment ID.
	 * @return void
	 */
	public function __construct( $id = 0 ) {
		global $post;

		if ( $id ) {
			if ( is_a( $id, 'WP_Post' ) ) {
				$this->id = $id->ID;
			} elseif ( is_numeric( $id ) ) {
				$this->id = $id;
			}
		} elseif ( $post && is_a( $post, 'WP_Post' ) ) {
			$this->id = $post->ID;
		}

		$this->id = absint( $this->id );
	}

	/**
	 * Get the attachment backup file path, even if the file doesn't exist.
	 *
	 * @since  1.6.13
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path. False on failure.
	 */
	abstract public function get_raw_backup_path();

	/**
	 * Get the attachment backup file path.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string|false The file path. False if it doesn't exist.
	 */
	public function get_backup_path() {
		$backup_path = $this->get_raw_backup_path();

		if ( $backup_path && file_exists( $backup_path ) ) {
			return $backup_path;
		}

		return false;
	}

	/**
	 * Get the attachment backup URL.
	 *
	 * @since  1.4
	 * @access public
	 *
	 * @return string|false
	 */
	public function get_backup_url() {
		return get_imagify_attachment_url( $this->get_raw_backup_path() );
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	abstract public function get_data();

	/**
	 * Get the attachment extension.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_extension() {
		$fullsize_path = $this->get_original_path();
		return pathinfo( $fullsize_path, PATHINFO_EXTENSION );
	}

	/**
	 * Tell if the current file mime type is supported.
	 *
	 * @since  1.6.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_mime_type_supported() {
		return imagify_is_attachment_mime_type_supported( $this->id );
	}

	/**
	 * Tell if the current attachment has the required WP metadata.
	 *
	 * @since  1.6.12
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_required_metadata() {
		return imagify_attachment_has_required_metadata( $this->id );
	}

	/**
	 * Get the attachment error if there is one.
	 *
	 * @since  1.1.5
	 * @access public
	 *
	 * @return string The message error
	 */
	public function get_optimized_error() {
		$error = $this->get_size_data( 'full', 'error' );

		if ( is_string( $error ) ) {
			return trim( $error );
		}

		return false;
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return int
	 */
	abstract public function get_optimization_level();

	/**
	 * Delete the 3 metas used by Imagify.
	 *
	 * @since  1.6.6
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_imagify_data() {
		delete_post_meta( $this->id, '_imagify_data' );
		delete_post_meta( $this->id, '_imagify_status' );
		delete_post_meta( $this->id, '_imagify_optimization_level' );
	}

	/**
	 * Get the attachment optimization level label.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @return string
	 */
	public function get_optimization_level_label() {
		$label = '';
		$level = $this->get_optimization_level();

		switch ( $level ) {
			case 2:
				$label = __( 'Ultra', 'imagify' );
				break;
			case 1:
				$label = __( 'Aggressive', 'imagify' );
				break;
			case 0:
				$label = __( 'Normal', 'imagify' );
		}

		return $label;
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return int
	 */
	public function get_optimized_sizes_count() {
		$data  = $this->get_data();
		$sizes = (array) $data['sizes'];
		$count = 0;

		unset( $sizes['full'] );

		foreach ( $sizes as $size ) {
			if ( $size['success'] ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	abstract public function get_status();

	/**
	 * Get the original attachment path.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	abstract public function get_original_path();

	/**
	 * Get the original attachment size.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string
	 */
	public function get_original_size( $human_format = true, $decimals = 2 ) {
		$filesystem    = imagify_get_filesystem();
		$original_size = $this->get_size_data( 'full', 'original_size' );
		$original_size = empty( $original_size ) ? $filesystem->size( $this->get_original_path() ) : (int) $original_size;

		if ( true === $human_format ) {
			$original_size = @size_format( $original_size, $decimals );
		}

		return $original_size;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	abstract public function get_original_url();

	/**
	 * Get the statistics of a specific size.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $size  The thumbnail slug.
	 * @param  string $key   The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = 'full', $key = '' ) {
		$data  = $this->get_data();
		$stats = array();

		if ( isset( $data['sizes'][ $size ] ) ) {
			$stats = $data['sizes'][ $size ];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}

	/**
	 * Get the global statistics data or a specific one.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $key The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = '' ) {
		$data  = $this->get_data();
		$stats = '';

		if ( isset( $data['stats'] ) ) {
			$stats = $data['stats'];
		}

		if ( isset( $stats[ $key ] ) ) {
			$stats = $stats[ $key ];
		}

		return $stats;
	}

	/**
	 * Check if the attachment is already optimized (before Imagify).
	 *
	 * @since  1.1.6
	 * @access public
	 *
	 * @return bool True if the attachment is optimized.
	 */
	public function is_already_optimized() {
		return 'already_optimized' === $this->get_status();
	}

	/**
	 * Check if the attachment is optimized.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool True if the attachment is optimized.
	 */
	public function is_optimized() {
		return 'success' === $this->get_status();
	}

	/**
	 * Check if the attachment exceeding the limit size (> 5mo).
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool True if the attachment is skipped.
	 */
	public function is_exceeded() {
		$filepath = $this->get_original_path();
		$size     = 0;

		if ( file_exists( $filepath ) ) {
			$size = filesize( $filepath );
		}

		return $size > IMAGIFY_MAX_BYTES;
	}

	/**
	 * Check if the attachment has a backup of the original size.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool True if the attachment has a backup.
	 */
	public function has_backup() {
		return (bool) $this->get_backup_path();
	}

	/**
	 * Check if the attachment has an error.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return bool True if the attachment has an error.
	 */
	public function has_error() {
		$has_error = $this->get_size_data( 'full', 'error' );
		return is_string( $has_error );
	}

	/**
	 * Update the metadata size of the attachment
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @return void
	 */
	abstract public function update_metadata_size();

	/**
	 * Delete the backup file.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function delete_backup() {
		$backup_path = $this->get_backup_path();

		if ( ! empty( $backup_path ) ) {
			imagify_get_filesystem()->delete( $backup_path );
		}
	}

	/**
	 * Get the registered sizes.
	 *
	 * @since  1.6.10
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array Data for the registered thumbnail sizes.
	 */
	static public function get_registered_sizes() {
		static $registered_sizes;

		if ( ! isset( $registered_sizes ) ) {
			$registered_sizes = get_imagify_thumbnail_sizes();
		}

		return $registered_sizes;
	}

	/**
	 * Get the unoptimized sizes for a specific attachment.
	 *
	 * @since  1.6.10
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array Data for the unoptimized thumbnail sizes.
	 *               Each size data has a "file" key containing the name the thumbnail "should" have.
	 */
	public function get_unoptimized_sizes() {
		// The attachment must have been optimized once and have a backup.
		if ( ! $this->is_optimized() || ! $this->has_backup() ) {
			return array();
		}

		$registered_sizes = self::get_registered_sizes();
		$attachment_sizes = $this->get_data();
		$attachment_sizes = ! empty( $attachment_sizes['sizes'] ) ? $attachment_sizes['sizes'] : array();
		$missing_sizes    = array_diff_key( $registered_sizes, $attachment_sizes );

		if ( ! $missing_sizes ) {
			// We have everything we need.
			return array();
		}

		// Get full size dimensions.
		$orig   = wp_get_attachment_metadata( $this->id );
		$orig_f = ! empty( $orig['file'] )   ? $orig['file']         : '';
		$orig_w = ! empty( $orig['width'] )  ? (int) $orig['width']  : 0;
		$orig_h = ! empty( $orig['height'] ) ? (int) $orig['height'] : 0;

		if ( ! $orig_f || ! $orig_w || ! $orig_h ) {
			return array();
		}

		$orig_f = pathinfo( $orig_f );
		$orig_f = $orig_f['filename'] . '-{%suffix%}.' . $orig_f['extension'];

		// Test if the missing sizes are needed.
		$disallowed_sizes      = get_imagify_option( 'disallowed-sizes', array() );
		$is_active_for_network = imagify_is_active_for_network();

		foreach ( $missing_sizes as $size_name => $size_data ) {
			$duplicate = ( $orig_w === $size_data['width'] ) && ( $orig_h === $size_data['height'] );

			if ( $duplicate ) {
				// Same dimensions as the full size.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			if ( ! $is_active_for_network && isset( $disallowed_sizes[ $size_name ] ) ) {
				// This size must be optimized.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			$resize_result = image_resize_dimensions( $orig_w, $orig_h, $size_data['width'], $size_data['height'], $size_data['crop'] );

			if ( ! $resize_result ) {
				// This size is not needed.
				unset( $missing_sizes[ $size_name ] );
				continue;
			}

			// Provide what should be the file name.
			list( , , , , $dst_w, $dst_h ) = $resize_result;
			$missing_sizes[ $size_name ]['file'] = str_replace( '{%suffix%}', "{$dst_w}x{$dst_h}", $orig_f );
		}

		return $missing_sizes;
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since  1.0
	 * @since  1.6.5 Not static anymore.
	 * @since  1.6.6 Removed the attachment ID parameter.
	 * @access public
	 *
	 * @param  array  $data     The statistics data.
	 * @param  object $response The API response.
	 * @param  int    $url      The attachment URL.
	 * @param  string $size     The attachment size key.
	 * @return bool|array False if the original size has an error or an array contains the data for other result.
	 */
	abstract public function fill_data( $data, $response, $url, $size = 'full' );

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  int   $optimization_level The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata           The attachment meta data.
	 * @return array $optimized_data     The optimization data.
	 */
	abstract public function optimize( $optimization_level = null, $metadata = array() );

	/**
	 * Optimize missing sizes with Imagify.
	 *
	 * @since  1.6.10
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $optimization_level The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @return array|object            An array of thumbnail data, size by size. A WP_Error object on failure.
	 */
	abstract public function optimize_missing_thumbnails( $optimization_level = null );

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	abstract public function restore();

	/**
	 * Resize an image if bigger than the maximum width defined in the settings.
	 *
	 * @since  1.5.7
	 * @access public
	 * @author Remy Perona
	 *
	 * @param  string $attachment_path  Path to the image.
	 * @param  array  $attachment_sizes Array of original image dimensions.
	 * @param  int    $max_width        Maximum width defined in the settings.
	 * @return string Path the the resized image or the original image if the resize failed.
	 */
	public function resize( $attachment_path, $attachment_sizes, $max_width ) {
		// Prevent removal of the exif/meta data when resizing (only works with Imagick).
		add_filter( 'image_strip_meta', '__return_false' );

		$new_sizes = wp_constrain_dimensions( $attachment_sizes[0], $attachment_sizes[1], $max_width );
		$editor    = wp_get_image_editor( $attachment_path );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$image_type = pathinfo( $attachment_path, PATHINFO_EXTENSION );

		// Try to correct for auto-rotation if the info is available.
		if ( function_exists( 'exif_read_data' ) && ( 'jpg' === $image_type || 'jpe' === $image_type || 'jpeg' === $image_type ) ) {
			$exif        = @exif_read_data( $attachment_path );
			$orientation = is_array( $exif ) && array_key_exists( 'Orientation', $exif ) ? $exif['Orientation'] : 0;

			switch ( $orientation ) {
				case 3:
					$editor->rotate( 180 );
					break;
				case 6:
					$editor->rotate( -90 );
					break;
				case 8:
					$editor->rotate( 90 );
			}
		}

		$resized = $editor->resize( $new_sizes[0], $new_sizes[1], false );

		if ( is_wp_error( $resized ) ) {
			return $resized;
		}

		$resized_image_path  = $editor->generate_filename( 'imagifyresized' );
		$resized_image_saved = $editor->save( $resized_image_path );

		if ( is_wp_error( $resized_image_saved ) ) {
			return $resized_image_saved;
		}

		// Remove the filter when we're done to prevent any conflict.
		remove_filter( 'image_strip_meta', '__return_false' );

		return $resized_image_path;
	}
}
