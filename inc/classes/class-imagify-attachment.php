<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify Attachment class.
 *
 * @since 1.0
 */
class Imagify_Attachment extends Imagify_Abstract_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.1';

	/**
	 * The editor instance used to resize files.
	 *
	 * @since 1.6.10
	 *
	 * @var object
	 * @access protected
	 */
	protected $editor;

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
	 * Update the metadata size of the attachment.
	 *
	 * @since 1.2
	 * @access public
	 *
	 * @return bool
	 */
	public function update_metadata_size() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_mime_type_supported() ) {
			return false;
		}

		$size = @getimagesize( $this->get_original_path() );

		if ( ! isset( $size[0], $size[1] ) ) {
			return false;
		}

		$metadata           = wp_get_attachment_metadata( $this->id );
		$metadata['width']  = $size[0];
		$metadata['height'] = $size[1];

		wp_update_attachment_metadata( $this->id, $metadata );
		return true;
	}

	/**
	 * Fills statistics data with values from $data array.
	 *
	 * @since 1.0
	 * @since 1.6.5 Not static anymore.
	 * @since 1.6.6 Removed the attachment ID parameter.
	 * @access public
	 *
	 * @param  array  $data      The statistics data.
	 * @param  object $response  The API response.
	 * @param  int    $url       The attachment URL.
	 * @param  string $size      The attachment size key.
	 * @return bool|array        False if the original size has an error or an array contains the data for other result.
	 */
	public function fill_data( $data, $response, $url, $size = 'full' ) {
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
				'file_url'       => $url,
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

		$original_dirname = trailingslashit( dirname( $this->get_original_path() ) );
		$thumbnail_path   = $original_dirname . $thumbnail_data['file'];
		$filesystem       = imagify_get_filesystem();

		if ( ! empty( $metadata_sizes[ $thumbnail_size ] ) && $filesystem->exists( $thumbnail_path ) ) {
			imagify_chmod_file( $thumbnail_path );
			return true;
		}

		// Get the editor.
		if ( ! isset( $this->editor ) ) {
			$this->editor = wp_get_image_editor( $this->get_backup_path() );
		}

		if ( is_wp_error( $this->editor ) ) {
			return $this->editor;
		}

		// Create the file.
		$result = $this->editor->multi_resize( array( $thumbnail_size => $thumbnail_data ) );

		if ( ! $result ) {
			return new WP_Error( 'image_resize_error' );
		}

		// The file name can change from what we expected (1px wider, etc).
		$backup_dirname    = trailingslashit( dirname( $this->get_backup_path() ) );
		$backup_thumb_path = $backup_dirname . $result[ $thumbnail_size ]['file'];
		$thumbnail_path    = $original_dirname . $result[ $thumbnail_size ]['file'];

		// Since we used the backup image as source, the new image is still in the backup folder, we need to move it.
		$filesystem->move( $backup_thumb_path, $thumbnail_path, true );

		if ( $filesystem->exists( $backup_thumb_path ) ) {
			$filesystem->delete( $backup_thumb_path );
		}

		if ( ! $filesystem->exists( $thumbnail_path ) ) {
			return new WP_Error( 'image_resize_error' );
		}

		imagify_chmod_file( $thumbnail_path );

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
		if ( ! $missing_sizes ) {
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
		if ( ! $this->is_mime_type_supported() ) {
			return;
		}

		$optimization_level = is_null( $optimization_level ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;
		$metadata           = $metadata ? $metadata : wp_get_attachment_metadata( $this->id );
		$sizes              = isset( $metadata['sizes'] ) ? (array) $metadata['sizes'] : array();

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		// Check if the full size is already optimized.
		if ( $this->is_optimized() && ( $this->get_optimization_level() === $optimization_level ) ) {
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

		set_transient( 'imagify-async-in-progress-' . $this->id, true, 10 * MINUTE_IN_SECONDS );

		// Get the resize values for the original size.
		$resized         = false;
		$do_resize       = get_imagify_option( 'resize_larger' );
		$resize_width    = get_imagify_option( 'resize_larger_w' );
		$attachment_size = @getimagesize( $attachment_path );

		if ( $do_resize && isset( $attachment_size[0] ) && $resize_width < $attachment_size[0] ) {
			$resized_attachment_path = $this->resize( $attachment_path, $attachment_size, $resize_width );

			if ( ! is_wp_error( $resized_attachment_path ) ) {
				// TODO (@Greg): Send an error message if the backup fails.
				imagify_backup_file( $attachment_path );

				$filesystem = imagify_get_filesystem();

				$filesystem->move( $resized_attachment_path, $attachment_path, true );
				imagify_chmod_file( $attachment_path );

				// If resized temp file still exists, delete it.
				if ( $filesystem->exists( $resized_attachment_path ) ) {
					$filesystem->delete( $resized_attachment_path );
				}

				$resized = true;
			}
		}

		// Optimize the original size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'wp',
			'resized'            => $resized,
			'original_size'      => $attachment_original_size,
		) );

		$data = $this->fill_data( null, $response, $attachment_url );

		// Save the optimization level.
		update_post_meta( $this->id, '_imagify_optimization_level', $optimization_level );

		if ( ! $data ) {
			delete_transient( 'imagify-async-in-progress-' . $this->id );
			return;
		}

		// If we resized the original with success, we have to update the attachment metadata.
		// If not, WordPress keeps the old attachment size.
		if ( $do_resize && $resized ) {
			$this->update_metadata_size();
		}

		// Optimize all thumbnails.
		if ( $sizes ) {
			$disallowed_sizes        = get_imagify_option( 'disallowed-sizes', array() );
			$is_active_for_network   = imagify_is_active_for_network();
			$attachment_path_dirname = trailingslashit( dirname( $attachment_path ) );
			$attachment_url_dirname  = trailingslashit( dirname( $attachment_url ) );

			foreach ( $sizes as $size_key => $size_data ) {
				// Check if this size has to be optimized.
				if ( ! $is_active_for_network && isset( $disallowed_sizes[ $size_key ] ) ) {
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size isn\'t authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
					);
					continue;
				}

				$thumbnail_path = $attachment_path_dirname . $size_data['file'];
				$thumbnail_url  = $attachment_url_dirname . $size_data['file'];

				// Optimize the thumbnail size.
				$response = do_imagify( $thumbnail_path, array(
					'backup'             => false,
					'optimization_level' => $optimization_level,
					'context'            => 'wp',
				) );

				$data = $this->fill_data( $data, $response, $thumbnail_url, $size_key );

				/**
				* Filter the optimization data of a specific thumbnail.
				*
				* @since 1.0
				*
				* @param  array  $data            The statistics data.
				* @param  object $response        The API response.
				* @param  int    $id              The attachment ID.
				* @param  string $thumbnail_path  The attachment path.
				* @param  string $thumbnail_url   The attachment URL.
				* @param  string $size_key        The attachment size key.
				* @param  bool   $is_aggressive   The optimization level.
				* @return array  $data            The new optimization data.
				*/
				$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level );
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

		delete_transient( 'imagify-async-in-progress-' . $this->id );

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
		if ( ! $this->is_mime_type_supported() ) {
			return new WP_Error( 'mime_type_not_supported', __( 'This type of file is not supported.', 'imagify' ) );
		}

		$optimization_level = is_null( $optimization_level ) ? (int) get_imagify_option( 'optimization_level', 1 ) : (int) $optimization_level;
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

		set_transient( 'imagify-async-in-progress-' . $this->id, true, 10 * MINUTE_IN_SECONDS );

		$errors = new WP_Error();

		// Create the missing thumbnails.
		$result_sizes = $this->create_missing_thumbnails( $missing_sizes );
		$failed_sizes = array_diff_key( $missing_sizes, $result_sizes );

		if ( $failed_sizes ) {
			$failed_count  = count( $failed_sizes );
			/* translators: %d is a number of thumbnails. */
			$error_message = _n( '%d thumbnail failed to be created', '%d thumbnails failed to be created', $failed_count, 'imagify' );
			$error_message = sprintf( $error_message, $failed_count );
			$errors->add( 'image_resize_error', $error_message, array( 'nbr_failed' => $failed_count, 'sizes_failed' => $failed_sizes, 'sizes_succeeded' => $result_sizes ) );
		}

		if ( ! $result_sizes ) {
			delete_transient( 'imagify-async-in-progress-' . $this->id );
			return $errors;
		}

		// Optimize.
		$imagify_data     = $this->get_data();
		$original_dirname = trailingslashit( dirname( $this->get_original_path() ) );
		$orig_url_dirname = trailingslashit( dirname( $this->get_original_url() ) );

		foreach ( $result_sizes as $size_name => $thumbnail_data ) {
			$thumbnail_path = $original_dirname . $thumbnail_data['file'];
			$thumbnail_url  = $orig_url_dirname . $thumbnail_data['file'];

			// Optimize the thumbnail size.
			$response = do_imagify( $thumbnail_path, array(
				'backup'             => false,
				'optimization_level' => $optimization_level,
				'context'            => 'wp',
			) );

			$imagify_data = $this->fill_data( $imagify_data, $response, $thumbnail_url, $size_name );

			/** This filter is documented in inc/classes/class-imagify-attachment.php. */
			$imagify_data = apply_filters( 'imagify_fill_thumbnail_data', $imagify_data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_name, $optimization_level );
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

		delete_transient( 'imagify-async-in-progress-' . $this->id );

		// Return the result.
		if ( $errors->get_error_codes() ) {
			return $errors;
		}

		return $result_sizes;
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
		if ( ! $this->is_mime_type_supported() ) {
			return;
		}

		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return;
		}

		$backup_path     = $this->get_backup_path();
		$attachment_path = $this->get_original_path();
		$filesystem      = imagify_get_filesystem();

		/**
		 * Fires before restoring an attachment.
		 *
		 * @since 1.0
		 *
		 * @param int $id The attachment ID
		*/
		do_action( 'before_imagify_restore_attachment', $this->id );

		// Create the original image from the backup.
		$filesystem->copy( $backup_path, $attachment_path, true );
		imagify_chmod_file( $attachment_path );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		remove_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', IMAGIFY_INT_MAX );
		wp_generate_attachment_metadata( $this->id, $attachment_path );

		// Remove old optimization data.
		$this->delete_imagify_data();

		// Restore the original size in the metadata.
		$this->update_metadata_size();

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
