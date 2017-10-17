<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'ngg_after_new_images_added', '_imagify_ngg_optimize_attachment', IMAGIFY_INT_MAX, 2 );
/**
 * Auto-optimize when a new attachment is generated.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param int   $gallery_id A Gallery ID.
 * @param array $image_ids  Id's which are sucessfully added.
 */
function _imagify_ngg_optimize_attachment( $gallery_id, $image_ids ) {

	if ( ! imagify_valid_key() || ! get_imagify_option( 'auto_optimize' ) ) {
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

add_action( 'ngg_delete_picture', '_imagify_ngg_delete_picture' );
/**
 * Delete the Imagify data when an image is deleted.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param int $image_id An image ID.
 */
function _imagify_ngg_delete_picture( $image_id ) {
	Imagify_NGG_DB::get_instance()->delete( $image_id );
}

add_filter( 'ngg_medialibrary_imported_image', '_imagify_ngg_media_library_imported_image_data', 10, 2 );
/**
 * Import Imagify data from a WordPress image to a new NGG image
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param  object $image      A NGG image object.
 * @param  object $attachment An attachment object.
 * @return object
 */
function _imagify_ngg_media_library_imported_image_data( $image, $attachment ) {
	$class_name = get_imagify_attachment_class_name( 'wp', $attachment->ID, 'ngg_medialibrary_imported_image' );
	$attachment = new $class_name( $attachment->ID );

	if ( ! $attachment->is_optimized() ) {
		return $image;
	}

	$full_size = $attachment->get_size_data();

	Imagify_NGG_DB::get_instance()->update( $image->pid, array(
		'pid'                => $image->pid,
		'optimization_level' => $attachment->get_optimization_level(),
		'status'             => $attachment->get_status(),
		'data'               => maybe_serialize( array(
			'stats' => array(
				'original_size'  => $full_size['original_size'],
				'optimized_size' => $full_size['optimized_size'],
				'percent'        => $full_size['percent'],
			),
			'sizes' => array( 'full' => $full_size ),
		) ),
	) );

	$imagify_image = new Imagify_NGG_Attachment( $image->pid );
	$imagify_image->optimize_thumbnails();

	return $image;
}
