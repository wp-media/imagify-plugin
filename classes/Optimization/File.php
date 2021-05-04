<?php
namespace Imagify\Optimization;

use Imagify_Requirements;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * A generic optimization class focussed on the file itself.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class File {

	/**
	 * Absolute path to the file.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $path;

	/**
	 * Tell if the file is an image.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @see    $this->is_image()
	 * @author Grégory Viguier
	 */
	protected $is_image;

	/**
	 * Store the file mime type + file extension (if the file is supported).
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @see    $this->get_file_type()
	 * @author Grégory Viguier
	 */
	protected $file_type;

	/**
	 * Filesystem object.
	 *
	 * @var    \Imagify_Filesystem
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The editor instance used to resize the file.
	 *
	 * @var    \WP_Image_Editor_Imagick|\WP_Image_Editor_GD|WP_Error.
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $editor;

	/**
	 * Used to cache the plugin’s options.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $options = [];

	/**
	 * The constructor.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Absolute path to the file.
	 */
	public function __construct( $file_path ) {
		$this->path       = $file_path;
		$this->filesystem = \Imagify_Filesystem::get_instance();
	}

	/**
	 * Tell if the file is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid() {
		return (bool) $this->path;
	}

	/**
	 * Tell if the file can be processed.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|WP_Error
	 */
	public function can_be_processed() {
		if ( ! $this->path ) {
			return new \WP_Error( 'empty_path', __( 'File path is empty.', 'imagify' ) );
		}

		if ( ! empty( $this->filesystem->errors->errors ) ) {
			return new \WP_Error( 'filesystem_error', __( 'Filesystem error.', 'imagify' ), $this->filesystem->errors );
		}

		if ( ! $this->filesystem->exists( $this->path ) ) {
			return new \WP_Error(
				'not_exists',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s does not seem to exist.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $this->path ) ) . '</code>'
				)
			);
		}

		if ( ! $this->filesystem->is_file( $this->path ) ) {
			return new \WP_Error(
				'not_a_file',
				sprintf(
					/* translators: %s is a file path. */
					__( 'This does not seem to be a file: %s.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $this->path ) ) . '</code>'
				)
			);
		}

		if ( ! $this->filesystem->is_writable( $this->path ) ) {
			return new \WP_Error(
				'not_writable',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s does not seem to be writable.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $this->path ) ) . '</code>'
				)
			);
		}

		$parent_folder = $this->filesystem->dir_path( $this->path );

		if ( ! $this->filesystem->is_writable( $parent_folder ) ) {
			return new \WP_Error(
				'folder_not_writable',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The folder %s does not seem to be writable.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $parent_folder ) ) . '</code>'
				)
			);
		}

		return true;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** EDITION ================================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Resize (and rotate) an image if it is bigger than the maximum width provided.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 * @author Remy Perona
	 *
	 * @param  array $dimensions {
	 *     Array of image dimensions.
	 *
	 *     @type int $width  The image width.
	 *     @type int $height The image height.
	 * }
	 * @param  int   $max_width Maximum width to resize to.
	 * @return string|WP_Error  Path the the resized image. A WP_Error object on failure.
	 */
	public function resize( $dimensions = [], $max_width = 0 ) {
		$can_be_processed = $this->can_be_processed();

		if ( is_wp_error( $can_be_processed ) ) {
			return $can_be_processed;
		}

		if ( ! $max_width ) {
			return new \WP_Error(
				'no_resizing_threshold',
				__( 'No threshold provided for resizing.', 'imagify' )
			);
		}

		if ( ! $this->is_image() ) {
			return new \WP_Error(
				'not_an_image',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s does not seem to be an image, and cannot be resized.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $this->path ) ) . '</code>'
				)
			);
		}

		$editor = $this->get_editor();

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		// Try to correct the auto-rotation if the info is available.
		if ( $this->filesystem->can_get_exif() && 'image/jpeg' === $this->get_mime_type() ) {
			$exif        = $this->filesystem->get_image_exif( $this->path );
			$orientation = isset( $exif['Orientation'] ) ? (int) $exif['Orientation'] : 1;

			switch ( $orientation ) {
				case 2:
					// Flip horizontally.
					$editor->flip( true, false );
					break;
				case 3:
					// Rotate 180 degrees or flip horizontally and vertically.
					// Flipping seems faster/uses less resources.
					$editor->flip( true, true );
					break;
				case 4:
					// Flip vertically.
					$editor->flip( false, true );
					break;
				case 5:
					// Rotate 90 degrees counter-clockwise and flip vertically.
					$result = $editor->rotate( 90 );

					if ( ! is_wp_error( $result ) ) {
						$editor->flip( false, true );
					}
					break;
				case 6:
					// Rotate 90 degrees clockwise (270 counter-clockwise).
					$editor->rotate( 270 );
					break;
				case 7:
					// Rotate 90 degrees counter-clockwise and flip horizontally.
					$result = $editor->rotate( 90 );

					if ( ! is_wp_error( $result ) ) {
						$editor->flip( true, false );
					}
					break;
				case 8:
					// Rotate 90 degrees counter-clockwise.
					$editor->rotate( 90 );
					break;
			}
		}

		if ( ! $dimensions ) {
			$dimensions = $this->get_dimensions();
		}

		// Prevent removal of the exif data when resizing (only works with Imagick).
		add_filter( 'image_strip_meta', '__return_false', 789 );

		// Resize.
		$new_sizes = wp_constrain_dimensions( $dimensions['width'], $dimensions['height'], $max_width );
		$resized   = $editor->resize( $new_sizes[0], $new_sizes[1], false );

		// Remove the filter when we're done to prevent any conflict.
		remove_filter( 'image_strip_meta', '__return_false', 789 );

		if ( is_wp_error( $resized ) ) {
			return $resized;
		}

		$resized_image_path  = $editor->generate_filename( 'imagifyresized' );
		$resized_image_saved = $editor->save( $resized_image_path );

		if ( is_wp_error( $resized_image_saved ) ) {
			return $resized_image_saved;
		}

		return $resized_image_path;
	}

	/**
	 * Create a thumbnail.
	 * Warning: If the destination file already exists, it will be overwritten.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $destination {
	 *     The thumbnail data.
	 *
	 *     @type string $path            Path to the destination file.
	 *     @type int    $width           The image width.
	 *     @type int    $height          The image height.
	 *     @type bool   $crop            True to crop, false to resize.
	 *     @type bool   $adjust_filename True to adjust the file name like what `$editor->multi_resize()` returns, like WP default behavior (default). False to prevent it, and use the file name from $path instead.
	 * }
	 * @return bool|array|WP_Error {
	 *     A WP_Error object on error. True if the file exists.
	 *     An array of thumbnail data if the file has just been created:
	 *
	 *     @type string $file      File name.
	 *     @type int    $width     The image width.
	 *     @type int    $height    The image height.
	 *     @type string $mime-type The mime type.
	 * }
	 */
	public function create_thumbnail( $destination ) {
		$can_be_processed = $this->can_be_processed();

		if ( is_wp_error( $can_be_processed ) ) {
			return $can_be_processed;
		}

		if ( ! $this->is_image() ) {
			return new \WP_Error(
				'not_an_image',
				sprintf(
					/* translators: %s is a file path. */
					__( 'The file %s does not seem to be an image, and cannot be resized.', 'imagify' ),
					'<code>' . esc_html( $this->filesystem->make_path_relative( $this->path ) ) . '</code>'
				)
			);
		}

		$editor = $this->get_editor();

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		// Create the file.
		$result = $editor->multi_resize( [ $destination ] );

		if ( ! $result ) {
			return new \WP_Error( 'image_resize_error', __( 'The thumbnail could not be created.', 'imagify' ) );
		}

		$result = reset( $result );

		$filename          = $result['file'];
		$source_thumb_path = $this->filesystem->dir_path( $this->path ) . $filename;

		if ( ! isset( $destination['adjust_filename'] ) || $destination['adjust_filename'] ) {
			// The file name can change from what we expected (1px wider, etc), let's use the resulting data to move the file to the right place.
			$destination_thumb_path = $this->filesystem->dir_path( $destination['path'] ) . $filename;
		} else {
			// Respect what is set in $path.
			$destination_thumb_path = $destination['path'];
			$result['file']         = $this->filesystem->file_name( $destination['path'] );
		}

		if ( $source_thumb_path === $destination_thumb_path ) {
			return $result;
		}

		$moved = $this->filesystem->move( $source_thumb_path, $destination_thumb_path, true );

		if ( ! $moved ) {
			return new \WP_Error( 'move_error', __( 'The file could not be moved to its final destination.', 'imagify' ) );
		}

		return $result;
	}

	/**
	 * Backup a file.
	 *
	 * @since  1.9
	 * @since  1.9.8 Added $backup_source argument.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $backup_path   The backup path.
	 * @param  string $backup_source Path to the file to backup. This is useful in WP 5.3+ when we want to optimize the full size: in that case we need to backup the original file.
	 * @return bool|WP_Error         True on success. False if the backup option is disabled. A WP_Error object on failure.
	 */
	public function backup( $backup_path = null, $backup_source = null ) {
		$can_be_processed = $this->can_be_processed();

		if ( is_wp_error( $can_be_processed ) ) {
			return $can_be_processed;
		}

		// Make sure the backups directory has no errors.
		if ( ! $backup_path ) {
			return new \WP_Error( 'wp_upload_error', __( 'Error while retrieving the backups directory path.', 'imagify' ) );
		}

		// Create sub-directories.
		$created = $this->filesystem->make_dir( $this->filesystem->dir_path( $backup_path ) );

		if ( ! $created ) {
			return new \WP_Error( 'backup_dir_not_writable', __( 'The backup directory is not writable.', 'imagify' ) );
		}

		$path = $backup_source && $this->filesystem->exists( $backup_source ) ? $backup_source : $this->path;

		/**
		 * Allow to overwrite the backup file if it already exists.
		 *
		 * @since  1.6.9
		 * @author Grégory Viguier
		 *
		 * @param bool   $overwrite   Whether to overwrite the backup file.
		 * @param string $path        The file path.
		 * @param string $backup_path The backup path.
		 */
		$overwrite = apply_filters( 'imagify_backup_overwrite_backup', false, $path, $backup_path );

		// Copy the file.
		$this->filesystem->copy( $path, $backup_path, $overwrite, FS_CHMOD_FILE );

		// Make sure the backup copy exists.
		if ( ! $this->filesystem->exists( $backup_path ) ) {
			return new \WP_Error( 'backup_doesnt_exist', __( 'The file could not be saved.', 'imagify' ), array(
				'file_path'   => $this->filesystem->make_path_relative( $path ),
				'backup_path' => $this->filesystem->make_path_relative( $backup_path ),
			) );
		}

		return true;
	}

	/**
	 * Optimize a file with Imagify.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $args         {
	 *     Optional. An array of arguments.
	 *
	 *     @type bool   $backup             False to prevent backup. True to follow the user's setting. A backup can't be forced.
	 *     @type string $backup_path        If a backup must be done, this is the path to use. Default is the backup path used for the WP Media Library.
	 *     @type int    $optimization_level The optimization level (2=ultra, 1=aggressive, 0=normal).
	 *     @type bool   $keep_exif          To keep exif data or not.
	 *     @type string $convert            Set to 'webp' to convert the image to WebP.
	 *     @type string $context            The context.
	 *     @type int    $original_size      The file size, sent to the API.
	 * }
	 * @return \sdtClass|\WP_Error Optimized image data. A \WP_Error object on error.
	 */
	public function optimize( $args = [] ) {
		$args = array_merge( [
			'backup'             => true,
			'backup_path'        => null,
			'backup_source'      => null,
			'optimization_level' => 0,
			'keep_exif'          => true,
			'convert'            => '',
			'context'            => 'wp',
			'original_size'      => 0,
		], $args );

		$can_be_processed = $this->can_be_processed();

		if ( is_wp_error( $can_be_processed ) ) {
			return $can_be_processed;
		}

		// Check if external HTTP requests are blocked.
		if ( Imagify_Requirements::is_imagify_blocked() ) {
			return new \WP_Error( 'http_block_external', __( 'External HTTP requests are blocked.', 'imagify' ) );
		}

		/**
		 * Fires before a media file optimization.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $path Absolute path to the media file.
		 * @param array  $args Arguments passed to the method.
		*/
		do_action( 'imagify_before_optimize_file', $this->path, $args );

		/**
		 * Fires before to optimize the Image with Imagify.
		 *
		 * @since 1.0
		 * @deprecated
		 *
		 * @param string $path   Absolute path to the image file.
		 * @param bool   $backup True if a backup will be make.
		*/
		do_action_deprecated( 'before_do_imagify', [ $this->path, $args['backup'] ], '1.9', 'imagify_before_optimize_file' );

		if ( $args['backup'] ) {
			$backup_result = $this->backup( $args['backup_path'], $args['backup_source'] );

			if ( is_wp_error( $backup_result ) ) {
				// Stop the process if we can't backup the file.
				return $backup_result;
			}
		}

		// Send file for optimization and fetch the response.
		$data = [
			'normal'        => 0 === $args['optimization_level'],
			'aggressive'    => 1 === $args['optimization_level'],
			'ultra'         => 2 === $args['optimization_level'],
			'keep_exif'     => $args['keep_exif'],
			'original_size' => $args['original_size'],
			'context'       => $args['context'],
		];

		if ( $args['convert'] ) {
			$data['convert'] = $args['convert'];
		}

		$response = upload_imagify_image( [
			'image' => $this->path,
			'data'  => wp_json_encode( $data ),
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', $response->get_error_message() );
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$temp_file = download_url( $response->image );

		if ( is_wp_error( $temp_file ) ) {
			return new \WP_Error( 'temp_file_not_found', $temp_file->get_error_message() );
		}

		if ( 'webp' === $args['convert'] ) {
			$destination_path = $this->get_path_to_webp();
			$this->path       = $destination_path;
			$this->file_type  = null;
			$this->editor     = null;
		} else {
			$destination_path = $this->path;
		}

		$moved = $this->filesystem->move( $temp_file, $destination_path, true );

		if ( ! $moved ) {
			return new \WP_Error( 'move_error', __( 'The file could not be moved to its final destination.', 'imagify' ) );
		}

		/**
		 * Fires after to optimize the Image with Imagify.
		 *
		 * @since 1.0
		 * @deprecated
		 *
		 * @param string $path   Absolute path to the image file.
		 * @param bool   $backup True if a backup has been made.
		*/
		do_action_deprecated( 'after_do_imagify', [ $this->path, $args['backup'] ], '1.9', 'imagify_before_optimize_file' );

		/**
		 * Fires after a media file optimization.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param string $path Absolute path to the media file.
		 * @param array  $args Arguments passed to the method.
		*/
		do_action( 'imagify_after_optimize_file', $this->path, $args );

		return $response;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGE EDITOR (GD/IMAGEMAGICK) =========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get an image editor instance (WP_Image_Editor_Imagick, WP_Image_Editor_GD).
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return WP_Image_Editor_Imagick|WP_Image_Editor_GD|WP_Error
	 */
	protected function get_editor() {
		if ( isset( $this->editor ) ) {
			return $this->editor;
		}

		$this->editor = wp_get_image_editor( $this->path, [
			'methods' => $this->get_editor_methods(),
		] );

		if ( ! is_wp_error( $this->editor ) ) {
			return $this->editor;
		}

		$this->editor = new \WP_Error(
			'image_editor',
			sprintf(
				/* translators: %1$s is an error message, %2$s is a "More info?" link. */
				__( 'No php extensions are available to edit images on the server. ImageMagick or GD is required. The internal error is: %1$s. %2$s', 'imagify' ),
				$this->editor->get_error_message(),
				'<a href="' . esc_url( imagify_get_external_url( 'documentation-imagick-gd' ) ) . '" target="_blank">' . __( 'More info?', 'imagify' ) . '</a>'
			)
		);

		return $this->editor;
	}

	/**
	 * Get the image editor methods we will use.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	protected function get_editor_methods() {
		static $methods;

		if ( isset( $methods ) ) {
			return $methods;
		}

		$methods = [
			'resize',
			'multi_resize',
			'generate_filename',
			'save',
		];

		if ( $this->filesystem->can_get_exif() ) {
			$methods[] = 'rotate';
		}

		return $methods;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS TOOLS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Check if a file exceeds the weight limit (> 5mo).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_exceeded() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$size = $this->filesystem->size( $this->path );

		return $size > IMAGIFY_MAX_BYTES;
	}

	/**
	 * Tell if the current file is supported for a given context.
	 *
	 * @since  1.9
	 * @access public
	 * @see    imagify_get_mime_types()
	 * @author Grégory Viguier
	 *
	 * @param  array $allowed_mime_types A list of allowed mime types.
	 * @return bool
	 */
	public function is_supported( $allowed_mime_types ) {
		return in_array( $this->get_mime_type(), $allowed_mime_types, true );
	}

	/**
	 * Tell if the file is an image.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_image() {
		if ( isset( $this->is_image ) ) {
			return $this->is_image;
		}

		$this->is_image = strpos( $this->get_mime_type(), 'image/' ) === 0;

		return $this->is_image;
	}

	/**
	 * Tell if the file is a pdf.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_pdf() {
		return 'application/pdf' === $this->get_mime_type();
	}

	/**
	 * Get the file mime type.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_mime_type() {
		return $this->get_file_type()->type;
	}

	/**
	 * Get the file extension.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|null
	 */
	public function get_extension() {
		return $this->get_file_type()->ext;
	}

	/**
	 * Get the file path.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Replace the file extension by WebP.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The file path on success. False if not an image or on failure.
	 */
	public function get_path_to_webp() {
		if ( ! $this->is_image() ) {
			return false;
		}

		if ( $this->is_webp() ) {
			return false;
		}

		return imagify_path_to_webp( $this->path );
	}

	/**
	 * Tell if the file is a WebP image.
	 * Rejects "path/to/.webp" files.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_webp() {
		return preg_match( '@(?!^|/|\\\)\.webp$@i', $this->path );
	}

	/**
	 * Get the file mime type + file extension.
	 *
	 * @since  1.9
	 * @access protected
	 * @see    wp_check_filetype()
	 * @author Grégory Viguier
	 *
	 * @return object {
	 *     @type string $ext  The file extension.
	 *     @type string $type The mime type.
	 * }
	 */
	protected function get_file_type() {
		if ( isset( $this->file_type ) ) {
			return $this->file_type;
		}

		$this->file_type = (object) [
			'ext'  => '',
			'type' => '',
		];

		if ( ! $this->is_valid() ) {
			return $this->file_type;
		}

		$this->file_type = (object) wp_check_filetype( $this->path );

		return $this->file_type;
	}

	/**
	 * If the media is an image, get its width and height.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_dimensions() {
		if ( ! $this->is_image() ) {
			return [
				'width'  => 0,
				'height' => 0,
			];
		}

		$values = $this->filesystem->get_image_size( $this->path );

		return [
			'width'  => $values['width'],
			'height' => $values['height'],
		];
	}

	/**
	 * Get a plugin’s option.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  string $option_name The option nme.
	 * @return mixed
	 */
	protected function get_option( $option_name ) {
		if ( isset( $this->options[ $option_name ] ) ) {
			return $this->options[ $option_name ];
		}

		$this->options[ $option_name ] = get_imagify_option( $option_name );

		return $this->options[ $option_name ];
	}
}
