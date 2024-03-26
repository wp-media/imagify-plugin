<?php
use \Imagify\Optimization\File;
use \Imagify\ThirdParty\NGG;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'ngg_after_new_images_added', '_imagify_ngg_optimize_attachment', IMAGIFY_INT_MAX, 2 );
/**
 * Auto-optimize when a new attachment is added to the database (NGG plugin's table), except for images imported from the library.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param int   $gallery_id A Gallery ID.
 * @param array $image_ids  An array of Ids or objects. Ids which are sucessfully added.
 */
function _imagify_ngg_optimize_attachment( $gallery_id, $image_ids ) {

	if ( ! Imagify_Requirements::is_api_key_valid() || ! get_imagify_option( 'auto_optimize' ) ) {
		return;
	}

	$is_maybe_library_import = ! empty( $_POST['action'] ) && 'import_media_library' === $_POST['action'] && ! empty( $_POST['attachment_ids'] ) && is_array( $_POST['attachment_ids'] ); // WPCS: CSRF ok.

	if ( $is_maybe_library_import && ! empty( $_POST['nextgen_upload_image_sec'] ) ) { // WPCS: CSRF ok.
		/**
		 * The images are imported from the library.
		 * In this case, those images are dealt with in _imagify_ngg_media_library_imported_image_data().
		 */
		return;
	}

	if ( $is_maybe_library_import && ( ! empty( $_POST['gallery_id'] ) || ! empty( $_POST['gallery_name'] ) ) ) { // WPCS: CSRF ok.
		/**
		 * Same thing but for NGG 2.0 probably.
		 */
		return;
	}

	foreach ( $image_ids as $image ) {
		if ( is_numeric( $image ) ) {
			$image_id = (int) $image;
		} elseif ( is_object( $image ) && ! empty( $image->pid ) ) {
			$image_id = (int) $image->pid;
		} else {
			$image_id = 0;
		}

		if ( ! $image_id ) {
			continue;
		}

		/**
		 * Allow to prevent automatic optimization for a specific NGG gallery image.
		 *
		 * @since  1.6.12
		 * @author Grégory Viguier
		 *
		 * @param bool $optimize   True to optimize, false otherwise.
		 * @param int  $image_id   Image ID.
		 * @param int  $gallery_id Gallery ID.
		 */
		$optimize = apply_filters( 'imagify_auto_optimize_ngg_gallery_image', true, $image_id, $gallery_id );

		if ( ! $optimize ) {
			continue;
		}

		$process = imagify_get_optimization_process( $image, 'ngg' );

		if ( ! $process->is_valid() ) {
			continue;
		}

		if ( $process->get_data()->get_optimization_status() ) {
			// Optimization already attempted.
			continue;
		}

		$process->optimize();
	}
}

add_filter( 'ngg_medialibrary_imported_image', '_imagify_ngg_media_library_imported_image_data', 10, 2 );
/**
 * Import Imagify data from a WordPress image to a new NGG image, and optimize the thumbnails.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param  object $image      A NGG image object.
 * @param  object $attachment An attachment object.
 * @return object
 */
