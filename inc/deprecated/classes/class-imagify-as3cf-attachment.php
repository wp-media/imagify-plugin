<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify WP Offload S3 attachment class.
 *
 * @since  1.6.6
 * @since  1.9 Deprecated
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_AS3CF_Attachment extends Imagify_Attachment {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.2';

	/**
	 * Tell if AS3CF settings will be used for this attachment.
	 *
	 * @var bool
	 */
	protected $use_s3_settings;

	/**
	 * Tell if the files should be deleted once sent to S3.
	 *
	 * @var bool
	 */
	protected $delete_files;

	/**
	 * The constructor.
	 *
	 * @since  1.5
	 * @author Jonathan Buttigieg
	 *
	 * @param int|object $id An image attachment ID or a NGG object.
	 */
	public function __construct( $id ) {
		imagify_deprecated_class( get_class( $this ), '1.9' );

		parent::__construct( $id );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ATTACHMENT PATHS AND URLS =============================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the original attachment path.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return string|bool Path to the file if it exists or has been successfully retrieved from S3. False on failure.
	 */
	public function get_original_path() {
		return $this->get_thumbnail_path();
	}

	/**
	 * Get a thumbnail path.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $size_file The basename of the file. If not provided, the path to the main file is returned.
	 * @return string|bool       Path to the file if it exists or has been successfully retrieved from S3. False on failure.
	 */
	public function get_thumbnail_path( $size_file = false ) {
		if ( ! $this->is_valid() ) {
			return '';
		}

		$file_path = get_attached_file( $this->id, true );

		if ( $size_file ) {
			// It's not the full size.
			$file_path = $this->filesystem->dir_path( $file_path ) . $size_file;
		}

		return $file_path;
	}

	/**
	 * Get the original attachment URL.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return string|bool The main file URL. False on failure.
	 */
	public function get_original_url() {
		return $this->get_thumbnail_url();
	}

	/**
	 * Get a thumbnail URL.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $size_file The basename of the file. If not provided, the main file's URL is returned.
	 * @return string|bool       The file URL. False on failure.
	 */
	public function get_thumbnail_url( $size_file = false ) {
		if ( ! $this->is_extension_supported() ) {
			return false;
		}

		$file_url = wp_get_attachment_url( $this->id );

		if ( $size_file ) {
			// It's not the full size.
			$file_url = $this->filesystem->dir_path( $file_url ) . $size_file;
		}

		return $file_url;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** THE PUBLIC STUFF ======================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Optimize all sizes with Imagify.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  int   $optimization_level  The optimization level (2 = ultra, 1 = aggressive, 0 = normal).
	 * @param  array $metadata            The attachment meta data, containing the sizes. Provide only for a new attachment.
	 * @return array|bool                 The optimization data. False on failure.
	 */
	public function optimize( $optimization_level = null, $metadata = array() ) {
		$metadata_changed = false;

		/**
		 * Make some sanity tests first.
		 */

		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return false;
		}

		// To avoid issue with "original_size" at 0 in "_imagify_data".
		if ( 0 === (int) $this->get_stats_data( 'original_size' ) ) {
			$this->delete_imagify_data();
		}

		$optimization_level = isset( $optimization_level ) ? (int) $optimization_level : get_imagify_option( 'optimization_level' );

		// Check if the full size is already optimized with this level.
		if ( $this->is_optimized() && $this->get_optimization_level() === $optimization_level ) {
			return false;
		}

		// Get file path of the full size.
		$attachment_path = $this->get_original_path();
		$attachment_url  = $this->get_original_url();

		if ( ! $attachment_path ) {
			// We're in deep sh**.
			return false;
		}

		if ( ! $this->filesystem->exists( $attachment_path ) && ! $this->get_file_from_s3( $attachment_path ) ) {
			// The file doesn't exist and couldn't be retrieved from S3.
			return false;
		}

		/**
		 * Start the process.
		 */
		$this->set_running_status();

		/** This hook is documented in /inc/classes/class-imagify-attachment.php. */
		do_action( 'before_imagify_optimize_attachment', $this->id );

		$metadata = $this->set_deletion_status( $metadata );

		// Store the paths of the files that may be deleted once optimized and sent to S3.
		$to_delete      = array();
		$filesize_total = 0;

		// Maybe resize (and backup) the image.
		$resized = $this->is_image() && $this->maybe_resize( $attachment_path );

		if ( $resized ) {
			$size = $this->filesystem->get_image_size( $attachment_path );

			if ( $size ) {
				$metadata['width']  = $size['width'];
				$metadata['height'] = $size['height'];
				$metadata_changed   = true;
			}
		}

		// Optimize the full size.
		$response = do_imagify( $attachment_path, array(
			'optimization_level' => $optimization_level,
			'context'            => 'wp',
			'resized'            => $resized,
			'original_size'      => $this->get_original_size( false ),
		) );

		$data = $this->fill_data( null, $response );

		if ( $this->delete_files ) {
			$to_delete[] = $attachment_path;
			// This is used by AS3CF.
			$bytes       = $this->filesystem->size( $attachment_path );

			if ( false !== $bytes ) {
				$metadata_changed     = true;
				$filesize_total      += $bytes;
				$metadata['filesize'] = $bytes;
			} else {
				$metadata['filesize'] = 0;
			}
		}

		/** This filter is documented in /inc/classes/class-imagify-attachment.php. */
		$data = apply_filters( 'imagify_fill_full_size_data', $data, $response, $this->id, $attachment_path, $attachment_url, 'full', $optimization_level, $metadata );

		// Save the optimization level.
		update_post_meta( $this->id, '_imagify_optimization_level', $optimization_level );

		if ( ! $data ) {
			// The optimization failed.
			$metadata = $metadata_changed ? $metadata : false;
			$this->cleanup( $metadata, $to_delete );
			return false;
		}

		// Optimize all thumbnails.
		if ( ! empty( $metadata['sizes'] ) ) {
			$disallowed_sizes      = get_imagify_option( 'disallowed-sizes' );
			$is_active_for_network = imagify_is_active_for_network();

			foreach ( $metadata['sizes'] as $size_key => $size_data ) {
				$thumbnail_path = $this->get_thumbnail_path( $size_data['file'] );
				$thumbnail_url  = $this->get_thumbnail_url( $size_data['file'] );

				if ( $this->delete_files ) {
					$to_delete[] = $thumbnail_path;

					// Even if this size must not be optimized ($disallowed_sizes), we must fetch the file from S3 to get its size, and not to trigger a `WP_Error` in `upload_attachment_to_s3()`.
					if ( ! $this->filesystem->exists( $thumbnail_path ) && ! $this->get_file_from_s3( $thumbnail_path ) ) {
						// Doesn't exist and couldn't be retrieved from S3.
						$data['sizes'][ $size_key ] = array(
							'success' => false,
							'error'   => __( 'This size could not be retrieved from Amazon S3.', 'imagify' ),
						);
						continue;
					}

					// This is used by AS3CF.
					$bytes = $this->filesystem->size( $thumbnail_path );

					if ( false !== $bytes ) {
						$filesize_total += $bytes;
					}
				}

				// Check if this size has to be optimized.
				if ( ! $is_active_for_network && isset( $disallowed_sizes[ $size_key ] ) && $this->is_image() ) {
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size is not authorized to be optimized. Update your Imagify settings if you want to optimize it.', 'imagify' ),
					);

					/** This filter is documented in /inc/classes/class-imagify-attachment.php. */
					$data = apply_filters( 'imagify_fill_unauthorized_thumbnail_data', $data, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level, $metadata );
					continue;
				}

				if ( ! $this->delete_files && ! $this->filesystem->exists( $thumbnail_path ) && ! $this->get_file_from_s3( $thumbnail_path ) ) {
					// Doesn't exist and couldn't be retrieved from S3.
					$data['sizes'][ $size_key ] = array(
						'success' => false,
						'error'   => __( 'This size could not be retrieved from Amazon S3.', 'imagify' ),
					);
					continue;
				}

				if ( ! $this->is_image() ) {
					continue;
				}

				// Optimize the thumbnail size.
				$response = do_imagify( $thumbnail_path, array(
					'backup'             => false,
					'optimization_level' => $optimization_level,
					'context'            => 'wp',
				) );

				$data = $this->fill_data( $data, $response, $size_key );

				/** This filter is documented in /inc/classes/class-imagify-attachment.php. */
				$data = apply_filters( 'imagify_fill_thumbnail_data', $data, $response, $this->id, $thumbnail_path, $thumbnail_url, $size_key, $optimization_level, $metadata );
			} // End foreach().
		} // End if().

		$data['stats']['percent'] = round( ( ( $data['stats']['original_size'] - $data['stats']['optimized_size'] ) / $data['stats']['original_size'] ) * 100, 2 );

		update_post_meta( $this->id, '_imagify_data', $data );
		update_post_meta( $this->id, '_imagify_status', 'success' );

		if ( $this->delete_files && $filesize_total ) {
			// Add the total file size for all image sizes. This is a meta used by AS3CF.
			update_post_meta( $this->id, 'wpos3_filesize_total', $filesize_total );
		}

		$optimized_data = $this->get_data();

		/** This hook is documented in /inc/classes/class-imagify-attachment.php. */
		do_action( 'after_imagify_optimize_attachment', $this->id, $optimized_data );

		$sent      = $this->maybe_send_attachment_to_s3( $metadata, $attachment_path );
		// Update metadata only if they changed.
		$metadata  = $metadata_changed ? $metadata  : false;
		// Delete files only if they have been uploaded to S3.
		$to_delete = $sent             ? $to_delete : array();

		$this->cleanup( $metadata, $to_delete );

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

		/**
		 * Create missing thumbnails and optimize them.
		 */
		$result       = parent::optimize_missing_thumbnails( $optimization_level );
		$result_sizes = array();

		$this->set_running_status();

		if ( is_array( $result ) ) {
			// All good.
			$result_sizes = $result;
		} elseif ( is_wp_error( $result ) ) {
			// Some thumbnails could not be created. Lets see if some were.
			$result_sizes = $result->get_error_data( 'image_resize_error' );
			$result_sizes = ! empty( $result_sizes['sizes_succeeded'] ) ? $result_sizes['sizes_succeeded'] : array();
		}

		if ( ! $result_sizes ) {
			// No thumbnails created.
			$this->delete_running_status();
			return $result;
		}

		/**
		 * Fetch all images from S3 if they're not on the server.
		 * S3 Offload needs ALL images (so it can update its metas), we can't just send some of them without entering Hell -_-'.
		 */
		$metadata = $this->set_deletion_status();

		if ( ! $this->can_send_to_s3() ) {
			// The other thumbnails are not on S3, so we don't need to send the new ones.
			$this->delete_running_status();
			return $result;
		}

		/**
		 * The main file.
		 */
		$attachment_path  = $this->get_original_path();
		$to_delete        = array();
		$to_skip          = array();
		$filesize_total   = 0;
		$metadata_changed = false;

		if ( ! $attachment_path ) {
			// WAT?!
			if ( ! is_wp_error( $result ) ) {
				$result = new WP_Error( 'no_attachment_path', __( 'Files could not be sent to Amazon S3.', 'imagify' ), array(
					'sizes_succeeded' => $result_sizes,
				) );
			} else {
				$result->add( 'no_attachment_path', __( 'Files could not be sent to Amazon S3.', 'imagify' ) );
			}

			$this->delete_running_status();
			return $result;
		}

		if ( ! $this->filesystem->exists( $attachment_path ) && ! $this->get_file_from_s3( $attachment_path ) ) {
			// The file doesn't exist and couldn't be retrieved from S3.
			if ( ! is_wp_error( $result ) ) {
				$result = new WP_Error( 'main_file_not_on_s3', __( 'The main image could not be retrieved from Amazon S3.', 'imagify' ), array(
					'sizes_succeeded' => $result_sizes,
				) );
			} else {
				$result->add( 'main_file_not_on_s3', __( 'The main image could not be retrieved from Amazon S3.', 'imagify' ) );
			}

			$this->delete_running_status();
			return $result;
		}

		// Files that must not be retrieved from S3.
		foreach ( $result_sizes as $size_key => $size_data ) {
			$to_skip[] = $this->get_thumbnail_path( $size_data['file'] );
		}

		// Store the paths of the files that may be deleted once sent to S3.
		if ( $this->delete_files ) {
			$to_delete[] = $attachment_path;
			$to_delete   = array_merge( $to_delete, $to_skip );

			// This is used by AS3CF.
			$bytes = $this->filesystem->size( $attachment_path );

			if ( false !== $bytes ) {
				$metadata_changed     = true;
				$filesize_total      += $bytes;
				$metadata['filesize'] = $bytes;
			} elseif ( ! isset( $metadata['filesize'] ) ) {
				$metadata_changed     = true;
				$metadata['filesize'] = 0;
			}
		}

		/**
		 * The thumbnails.
		 */
		if ( ! empty( $metadata['sizes'] ) ) {
			$to_skip = array_flip( $to_skip );

			foreach ( $metadata['sizes'] as $size_key => $size_data ) {
				$thumbnail_path = $this->get_thumbnail_path( $size_data['file'] );

				if ( isset( $to_skip[ $thumbnail_path ] ) ) {
					continue;
				}

				if ( ! $this->filesystem->exists( $thumbnail_path ) && ! $this->get_file_from_s3( $thumbnail_path ) ) {
					// The file doesn't exist and couldn't be retrieved from S3.
					if ( ! is_wp_error( $result ) ) {
						$result = new WP_Error( 'thumbnail_not_on_s3', __( 'This size could not be retrieved from Amazon S3.', 'imagify' ), array(
							'sizes_succeeded' => $result_sizes,
							'size'            => $size_key,
						) );
					} else {
						$result->add( 'thumbnail_not_on_s3', __( 'This size could not be retrieved from Amazon S3.', 'imagify' ), array(
							'size' => $size_key,
						) );
					}

					$this->delete_running_status();
					return $result;
				}

				if ( $this->delete_files ) {
					$to_delete[] = $thumbnail_path;

					// This is used by AS3CF.
					$bytes = $this->filesystem->size( $thumbnail_path );

					if ( false !== $bytes ) {
						$filesize_total += $bytes;
					}
				}
			} // End foreach().
		} // End if().

		if ( $this->delete_files && $filesize_total ) {
			// Add the total file size for all image sizes. This is a meta used by AS3CF.
			update_post_meta( $this->id, 'wpos3_filesize_total', $filesize_total );
		}

		$sent      = $this->maybe_send_attachment_to_s3( $metadata, $attachment_path );
		// Update metadata only if they changed.
		$metadata  = $metadata_changed ? $metadata  : false;
		// Delete files only if they have been uploaded to S3.
		$to_delete = $sent             ? $to_delete : array();

		$this->cleanup( $metadata, $to_delete );

		return $result;
	}

	/**
	 * Re-optimize the given thumbnail sizes to the same level.
	 * Not supported yet in this context.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $sizes The sizes to optimize.
	 * @return array|void             A WP_Error object on failure.
	 */
	public function reoptimize_thumbnails( $sizes ) {}

	/**
	 * Process an attachment restoration from the backup file.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return array A list of files sent to S3.
	 */
	public function restore() {
		// Check if the attachment extension is allowed.
		if ( ! $this->is_extension_supported() ) {
			return false;
		}

		// Stop the process if there is no backup file to restore.
		if ( ! $this->has_backup() ) {
			return false;
		}

		/** This hook is documented in /inc/classes/class-imagify-attachment.php. */
		do_action( 'before_imagify_restore_attachment', $this->id );

		$backup_path     = $this->get_backup_path();
		$attachment_path = $this->get_original_path();

		if ( ! $attachment_path ) {
			return false;
		}

		// Create the original image from the backup.
		$this->filesystem->copy( $backup_path, $attachment_path, true );
		$this->filesystem->chmod_file( $attachment_path );

		if ( ! $this->filesystem->exists( $attachment_path ) ) {
			return false;
		}

		$this->set_deletion_status();

		// Remove old optimization data.
		$this->delete_imagify_data();

		if ( ! $this->is_image() ) {
			// We need to delete the thumbnails for pdf, or new ones will be generated with new unique file names.
			$metadata = wp_get_attachment_metadata( $this->id );

			if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size_key => $size_data ) {
					$thumbnail_path = $this->get_thumbnail_path( $size_data['file'] );

					if ( $this->filesystem->exists( $thumbnail_path ) ) {
						$this->filesystem->delete( $thumbnail_path );
					}
				}
			}
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Generate new thumbnails and new metadata.
		$metadata = wp_generate_attachment_metadata( $this->id, $attachment_path );

		// Send to S3.
		$sent           = $this->maybe_send_attachment_to_s3( $metadata, $attachment_path );
		// Files restored (and maybe to delete).
		$files          = array();
		// If the files must be deleted, we need to store the file sizes.
		$filesize_total = 0;

		if ( $sent ) {
			$files[] = $attachment_path;
		}

		if ( $this->delete_files ) {
			// This is used by AS3CF.
			$bytes = $this->filesystem->size( $attachment_path );

			if ( false !== $bytes ) {
				$filesize_total      += $bytes;
				$metadata['filesize'] = $bytes;
			} else {
				$metadata['filesize'] = 0;
			}
		}

		if ( ! empty( $metadata['sizes'] ) && ( $sent || $this->delete_files ) ) {
			foreach ( $metadata['sizes'] as $size_key => $size_data ) {
				$thumbnail_path = $this->get_thumbnail_path( $size_data['file'] );

				if ( $sent ) {
					$files[] = $thumbnail_path;
				}

				if ( $this->delete_files ) {
					// This is used by AS3CF.
					$bytes = $this->filesystem->size( $thumbnail_path );

					if ( false !== $bytes ) {
						$filesize_total += $bytes;
					}
				}
			}
		}

		if ( $this->delete_files && $filesize_total ) {
			// Add the total file size for all image sizes. This is a meta used by AS3CF.
			update_post_meta( $this->id, 'wpos3_filesize_total', $filesize_total );
		}

		/** This hook is documented in /inc/classes/class-imagify-attachment.php. */
		do_action( 'after_imagify_restore_attachment', $this->id );

		$to_delete = $this->delete_files ? $files : array();

		$this->cleanup( $metadata, $to_delete );

		return $files;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL UTILITIES ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Cleanup after optimization or a restore:
	 * - Maybe update metadata.
	 * - Maybe delete local files.
	 * - Delete the "optimization status" transient.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  array $new_metadata    New attachment metadata to be stored.
	 * @param  array $files_to_remove Files to delete.
	 */
	protected function cleanup( $new_metadata, $files_to_remove ) {
		if ( $new_metadata ) {
			/**
			 * Filter the metadata stored after optimization or a restore.
			 *
			 * @since  1.6.6
			 * @author Grégory Viguier
			 *
			 * @param array  $new_metadata New attachment metadata to be stored.
			 * @param int    $id           The attachment ID.
			 */
			$new_metadata = apply_filters( 'imagify_as3cf_attachment_cleanup_metadata', $new_metadata, $this->id );
			/**
			 * Update the attachment meta that contains the file sizes.
			 * Here we don't use wp_update_attachment_metadata() to prevent triggering unwanted hooks.
			 */
			update_post_meta( $this->id, '_wp_attachment_metadata', $new_metadata );
		}

		if ( $files_to_remove ) {
			$attachment_path = $this->get_original_path();
			/** This filter is documented in /amazon-s3-and-cloudfront/classes/amazon-s3-and-cloudfront.php. */
			$files_to_remove = (array) apply_filters( 'as3cf_upload_attachment_local_files_to_remove', $files_to_remove, $this->id, $attachment_path );
			$files_to_remove = array_filter( $files_to_remove );

			if ( $files_to_remove ) {
				$files_to_remove = array_unique( $files_to_remove );
				/**
				 * Delete the local files.
				 */
				array_map( array( $this, 'maybe_delete_file' ), $files_to_remove );
			}
		}

		/**
		 * Delete the "optimization status" transient.
		 */
		$this->delete_running_status();
	}

	/**
	 * Maybe resize (and backup) an image.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $attachment_path  The file path.
	 * @return bool                     True on success. False on failure.
	 */
	protected function maybe_resize( $attachment_path ) {
		if ( ! $this->is_image() ) {
			return false;
		}

		$do_resize       = get_imagify_option( 'resize_larger' );
		$resize_width    = get_imagify_option( 'resize_larger_w' );
		$attachment_size = $this->filesystem->get_image_size( $attachment_path );

		if ( ! $do_resize || ! $attachment_size || $resize_width >= $attachment_size['width'] ) {
			return false;
		}

		$resized_attachment_path = $this->resize( $attachment_path, $attachment_size, $resize_width );

		if ( is_wp_error( $resized_attachment_path ) ) {
			return false;
		}

		$backuped = imagify_backup_file( $attachment_path );

		if ( is_wp_error( $backuped ) ) {
			return false;
		}

		return $this->filesystem->move( $resized_attachment_path, $attachment_path, true );
	}

	/**
	 * Tell if the files must be deleted after being optimized or restored.
	 * It sets the 2 properties $this->use_s3_settings and $this->delete_files.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  array $metadata Attachment metadata. Provide, only if it comes from a 'wp_generate_attachment_metadata' or 'wp_update_attachment_metadata' hook.
	 * @return array           Attachment metadata. If not provided as argument, new values are fetched.
	 */
	protected function set_deletion_status( $metadata = false ) {
		global $as3cf;

		if ( $metadata ) {
			/**
			 * Metadata is provided: we were in a 'wp_generate_attachment_metadata' or 'wp_update_attachment_metadata' hook.
			 * This means we'll follow AS3CF settings to know if the local files must be sent to S3 and/or deleted.
			 */
			$this->use_s3_settings = true;
			$this->delete_files    = $as3cf && $as3cf->get_setting( 'remove-local-file' ) && $this->can_send_to_s3();

			return $metadata;
		}

		/**
		 * Metadata is not provided: we were not in a 'wp_generate_attachment_metadata' or 'wp_update_attachment_metadata' hook.
		 * So, we fetch the current meta value.
		 * This also means we won't follow AS3CF settings to know if the local files must be sent to S3 and/or deleted.
		 * In that case we'll send the files to S3 if they already are there, and delete them if they is a 'filesize' entry in the metadata.
		 */
		$metadata              = wp_get_attachment_metadata( $this->id, true );
		$this->use_s3_settings = false;
		$this->delete_files    = isset( $metadata['filesize'] ) && $this->can_send_to_s3();

		return $metadata;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** S3 UTILITIES ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if AS3CF is set up.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_s3_setup() {
		global $as3cf;
		static $is;

		if ( ! isset( $is ) ) {
			$is = $as3cf && $as3cf->is_plugin_setup();
		}

		return $is;
	}

	/**
	 * Tell if an attachment is stored on S3.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return array|bool The S3 info on success. False if the attachment is not on S3.
	 */
	public function get_s3_info() {
		global $as3cf;

		if ( ! $as3cf ) {
			return false;
		}

		if ( method_exists( $as3cf, 'get_attachment_s3_info' ) ) {
			return $as3cf->get_attachment_s3_info( $this->id );
		}

		return $as3cf->get_attachment_provider_info( $this->id );
	}

	/**
	 * Get a file from S3.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string|bool       The file path on success, false on failure.
	 */
	protected function get_file_from_s3( $file_path ) {
		global $as3cf;

		if ( ! $this->is_extension_supported() ) {
			return false;
		}

		if ( ! $this->is_s3_setup() ) {
			return false;
		}

		$s3_object = $this->get_s3_info();

		if ( ! $s3_object ) {
			// The attachment is not on S3.
			return false;
		}

		$directory        = $this->filesystem->dir_path( $s3_object['key'] );
		$directory        = $this->filesystem->is_root( $directory ) ? '' : $directory;
		$s3_object['key'] = $directory . $this->filesystem->file_name( $file_path );

		// Retrieve file from S3.
		if ( method_exists( $as3cf->plugin_compat, 'copy_s3_file_to_server' ) ) {
			$as3cf->plugin_compat->copy_s3_file_to_server( $s3_object, $file_path );
		} else {
			$as3cf->plugin_compat->copy_provider_file_to_server( $s3_object, $file_path );
		}

		return $this->filesystem->exists( $file_path ) ? $file_path : false;
	}

	/**
	 * Maybe send the attachment to S3.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  array  $metadata           The attachment metadata.
	 * @param  string $attachment_path    The attachment path.
	 * @param  bool   $remove_local_files True to let AS3CF delete the local files (if set in the settings). We usually don't want that, we do it by ourselves.
	 * @return bool                       True on success. False otherwize.
	 */
	protected function maybe_send_attachment_to_s3( $metadata = null, $attachment_path = null, $remove_local_files = false ) {
		global $as3cf;

		if ( ! $this->can_send_to_s3() ) {
			return false;
		}

		$s3_object = $this->get_s3_info();

		if ( ! $s3_object ) {
			return false;
		}

		$full_file_path = $this->get_original_path();

		if ( ! $full_file_path ) {
			// This is bad.
			return false;
		}

		if ( method_exists( $as3cf, 'upload_attachment_to_s3' ) ) {
			$s3_data = $as3cf->upload_attachment_to_s3( $this->id, $metadata, $attachment_path, false, $remove_local_files );
		} else {
			$s3_data = $as3cf->upload_attachment( $this->id, $metadata, $attachment_path, false, $remove_local_files );
		}

		return ! is_wp_error( $s3_data );
	}

	/**
	 * Tell if an attachment can be sent to S3.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function can_send_to_s3() {
		global $as3cf;
		static $can = array();
		static $copy_to_s3;

		if ( isset( $can[ $this->id ] ) ) {
			return $can[ $this->id ];
		}

		if ( ! isset( $copy_to_s3 ) ) {
			$copy_to_s3 = $as3cf && $as3cf->get_setting( 'copy-to-s3' );
		}

		$is_s3_setup      = $this->is_s3_setup();
		$s3_object        = $this->get_s3_info();
		// S3 is set up and the attachment is on S3.
		$can[ $this->id ] = $is_s3_setup && $s3_object;

		if ( $can[ $this->id ] && ! empty( $this->use_s3_settings ) ) {
			// Use AS3CF setting to tell if we're allowed to send the files.
			$can[ $this->id ] = $copy_to_s3;
		}

		/**
		 * Filter the result of Imagify_AS3CF_Attachment::can_send_to_s3().
		 *
		 * @since  1.6.6
		 * @author Grégory Viguier
		 *
		 * @param  bool  $can             True if the attachment can be sent. False otherwize.
		 * @param  int   $id              The attachment ID.
		 * @param  array $s3_object       The S3 infos.
		 * @param  bool  $is_s3_setup     AS3CF is set up or not.
		 * @param  bool  $copy_to_s3      AS3CF setting that tells if a "new" attachment can be sent.
		 * @param  bool  $use_s3_settings Tell if we must use AS3CF setting in this case.
		 */
		$can[ $this->id ] = (bool) apply_filters( 'imagify_can_send_to_s3', $can[ $this->id ], $this->id, $s3_object, $is_s3_setup, $copy_to_s3, $this->use_s3_settings );

		return $can[ $this->id ];
	}

	/**
	 * Maybe delete the local file.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return bool              True if deleted or doesn't exist. False on failure or if the file is not supposed to be deleted.
	 */
	protected function maybe_delete_file( $file_path ) {
		if ( ! $this->file_should_be_deleted( $file_path ) ) {
			return false;
		}

		if ( ! $this->filesystem->exists( $file_path ) ) {
			return true;
		}

		return $this->filesystem->delete( $file_path, false, 'f' );
	}

	/**
	 * Tell if a file should be deleted.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return bool              True to delete, false to keep.
	 */
	protected function file_should_be_deleted( $file_path ) {
		if ( ! $file_path || ! $this->delete_files ) {
			// We keep the file.
			return false;
		}

		/** This hook is documented in /amazon-s3-and-cloudfront/classes/amazon-s3-and-cloudfront.php. */
		$preserve = apply_filters( 'as3cf_preserve_file_from_local_removal', false, $file_path );

		return false === $preserve;
	}
}
