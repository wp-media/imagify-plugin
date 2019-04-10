<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify Attachment class.
 *
 * @since 1.0
 * @since 1.9 Deprecated
 * @deprecated
 */
class Imagify_Attachment extends Imagify_Abstract_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.2';

	/**
	 * The constructor.
	 *
	 * @since  1.2
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int|object $id The attachment ID or the attachment itself.
	 *                       If an integer, make sure the attachment exists.
	 */
	public function __construct( $id = 0 ) {
		imagify_deprecated_class( get_class( $this ), '1.9', '\\Imagify\\Optimization\\Process\\WP( $id )' );

		parent::__construct();
	}

	/**
	 * Get the attachment backup file path, even if the file doesn't exist.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string|bool The file path. False on failure.
	 */
	public function get_raw_backup_path() {
		return get_imagify_attachment_backup_path( $this->get_original_path() );
	}

	/**
	 * Get the attachment optimization data.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_data() {
		$data = get_post_meta( $this->id, '_imagify_data', true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get the attachment optimization level.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return int
	 */
	public function get_optimization_level() {
		$level = get_post_meta( $this->id, '_imagify_optimization_level', true );
		return false !== $level ? (int) $level : false;
	}

	/**
	 * Get the attachment optimization status (success or error).
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_status() {
		$status = get_post_meta( $this->id, '_imagify_status', true );
		return is_string( $status ) ? $status : '';
	}

	/**
	 * Get the original attachment path.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_original_path() {
		return get_attached_file( $this->id );
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return string
	 */
	public function get_original_url() {
		return wp_get_attachment_url( $this->id );
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
		if ( ! $this->is_image() ) {
			return parent::get_dimensions();
		}

		$values = wp_get_attachment_image_src( $this->id, 'full' );

		return array(
			'width'  => $values[1],
			'height' => $values[2],
		);
	}

	/**
	 * Update the metadata size of the attachment.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @return bool
	 */
	public function update_metadata_size() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() || ! $this->is_image() ) {
			return false;
		}

		$size = $this->filesystem->get_image_size( $this->get_original_path() );

		if ( ! $size ) {
			return false;
		}

		/**
		 * Triggered before updating an image width and height into its metadata.
		 *
		 * @since  1.8.4
		 * @see    Imagify_Filesystem->get_image_size()
		 * @author Grégory Viguier
		 *
		 * @param int   $attachment_id The attachment ID.
		 * @param array $size          {
		 *     An array with, among other data:
		 *
		 *     @type int $width  The image width.
		 *     @type int $height The image height.
		 * }
		 */
		do_action( 'before_imagify_update_metadata_size', $this->id, $size );

		$metadata           = wp_get_attachment_metadata( $this->id );
		$metadata['width']  = $size['width'];
		$metadata['height'] = $size['height'];

		wp_update_attachment_metadata( $this->id, $metadata );

		/**
		 * Triggered after updating an image width and height into its metadata.
		 *
		 * @since  1.8.4
		 * @see    Imagify_Filesystem->get_image_size()
		 * @author Grégory Viguier
		 *
		 * @param int   $attachment_id The attachment ID.
		 * @param array $size          {
		 *     An array with, among other data:
		 *
		 *     @type int $width  The image width.
		 *     @type int $height The image height.
		 * }
		 */
		do_action( 'after_imagify_update_metadata_size', $this->id, $size );

		return true;
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
	 * @param  array  $data      The statistics data.
	 * @param  object $response  The API response.
	 * @param  string $size      The attachment size key.
	 * @return bool|array        False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $size = 'full' ) {
		$data          = is_array( $data ) ? $data : array();
		$data['sizes'] = ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ? $data['sizes'] : array();

		if ( empty( $data['stats'] ) ) {
			$data['stats'] = array(
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			);
		}

		if ( is_wp_error( $response ) ) {
			$error        = $response->get_error_message();
			$error_status = 'error';

			$data['sizes'][ $size ] = array(
				'success' => false,
				'error'   => $error,
			);

			// Update the error status for the original size.
			if ( 'full' === $size ) {
				update_post_meta( $this->id, '_imagify_data', $data );

				if ( false !== strpos( $error, 'This image is already compressed' ) ) {
					$error_status = 'already_optimized';
				}

				update_post_meta( $this->id, '_imagify_status', $error_status );

				return false;
			}
		} else {
			$response = (object) array_merge( array(
				'original_size' => 0,
				'new_size'      => 0,
				'percent'       => 0,
			), (array) $response );

			$data['sizes'][ $size ] = array(
				'success'        => true,
				'original_size'  => $response->original_size,
				'optimized_size' => $response->new_size,
				'percent'        => $response->percent,
			);

			$data['stats']['original_size']  += $response->original_size;
			$data['stats']['optimized_size'] += $response->new_size;
		} // End if().

		return $data;
	}

	/**
	 * Create a thumbnail if it doesn't exist.
	 *
	 * @since  1.6.10
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $thumbnail_data The thumbnail data (width, height, crop, name, file).
	 * @return bool|array|object     True if the file exists. An array of thumbnail data if the file has just been created (width, height, crop, file). A WP_Error object on error.
	 */
	protected function create_thumbnail( $thumbnail_data ) {
		$thumbnail_size = $thumbnail_data['name'];
		$metadata       = wp_get_attachment_metadata( $this->id );
		$metadata_sizes = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();

		$original_dirname = $this->filesystem->dir_path( $this->get_original_path() );
		$thumbnail_path   = $original_dirname . $thumbnail_data['file'];

		if ( ! empty( $metadata_sizes[ $thumbnail_size ] ) && $this->filesystem->exists( $thumbnail_path ) ) {
			$this->filesystem->chmod_file( $thumbnail_path );
			return true;
		}

		// Get the editor.
		$editor = $this->get_editor( $this->get_backup_path() );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		// Create the file.
		$result = $editor->multi_resize( array( $thumbnail_size => $thumbnail_data ) );

		if ( ! $result ) {
			return new WP_Error( 'image_resize_error' );
		}

		// The file name can change from what we expected (1px wider, etc).
		$backup_dirname    = $this->filesystem->dir_path( $this->get_backup_path() );
		$backup_thumb_path = $backup_dirname . $result[ $thumbnail_size ]['file'];
		$thumbnail_path    = $original_dirname . $result[ $thumbnail_size ]['file'];

		// Since we used the backup image as source, the new image is still in the backup folder, we need to move it.
		$moved = $this->filesystem->move( $backup_thumb_path, $thumbnail_path, true );

		if ( ! $moved ) {
			return new WP_Error( 'image_resize_error' );
		}

		return reset( $result );
	}

	/**
	 * Create all missing thumbnails if they don't exist and update the attachment metadata.
	 *
	 * @since  1.6.10
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  array $missing_sizes An array of thumbnail data (width, height, crop, name, file) for each thumbnail size.
	 * @return array                An array of thumbnail data (width, height, crop, file).
	 */
	protected function create_missing_thumbnails( $missing_sizes ) {
		if ( ! $missing_sizes || ! $this->is_image() ) {
			return array();
		}

		$metadata            = wp_get_attachment_metadata( $this->id );
		$metadata['sizes']   = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();
		$thumbnail_new_datas = array();
		$thumbnail_metadatas = array();

		// Create the missing thumbnails.
		foreach ( $missing_sizes as $size_name => $thumbnail_data ) {
			$result = $this->create_thumbnail( $thumbnail_data );

			if ( is_array( $result ) ) {
				// New file.
				$thumbnail_new_datas[ $size_name ] = $result;
				unset( $thumbnail_new_datas[ $size_name ]['name'] );
			} elseif ( true === $result ) {
				// The file already exists.
				$thumbnail_metadatas[ $size_name ] = $metadata['sizes'][ $size_name ];
			}
		}

		// Save the new data into the attachment metadata.
		if ( $thumbnail_new_datas ) {
			$metadata['sizes'] = array_merge( $metadata['sizes'], $thumbnail_new_datas );

			/**
			 * Here we don't use wp_update_attachment_metadata() to prevent triggering unwanted hooks.
			 */
			update_post_meta( $this->id, '_wp_attachment_metadata', $metadata );
		}

		return array_merge( $thumbnail_metadatas, $thumbnail_new_datas );
	}

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param  int   $optimization_level  The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @param  array $metadata            The attachment meta data.
	 * @return array $optimized_data      The optimization data.
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return;
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );
		$metadata           = $metadata ? $metadata : wp_get_attachment_metadata( $this->id );
		$sizes              = ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) && $this->is_image() ? $metadata['sizes'] : array();

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && $this->get_optimization_level() === $optimization_level ) {
			return;
		}

		// Get file path & URL for original image.
		$attachment_path          = $this->get_original_path();
		$attachment_url           = $this->get_original_url();
		$attachment_original_size = $this->get_original_size( false );

		/**
		 * Fires before optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID.
		 */
		do_action( 'before_imagify_optimize_attachment', $this->id );

		$this->set_running_status();

		// Get the resize values for the original size.
		$resized   = false;
		$do_resize = $this->is_image() && get_imagify_option( 'resize_larger' );

		if ( $do_resize ) {
			$resize_width    = get_imagify_option( 'resize_larger_w' );
			$attachment_size = $this->filesystem->get_image_size( $attachment_path );

			if ( $attachment_size && $resize_width < $attachment_size['width'] ) {
				$resized_attachment_path = $this->resize( $attachment_path, $attachment_size, $resize_width );

				if ( ! is_wp_error( $resized_attachment_path ) ) {
					// TODO (@Greg): Send an error message if the backup fails.
					imagify_backup_file( $attachment_path );

					$this->filesystem->move( $resized_attachment_path, $attachment_path, true );

					$resized = true;
				}
			}
		}

		// Optimize the original size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => $this->get_context(),
			'resized'            => $resized,
			'original_size'      => $attachment_original_size,
		) );

		$data = $this->fill_data( null, $response );

		/**
		 * Filter the optimization data of the full size.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param array  $data               The statistics data.
		 * @param object $response           The API response.
		 * @param int    $id                 The attachment ID.
		 * @param string $attachment_path    The attachment path.
		 * @param string $attachment_url     The attachment URL.
		 * @param string $size_key           The attachment size key. The value is obviously 'full' but it's kept for concistancy with other filters.
		 * @param int    $optimization_level The optimization level.
		 * @param array  $metadata           WP metadata.
		 */
		$data = apply_filters( 'imagify_fill_full_size_data', $data, $response, $this->id, $attachment_path, $attachment_url, 'full', $optimization_level, $metadata );

		// Save the optimization level.
		update_post_meta( $this->id, '_imagify_optimization_level', $optimization_level );

		// If we resized the original with success, we have to update the attachment metadata.
		// If not, WordPress keeps the old attachment size.
		if ( $resized ) {
			$this->update_metadata_size();
		}

		if ( ! $data ) {
			$this->delete_running_status();
			return;
		}

		// Optimize all thumbnails.
		if ( $sizes ) {
			$disallowed_sizes        = get_imagify_option( 'disallowed-sizes' );
			$is_active_for_network   = imagify_is_active_for_network();
			$attachment_path_dirname = $this->filesystem->dir_path( $attachment_path );
			$attachment_url_dirname  = $this->filesystem->dir_path( $attachment_url );

			foreach ( $sizes as $size_key => $size_data ) {
				$thumbnail_path = $attachment_path_dirname . $size_data['file'];
				$thumbnail_url  = $attachment_url_dirname . $size_data['file'];

				// Check if this size has to be optimized.
				if ( ! $is_active_for_network && isset( $disallowed_sizes[ $size_key ] ) ) {
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
					);

					/**
					 * Filter the optimization data of an unauthorized thumbnail.
					 *
					 * @since  1.8
					 * @author Grégory Viguier
					 *
					 * @param array  $data               The statistics data.
					 * @param int    $id                 The attachment ID.
					 * @param string $thumbnail_path     The thumbnail path.
					 * @param string $thumbnail_url      The thumbnail URL.
					 * @param string $size_key           The thumbnail size key.
					 * @param int    $optimization_level The optimization level.
					 * @param array  $metadata           WP metadata.
					 */
					$data = apply_filters( 'imagify_fill_unauthorized_thumbnail_data', $data, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level, $metadata );
					continue;
				}

				// Optimize the thumbnail size.
				$response = do_imagify( $thumbnail_path, array(
					'backup'             => false,
					'optimization_level' => $optimization_level,
					'context'            => $this->get_context(),
				) );

				$data = $this->fill_data( $data, $response, $size_key );

				/**
				 * Filter the optimization data of a specific thumbnail.
				 *
				 * @since 1.0
				 * @since 1.8 Added $metadata.
				 *
				 * @param array  $data               The statistics data.
				 * @param object $response           The API response.
				 * @param int    $id                 The attachment ID.
				 * @param string $thumbnail_path     The thumbnail path.
				 * @param string $thumbnail_url      The thumbnail URL.
				 * @param string $size_key           The thumbnail size key.
				 * @param int    $optimization_level The optimization level.
				 * @param array  $metadata           WP metadata.
				 */
				$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level, $metadata );
			} // End foreach().
		} // End if().

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $this->id, '_imagify_data', $data );
		update_post_meta( $this->id, '_imagify_status', 'success' );

		$optimized_data = $this->get_data();

		/**
		 * Fires after optimizing an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int   $id              The attachment ID.
		 * @param array $optimized_data  The optimization data.
		 */
		do_action( 'after_imagify_optimize_attachment', $this->id, $optimized_data );

		$this->delete_running_status();

		return $optimized_data;
	}

	/**
	 * Optimize missing thumbnail sizes with Imagify.
	 *
	 * @since  1.6.10
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $optimization_level The optimization level (2=ultra, 1=aggressive, 0=normal).
	 * @return array|object            An array of thumbnail data, size by size. A WP_Error object on failure.
	 */
	public function optimize_missing_thumbnails( $optimization_level = null ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() || ! $this->is_image() ) {
			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );
		$missing_sizes      = $this->get_unoptimized_sizes();

		if ( ! $missing_sizes ) {
			// We have everything we need.
			return array();
		}

		// Stop the process if there is no backup file to use.
		if ( ! $this->has_backup() ) {
			return new WP_Error( 'no_backup', __( 'This file has no backup file.', 'imagify' ) );
		}

		/**
		 * Fires before optimizing the missing thumbnails.
		 *
		 * @since  1.6.10
		 * @author Grégory Viguier
		 * @see    $this->get_unoptimized_sizes()
		 *
		 * @param int   $id            The attachment ID.
		 * @param array $missing_sizes An array of the missing sizes.
		*/
		do_action( 'before_imagify_optimize_missing_thumbnails', $this->id, $missing_sizes );

		$this->set_running_status();

		$errors = new WP_Error();

		// Create the missing thumbnails.
		$result_sizes = $this->create_missing_thumbnails( $missing_sizes );
		$failed_sizes = array_diff_key( $missing_sizes, $result_sizes );

		if ( $failed_sizes ) {
			$failed_count  = count( $failed_sizes );
			/* translators: %d is a number of thumbnails. */
			$error_message = _n( '%d thumbnail failed to be created', '%d thumbnails failed to be created', $failed_count, 'imagify' );
			$error_message = sprintf( $error_message, $failed_count );

			$errors->add( 'image_resize_error', $error_message, array(
				'nbr_failed'      => $failed_count,
				'sizes_failed'    => $failed_sizes,
				'sizes_succeeded' => $result_sizes,
			) );
		}

		if ( ! $result_sizes ) {
			$this->delete_running_status();
			return $errors;
		}

		// Optimize.
		$imagify_data     = $this->get_data();
		$original_dirname = $this->filesystem->dir_path( $this->get_original_path() );
		$orig_url_dirname = $this->filesystem->dir_path( $this->get_original_url() );

		foreach ( $result_sizes as $size_name => $thumbnail_data ) {
			$thumbnail_path = $original_dirname . $thumbnail_data['file'];
			$thumbnail_url  = $orig_url_dirname . $thumbnail_data['file'];

			// Optimize the thumbnail size.
			$response = do_imagify( $thumbnail_path, array(
				'backup'             => false,
				'optimization_level' => $optimization_level,
				'context'            => $this->get_context(),
			) );

			$imagify_data = $this->fill_data( $imagify_data, $response, $size_name );
			$metadata     = wp_get_attachment_metadata( $this->id );

			/** This filter is documented in inc/classes/class-imagify-attachment.php. */
			$imagify_data = apply_filters( 'imagify_fill_thumbnail_data', $imagify_data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_name, $optimization_level, $metadata );
		}

		// Save Imagify data.
		$imagify_data['stats']['percent'] = round( ( ( $imagify_data['stats']['original_size'] - $imagify_data['stats']['optimized_size'] ) / $imagify_data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $this->id, '_imagify_data', $imagify_data );

		/**
		 * Fires after optimizing the missing thumbnails.
		 *
		 * @since  1.6.10
		 * @author Grégory Viguier
		 * @see    $this->create_missing_thumbnails()
		 *
		 * @param int    $id           The attachment ID.
		 * @param array  $result_sizes An array of created thumbnails.
		 * @param object $errors       A WP_Error object that stores thumbnail creation failures.
		 */
		do_action( 'after_imagify_optimize_missing_thumbnails', $this->id, $result_sizes, $errors );

		$this->delete_running_status();

		// Return the result.
		if ( $errors->get_error_codes() ) {
			return $errors;
		}

		return $result_sizes;
	}

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
	public function reoptimize_thumbnails( $sizes ) {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() || ! $this->is_image() ) {
			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		if ( ! $sizes || ! is_array( $sizes ) ) {
			return;
		}

		/**
		 * Fires before re-optimizing some thumbnails of an attachment.
		 *
		 * @since  1.7.1
		 * @author Grégory Viguier
		 *
		 * @param int   $id    The attachment ID.
		 * @param array $sizes The sizes to optimize.
		*/
		do_action( 'before_imagify_reoptimize_attachment_thumbnails', $this->id, $sizes );

		$this->set_running_status();

		$data = $this->get_data();

		$data['sizes'] = ! empty( $data['sizes'] ) && is_array( $data['sizes'] ) ? $data['sizes'] : array();

		foreach ( $sizes as $size_key => $size_data ) {
			// In case it's a disallowed size, fill in the new data. If it's not, it will be overwritten by $this->fill_data() later.
			$data['sizes'][ $size_key ] = array(
				'success' => false,
				'error'   => __( 'This size is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
			);
		}

		// Update global attachment stats.
		$data['stats'] = array(
			'original_size'  => 0,
			'optimized_size' => 0,
			'percent'        => 0,
		);

		foreach ( $data['sizes'] as $size_data ) {
			if ( ! empty( $size_data['original_size'] ) ) {
				$data['stats']['original_size'] += $size_data['original_size'];
			}
			if ( ! empty( $size_data['optimized_size'] ) ) {
				$data['stats']['optimized_size'] += $size_data['optimized_size'];
			}
		}

		// Remove disallowed sizes.
		if ( ! imagify_is_active_for_network() ) {
			$sizes = array_diff_key( $sizes, get_imagify_option( 'disallowed-sizes' ) );
		}

		if ( ! $sizes ) {
			$data['stats']['percent'] = $data['stats']['original_size'] ? round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 ) : 0;
			update_post_meta( $this->id, '_imagify_data', $data );
			$this->delete_running_status();
			return;
		}

		$optimization_level      = $this->get_optimization_level();
		$thumbnail_path          = $this->get_original_path();
		$thumbnail_url           = $this->get_original_url();
		$attachment_path_dirname = $this->filesystem->dir_path( $thumbnail_path );
		$attachment_url_dirname  = $this->filesystem->dir_path( $thumbnail_url );

		foreach ( $sizes as $size_key => $size_data ) {
			$thumbnail_path = $attachment_path_dirname . $size_data['file'];
			$thumbnail_url  = $attachment_url_dirname . $size_data['file'];

			// Optimize the thumbnail size.
			$response = do_imagify( $thumbnail_path, array(
				'backup'             => false,
				'optimization_level' => $optimization_level,
				'context'            => $this->get_context(),
			) );

			$data = $this->fill_data( $data, $response, $size_key );

			/** This filter is documented in /inc/classes/class-imagify-attachment.php. */
			$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level );
		} // End foreach().

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $this->id, '_imagify_data', $data );

		/**
		 * Fires after re-optimizing some thumbnails of an attachment.
		 *
		 * @since  1.7.1
		 * @author Grégory Viguier
		 *
		 * @param int   $id    The attachment ID.
		 * @param array $sizes The sizes to optimize.
		*/
		do_action( 'after_imagify_reoptimize_attachment_thumbnails', $this->id, $sizes );

		$this->delete_running_status();
	}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @return void
	 */
	public function restore() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return;
		}

		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return;
		}

		$backup_path     = $this->get_backup_path();
		$attachment_path = $this->get_original_path();

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		 */
		do_action( 'before_imagify_restore_attachment', $this->id );

		// Create the original image from the backup.
		$this->filesystem->copy( $backup_path, $attachment_path, true );
		$this->filesystem->chmod_file( $attachment_path );

		if ( $this->is_image() ) {
			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			wp_generate_attachment_metadata( $this->id, $attachment_path );

			// Restore the original size in the metadata.
			$this->update_metadata_size();
		}

		// Remove old optimization data.
		$this->delete_imagify_data();

		/**
		 * Fires after restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		 */
		do_action( 'after_imagify_restore_attachment', $this->id );
	}
}