function _imagify_ngg_media_library_imported_image_data( $image, $attachment ) {
	$wp_process = imagify_get_optimization_process( $attachment->ID, 'wp' );

	if ( ! $wp_process->is_valid() || ! $wp_process->get_media()->is_supported() ) {
		return $image;
	}

	$wp_data = $wp_process->get_data();

	if ( ! $wp_data->is_optimized() ) {
		// The main image is not optimized.
		return $image;
	}

	// Copy the full size data.
	$wp_full_size_data  = $wp_data->get_size_data();
	$optimization_level = $wp_data->get_optimization_level();

	NGG\DB::get_instance()->update( $image->pid, [
		'pid'                => $image->pid,
		'optimization_level' => $optimization_level,
		'status'             => $wp_data->get_optimization_status(),
		'data'               => [
			'sizes' => [
				'full' => $wp_full_size_data,
			],
			'stats' => [
				'original_size'  => $wp_full_size_data['original_size'],
				'optimized_size' => $wp_full_size_data['optimized_size'],
				'percent'        => $wp_full_size_data['percent'],
			],
		],
	] );

	$ngg_process = imagify_get_optimization_process( $image->pid, 'ngg' );

	if ( ! $ngg_process->is_valid() ) {
		// WTF.
		return $image;
	}

	// Copy the backup file (we don't want to backup the optimized file if it can be avoided).
	$ngg_media      = $ngg_process->get_media();
	$wp_media       = $wp_process->get_media();
	$wp_backup_path = $wp_media->get_backup_path();
	$filesystem     = imagify_get_filesystem();
	$backup_copied  = false;

	if ( $wp_backup_path ) {
		$ngg_backup_path = $ngg_media->get_raw_backup_path();
		$backup_copied   = $filesystem->copy( $wp_backup_path, $ngg_backup_path, true );

		if ( $backup_copied ) {
			$filesystem->chmod_file( $ngg_backup_path );
		}
	}

	/**
	 * Next-gen for the full size.
	 * Look for an existing copy locally:
	 * - if it exists, copy it (and its optimization data),
	 * - if not, add it to the optimization queue.
	 */
	$add_full_nextgen = $wp_media->is_image();

	if ( $add_full_nextgen ) {
		$formats = [
			'avif' => $wp_process::AVIF_SUFFIX,
			'webp' => $wp_process::WEBP_SUFFIX,
		];

		foreach ( $formats as $extension => $suffix ) {
			$wp_full_path_nextgen = false;
			$nextgen_size_name    = 'full' . $suffix;
			$wp_nextgen_data      = $wp_data->get_size_data( $nextgen_size_name );

			// Get the path to the next-gen image if it exists.
			$wp_full_path_nextgen = $wp_process->get_fullsize_file()->get_path_to_nextgen( $extension );

			if ( $wp_full_path_nextgen && ! $filesystem->exists( $wp_full_path_nextgen ) ) {
				$wp_full_path_nextgen = false;
			}

			if ( $wp_full_path_nextgen ) {
				// We know we have a next-gen version. Make sure we have the right data.
				$wp_nextgen_data['success'] = true;

				if ( empty( $wp_nextgen_data['original_size'] ) ) {
					// The next-gen data is missing.
					$full_size_weight = $wp_full_size_data['original_size'];

					if ( ! $full_size_weight && $wp_backup_path ) {
						// For some reason we don't have the original file weight, but we can get it from the backup file.
						$full_size_weight = $filesystem->size( $wp_backup_path );

						if ( $full_size_weight ) {
							$wp_nextgen_data['original_size'] = $full_size_weight;
						}
					}
				}

				if ( ! empty( $wp_nextgen_data['original_size'] ) && empty( $wp_nextgen_data['optimized_size'] ) ) {
					// The next-gen file size.
					$wp_nextgen_data['optimized_size'] = $filesystem->size( $wp_full_path_nextgen );
				}

				if ( empty( $wp_nextgen_data['original_size'] ) || empty( $wp_nextgen_data['optimized_size'] ) ) {
					// We must have both original and optimized sizes.
					$wp_nextgen_data = [];
				}
			}

			if ( $wp_full_path_nextgen && $wp_nextgen_data ) {
				// We have the file and the data.
				// Copy the file.
				$ngg_full_file      = new File( $ngg_media->get_raw_fullsize_path() );
				$ngg_full_path_nextgen = $ngg_full_file->get_path_to_nextgen( $extension ); // Destination.

				if ( $ngg_full_path_nextgen ) {
					$copied = $filesystem->copy( $wp_full_path_nextgen, $ngg_full_path_nextgen, true );

					if ( $copied ) {
						// Success.
						$filesystem->chmod_file( $ngg_full_path_nextgen );
						$add_full_nextgen = false;
					}
				}

				if ( ! $add_full_nextgen ) {
					// The next-gen file has been successfully copied: now, copy the data.
					$ngg_process->get_data()->update_size_optimization_data( $nextgen_size_name, $wp_nextgen_data );
				}
			}
		}
	}

	// Optimize thumbnails.
	$sizes = $ngg_media->get_media_files();
	unset( $sizes['full'] );

	if ( $add_full_nextgen ) {
		// We could not use a local next-gen copy: ask for a new one.

		$formats = imagify_nextgen_images_formats();

		foreach ( $formats as $format ) {
			if ( 'webp' === $format ) {
				$suffix = $wp_process::WEBP_SUFFIX;
			} elseif ( 'avif' === $format ) {
				$suffix = $wp_process::AVIF_SUFFIX;
			}

			$sizes[ 'full' . $suffix ] = [];
		}
	}

	if ( ! $sizes ) {
		return $image;
	}

	$args = [
		'hook_suffix' => 'optimize_imported_images',
	];

	$ngg_process->optimize_sizes( array_keys( $sizes ), $optimization_level, $args );

	return $image;
}

