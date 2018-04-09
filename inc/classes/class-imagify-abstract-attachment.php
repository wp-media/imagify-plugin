<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify Attachment base class.
 *
 * @since 1.0
 */
abstract class Imagify_Abstract_Attachment extends Imagify_Abstract_Attachment_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.3.2';

	/**
	 * The attachment ID.
	 *
	 * @var    int
	 * @since  1.0
	 * @access public
	 */
	public $id = 0;

	/**
	 * Context.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $context;

	/**
	 * The attachment SQL DB class.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $db_class_name = '';

	/**
	 * The attachment SQL data row.
	 *
	 * @var    array
	 * @since  1.7
	 * @access protected
	 */
	protected $row = null;

	/**
	 * Tell if the file extension can be optimized by Imagify.
	 * This is used to cache the result of $this->is_extension_supported().
	 *
	 * @var    bool
	 * @since  1.7
	 * @access protected
	 * @see    $this->is_extension_supported()
	 */
	protected $is_extension_supported;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The editor instances used to resize files.
	 *
	 * @var    array An array of image editor objects (WP_Image_Editor_Imagick, WP_Image_Editor_GD).
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $editors = array();

	/**
	 * The name of the transient that tells if optimization is processing.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $optimization_state_transient;

	/**
	 * Tell if the optimization status is network-wide.
	 *
	 * @var    bool
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $optimization_state_network_wide = false;

	/**
	 * The constructor.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int|object $id The attachment ID or the attachment itself.
	 *                       If an integer, make sure the attachment exists.
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

		$this->id                           = (int) $this->id;
		$this->filesystem                   = Imagify_Filesystem::get_instance();
		$this->optimization_state_transient = 'wp' !== $this->get_context() ? strtolower( $this->get_context() ) . '-' : '';
		$this->optimization_state_transient = 'imagify-' . $this->optimization_state_transient . 'async-in-progress-' . $this->id;
	}

	/**
	 * Get the attachment context.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_context() {
		if ( $this->context ) {
			return $this->context;
		}

		$this->context = str_replace( array( 'Imagify_', 'Attachment' ), '', get_class( $this ) );
		$this->context = trim( $this->context, '_' );
		$this->context = $this->context ? $this->context : 'wp';

		return $this->context;
	}

	/**
	 * Tell if the current attachment is valid.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->id > 0;
	}

	/**
	 * Get the attachment ID.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

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
	 * Get the original attachment URL.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	abstract public function get_original_url();

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
		if ( ! $this->is_valid() ) {
			return false;
		}

		$backup_path = $this->get_raw_backup_path();

		if ( $backup_path && $this->filesystem->exists( $backup_path ) ) {
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
		if ( ! $this->is_valid() ) {
			return false;
		}

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
	 * Get the attachment optimization level.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return int
	 */
	abstract public function get_optimization_level();

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

		return '';
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
		$sizes = ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ? $data['sizes'] : array();
		$count = 0;

		unset( $sizes['full'] );

		if ( ! $sizes ) {
			return 0;
		}

		foreach ( $sizes as $size ) {
			if ( ! empty( $size['success'] ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Delete the 3 metas used by Imagify.
	 *
	 * @since  1.6.6
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_imagify_data() {
		if ( ! $this->is_valid() ) {
			return;
		}

		delete_post_meta( $this->id, '_imagify_data' );
		delete_post_meta( $this->id, '_imagify_status' );
		delete_post_meta( $this->id, '_imagify_optimization_level' );
	}

	/**
	 * Get width and height of the original image.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_dimensions() {
		return array(
			'width'  => 0,
			'height' => 0,
		);
	}

	/**
	 * Get the attachment extension.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string|null
	 */
	public function get_extension() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		return $this->filesystem->path_info( $this->get_original_path(), 'extension' );
	}

	/**
	 * Tell if the current file extension is supported.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_extension_supported() {
		if ( isset( $this->is_extension_supported ) ) {
			return $this->is_extension_supported;
		}

		if ( ! $this->is_valid() ) {
			$this->is_extension_supported = false;
			return $this->is_extension_supported;
		}

		$file_type = wp_check_filetype( $this->get_original_path(), imagify_get_mime_types() );

		$this->is_extension_supported = (bool) $file_type['ext'];

		return $this->is_extension_supported;
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
		if ( ! $this->is_valid() ) {
			return false;
		}

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
		if ( ! $this->is_valid() ) {
			return false;
		}

		return imagify_attachment_has_required_metadata( $this->id );
	}

	/**
	 * Get the attachment optimization level label.
	 *
	 * @since  1.2
	 * @since  1.7 Added $format parameter.
	 * @access public
	 *
	 * @param  string $format Format to display the label. Use %ICON% for the icon and %s for the label.
	 * @return string
	 */
	public function get_optimization_level_label( $format = '%s' ) {
		return imagify_get_optimization_level_label( $this->get_optimization_level(), $format );
	}

	/**
	 * Get the original attachment size.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_original_size( $human_format = true, $decimals = 2 ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$size = $this->get_size_data( 'full', 'original_size' );

		if ( ! $size ) {
			// Check for the backup file first.
			$filepath = $this->get_backup_path();

			if ( ! $filepath ) {
				$filepath = $this->get_original_path();
				$filepath = $filepath && $this->filesystem->exists( $filepath ) ? $filepath : false;
			}

			$size = $filepath ? $this->filesystem->size( $filepath ) : 0;
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
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_optimized_size( $human_format = true, $decimals = 2 ) {
		if ( ! $this->is_valid() ) {
			return $human_format ? imagify_size_format( 0, $decimals ) : 0;
		}

		$size = $this->get_size_data( 'full', 'optimized_size' );

		if ( ! $size ) {
			$filepath = $this->get_original_path();
			$filepath = $filepath && $this->filesystem->exists( $filepath ) ? $filepath : false;
			$size     = $filepath ? $this->filesystem->size( $filepath ) : 0;
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

		$percent = $this->get_size_data( 'full', 'percent' );
		$percent = $percent ? $percent : (float) 0;

		return round( $percent, 2 );
	}

	/**
	 * Get the overall optimized size (all thumbnails).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_overall_saving_percent() {
		if ( ! $this->is_valid() ) {
			return round( (float) 0, 2 );
		}

		$percent = $this->get_data();
		$percent = ! empty( $percent['stats']['percent'] ) ? $percent['stats']['percent'] : (float) 0;

		return round( $percent, 2 );
	}

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

		if ( $filepath && $this->filesystem->exists( $filepath ) ) {
			$size = $this->filesystem->size( $filepath );
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
		return 'error' === $this->get_status();
	}

	/**
	 * Get an image editor instance (WP_Image_Editor_Imagick, WP_Image_Editor_GD).
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $path A file path.
	 * @return object       An image editor instance (WP_Image_Editor_Imagick, WP_Image_Editor_GD). A WP_Error object on error.
	 */
	protected function get_editor( $path ) {
		if ( isset( $this->editors[ $path ] ) ) {
			return $this->editors[ $path ];
		}

		$this->editors[ $path ] = wp_get_image_editor( $path, array(
			'methods' => self::get_editor_methods(),
		) );

		return $this->editors[ $path ];
	}

	/**
	 * Get the image editor methods we will use.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public static function get_editor_methods() {
		static $methods;

		if ( isset( $methods ) ) {
			return $methods;
		}

		$methods = array(
			'resize',
			'multi_resize',
			'generate_filename',
			'save',
		);

		if ( Imagify_Filesystem::get_instance()->can_get_exif() ) {
			$methods[] = 'rotate';
		}

		return $methods;
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

		if ( $backup_path ) {
			$this->filesystem->delete( $backup_path );
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
		if ( ! $this->is_valid() || ! $this->is_optimized() || ! $this->has_backup() ) {
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

		$orig_f = $this->filesystem->path_info( $orig_f );
		$orig_f = $orig_f['file_base'] . '-{%suffix%}.' . $orig_f['extension'];

		// Test if the missing sizes are needed.
		$disallowed_sizes      = get_imagify_option( 'disallowed-sizes' );
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
	 * @since  1.7   Removed the image URL parameter.
	 * @access public
	 *
	 * @param  array  $data     The statistics data.
	 * @param  object $response The API response.
	 * @param  string $size     The attachment size key.
	 * @return bool|array False if the original size has an error or an array contains the data for other result.
	 */
	abstract public function fill_data( $data, $response, $size = 'full' );

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
	 * Re-optimize the given thumbnail sizes to the same level.
	 * Before doing this, the given sizes must be restored.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $sizes The sizes to optimize.
	 * @return array|void             A WP_Error object on failure.
	 */
	abstract public function reoptimize_thumbnails( $sizes );

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
	 * @since  1.7.1 Keys for width and height in $attachment_sizes are now 'width' and 'height' instead of 0 and 1.
	 * @access public
	 * @author Remy Perona
	 *
	 * @param  string $attachment_path  Path to the image.
	 * @param  array  $attachment_sizes Array of original image dimensions.
	 * @param  int    $max_width        Maximum width defined in the settings.
	 * @return string Path the the resized image or the original image if the resize failed.
	 */
	public function resize( $attachment_path, $attachment_sizes, $max_width ) {
		if ( ! $this->is_valid() ) {
			return '';
		}

		// Prevent removal of the exif/meta data when resizing (only works with Imagick).
		add_filter( 'image_strip_meta', '__return_false' );

		$new_sizes = wp_constrain_dimensions( $attachment_sizes['width'], $attachment_sizes['height'], $max_width );

		$editor = $this->get_editor( $attachment_path );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$image_type = strtolower( (string) $this->filesystem->path_info( $attachment_path, 'extension' ) );

		// Try to correct for auto-rotation if the info is available.
		if ( $this->filesystem->can_get_exif() && ( 'jpg' === $image_type || 'jpe' === $image_type || 'jpeg' === $image_type ) ) {
			$exif        = $this->filesystem->get_image_exif( $attachment_path );
			$orientation = isset( $exif['Orientation'] ) ? (int) $exif['Orientation'] : 1;

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


	/** ----------------------------------------------------------------------------------------- */
	/** WORKING STATUS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the file is currently being optimized (or restored, etc).
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_running() {
		$callback = $this->optimization_state_network_wide ? 'get_site_transient' : 'get_transient';

		return false !== call_user_func( $callback, $this->optimization_state_transient );
	}

	/**
	 * Set the running status to "running" for 10 minutes.
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 * @access public
	 */
	public function set_running_status() {
		$callback = $this->optimization_state_network_wide ? 'set_site_transient' : 'set_transient';

		call_user_func( $callback, $this->optimization_state_transient, true, 10 * MINUTE_IN_SECONDS );
	}

	/**
	 * Unset the running status.
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_running_status() {
		$callback = $this->optimization_state_network_wide ? 'delete_site_transient' : 'delete_transient';

		call_user_func( $callback, $this->optimization_state_transient );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DB ROW ================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the data row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_row() {
		if ( isset( $this->row ) ) {
			return $this->row;
		}

		if ( ! $this->db_class_name || ! $this->is_valid() ) {
			return $this->invalidate_row();
		}

		$this->row = $this->get_row_db_instance()->get( $this->id );

		if ( ! $this->row ) {
			return $this->invalidate_row();
		}

		return $this->row;
	}

	/**
	 * Update the data row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  array $data The data to update.
	 */
	public function update_row( $data ) {
		if ( ! $this->db_class_name || ! $this->is_valid() ) {
			return;
		}

		$this->get_row_db_instance()->update( $this->id, $data );

		$this->reset_row_cache();
	}

	/**
	 * Delete the data row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_row() {
		if ( ! $this->db_class_name || ! $this->is_valid() ) {
			return;
		}

		$this->get_row_db_instance()->delete( $this->id );

		$this->invalidate_row();
	}

	/**
	 * Shorthand to get the DB table instance.
	 *
	 * @since  1.7.1
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return object The DB table instance.
	 */
	public function get_row_db_instance() {
		return call_user_func( array( $this->db_class_name, 'get_instance' ) );
	}

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
		$this->row = array();
		return $this->row;
	}

	/**
	 * Reset the row cache.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return null The row.
	 */
	public function reset_row_cache() {
		$this->row = null;
		return $this->row;
	}
}
