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
 * @param array $image_ids  Id's which are sucessfully added.
 */
function _imagify_ngg_optimize_attachment( $gallery_id, $image_ids ) {

	if ( ! Imagify_Requirements::is_api_key_valid() || ! get_imagify_option( 'auto_optimize' ) ) {
		return;
	}

	if ( ! empty( $_POST['nextgen_upload_image_sec'] ) && ! empty( $_POST['action'] ) && 'import_media_library' === $_POST['action'] && ! empty( $_POST['attachment_ids'] ) && is_array( $_POST['attachment_ids'] ) ) { // WPCS: CSRF ok.
		/**
		 * The images are imported from the library.
		 * In this case, those images are dealt with in _imagify_ngg_media_library_imported_image_data().
		 */
		return;
	}

	foreach ( $image_ids as $image_id ) {
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

		$process = imagify_get_optimization_process( $image_id, 'ngg' );

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

	// Webp for the full size.
	$add_full_webp = $wp_media->is_image() && ! $wp_process->get_file()->is_webp() && get_imagify_option( 'convert_to_webp' );

	if ( $add_full_webp ) {
		$wp_full_path_webp = false;
		$webp_size_name    = 'full' . constant( get_class( $wp_process ) . '::WEBP_SUFFIX' );
		$wp_webp_data      = $wp_data->get_size_data( $webp_size_name );

		// Get the path to the webp image if it exists.
		$wp_full_path_webp = $wp_process->get_file()->get_path_to_webp();

		if ( $wp_full_path_webp && ! $filesystem->exists( $wp_full_path_webp ) ) {
			$wp_full_path_webp = false;
		}

		if ( $wp_full_path_webp ) {
			// We know we have a webp version. Make sure we have the right data.
			$wp_webp_data['success'] = true;

			if ( empty( $wp_webp_data['original_size'] ) ) {
				// The webp data is missing.
				$full_size_weight = $wp_full_size_data['original_size'];

				if ( ! $full_size_weight && $wp_backup_path ) {
					// For some reason we don't have the original file weight, but we can get it from the backup file.
					$full_size_weight = $filesystem->size( $wp_backup_path );

					if ( $full_size_weight ) {
						$wp_webp_data['original_size'] = $full_size_weight;
					}
				}
			}

			if ( ! empty( $wp_webp_data['original_size'] ) && empty( $wp_webp_data['optimized_size'] ) ) {
				// The webp file size.
				$wp_webp_data['optimized_size'] = $filesystem->size( $wp_full_path_webp );
			}

			if ( empty( $wp_webp_data['original_size'] ) || empty( $wp_webp_data['optimized_size'] ) ) {
				// We must have both original and optimized sizes.
				$wp_webp_data = [];
			}
		}

		if ( $wp_full_path_webp && $wp_webp_data ) {
			// We have the file and the data.
			// Copy the file.
			$ngg_full_file      = new File( $ngg_media->get_raw_original_path() );
			$ngg_full_path_webp = $ngg_full_file->get_path_to_webp(); // Destination.

			if ( $ngg_full_path_webp ) {
				$copied = $filesystem->copy( $wp_full_path_webp, $ngg_full_path_webp, true );

				if ( $copied ) {
					// Success.
					$filesystem->chmod_file( $ngg_full_path_webp );
					$add_full_webp = false;
				}
			}

			if ( ! $add_full_webp ) {
				// The webp file has been successfully copied: now, copy the data.
				$ngg_process->get_data()->update_size_optimization_data( $webp_size_name, $wp_webp_data );
			}
		}
	}

	// Optimize thumbnails.
	$sizes = $ngg_media->get_media_files();
	unset( $sizes['full'] );

	if ( $add_full_webp ) {
		$sizes[ $webp_size_name ] = [];
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

	/**
	 * The backup file has already been deleted by NGG.
	 * Delete the webp versions and the optimization data.
	 */
	$process->delete_webp_files();
	$process->get_data()->delete_optimization_data();
}
