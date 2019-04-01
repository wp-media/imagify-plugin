<?php
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

		imagify_do_async_job( array(
			'action'        => 'imagify_async_optimize_upload_new_media',
			'_ajax_nonce'   => wp_create_nonce( 'new_media-' . $image_id ),
			'metadata'      => 1,
			'context'       => 'NGG',
			'attachment_id' => $image_id,
		) );
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
	$attachment = get_imagify_attachment( 'wp', $attachment->ID, 'ngg_medialibrary_imported_image' );

	if ( ! $attachment->is_optimized() ) {
		// The image is not optimized.
		return $image;
	}

	// Copy the attachment data.
	$full_size = $attachment->get_size_data();

	Imagify_NGG_DB::get_instance()->update( $image->pid, array(
		'pid'                => $image->pid,
		'optimization_level' => $attachment->get_optimization_level(),
		'status'             => $attachment->get_status(),
		'data'               => array(
			'sizes' => array(
				'full' => $full_size,
			),
			'stats' => array(
				'original_size'  => $full_size['original_size'],
				'optimized_size' => $full_size['optimized_size'],
				'percent'        => $full_size['percent'],
			),
		),
	) );

	$imagify_image = new Imagify_NGG_Attachment( $image->pid );

	// Copy the backup file (we don't want to backup the optimized file).
	$attachment_backup_path = $attachment->get_backup_path();

	if ( $attachment_backup_path ) {
		$ngg_backup_path = $imagify_image->get_raw_backup_path();

		imagify_get_filesystem()->copy( $attachment_backup_path, $ngg_backup_path, true );
		imagify_get_filesystem()->chmod_file( $ngg_backup_path );
	}

	// Optimize thumbnails.
	$imagify_image->optimize_thumbnails();

	return $image;
}

add_action( 'ngg_generated_image', 'imagify_ngg_maybe_add_dynamic_thumbnail_to_background_process', IMAGIFY_INT_MAX, 2 );
/**
 * Add a dynamically generated thumbnail to the background process queue.
 *
 * @since  1.8
 * @author Grégory Viguier
 *
 * @param object $image A NGG image object.
 * @param string $size  The thumbnail size name.
 */
function imagify_ngg_maybe_add_dynamic_thumbnail_to_background_process( $image, $size ) {
	static $done = false;

	$attachment = get_imagify_attachment( 'NGG', $image, 'ngg_maybe_add_dynamic_thumbnail_to_background_process' );

	if ( ! $attachment->is_optimized() ) {
		// The main image is not optimized.
		return;
	}

	$data = $attachment->get_data();

	if ( isset( $data['sizes'][ $size ]['success'] ) ) {
		// This thumbnail has already been processed.
		return;
	}

	$process = Imagify_NGG_Dynamic_Thumbnails_Background_Process::get_instance();
	$data    = array(
		'id'   => $attachment->get_id(),
		'size' => $size,
	);

	if ( $process->is_in_queue( $data ) ) {
		// Duplicate.
		return;
	}

	// Do the optimization asynchroniously.
	$process->push_to_queue( $data );

	if ( ! $done ) {
		$done = true;
		add_action( 'shutdown', 'imagify_ngg_dispatch_dynamic_thumbnail_background_process' );
	}
}

/**
 * Dispatch the optimization process.
 *
 * @since  1.8
 * @author Grégory Viguier
 */
function imagify_ngg_dispatch_dynamic_thumbnail_background_process() {
	Imagify_NGG_Dynamic_Thumbnails_Background_Process::get_instance()->save()->dispatch();
}

/**
 * Delete the Imagify data when an image is deleted.
 *
 * @since 1.5
 */
add_action( 'ngg_delete_picture', array( Imagify_NGG_DB::get_instance(), 'delete' ) );