add_action( 'ngg_generated_image', 'imagify_ngg_maybe_add_dynamic_thumbnail_to_background_process', IMAGIFY_INT_MAX, 2 );
/**
 * Add a dynamically generated thumbnail to the background process queue.
 * Note that this won’t work when images are imported (from WP Library or uploaded), since they are already being processed, and locked.
 *
 * @since  1.8
 * @since  1.9 Doesn't use the class Imagify_NGG_Dynamic_Thumbnails_Background_Process anymore.
 * @author Grégory Viguier
 *
 * @param object $image A NGG image object.
 * @param string $size  The thumbnail size name.
 */
function imagify_ngg_maybe_add_dynamic_thumbnail_to_background_process( $image, $size ) {
	NGG\DynamicThumbnails::get_instance()->push_to_queue( $image, $size );
}

add_action( 'ngg_delete_picture', 'imagify_ngg_cleanup_after_media_deletion', 10, 2 );
/**
 * Delete everything when a NGG image is deleted.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param int    $image_id The image ID.
 * @param object $image    A NGG object.
 */
function imagify_ngg_cleanup_after_media_deletion( $image_id, $image ) {
	$process = imagify_get_optimization_process( $image, 'ngg' );

	if ( ! $process->is_valid() ) {
		return;
	}

	// Trigger a common hook.
	imagify_trigger_delete_media_hook( $process );

	/**
	 * The backup file has already been deleted by NGG.
	 * Delete the next-gen versions and the optimization data.
	 */
	$process->delete_nextgen_files();

	$process->get_data()->delete_optimization_data();
}

add_filter( 'imagify_crop_thumbnail', 'imagify_ngg_should_crop_thumbnail', 10, 4 );
/**
 * In case of a dynamic thumbnail, tell if the image must be croped or resized.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @param  bool           $crop      True to crop the thumbnail, false to resize. Null by default.
 * @param  string         $size      Name of the thumbnail size.
 * @param  array          $size_data Data of the thumbnail being processed. Contains at least 'width', 'height', and 'path'.
 * @param  MediaInterface $media     The MediaInterface instance corresponding to the image being processed.
 * @return bool
 */
function imagify_ngg_should_crop_thumbnail( $crop, $size, $size_data, $media ) {
	static $data_per_media    = [];
	static $storage_per_media = [];

	if ( 'ngg' !== $media->get_context() ) {
		return $crop;
	}

	$media_id = $media->get_id();

	if ( ! isset( $data_per_media[ $media_id ] ) ) {
		$image = \nggdb::find_image( $media_id );

		if ( ! empty( $image->_ngiw ) ) {
			$storage_per_media[ $media_id ] = $image->_ngiw->get_storage()->object;
		} else {
			$storage_per_media[ $media_id ] = \C_Gallery_Storage::get_instance()->object;
		}

		$data_per_media[ $media_id ] = $storage_per_media[ $media_id ]->_image_mapper->find( $media_id ); // stdClass Object.
	}

	$params = $storage_per_media[ $media_id ]->get_image_size_params( $data_per_media[ $media_id ], $size );

	return ! empty( $params['crop'] );
}
