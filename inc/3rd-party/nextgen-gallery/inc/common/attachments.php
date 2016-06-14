<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Auto-optimize when a new attachment is generated
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'ngg_after_new_images_added', '_imagify_ngg_optimize_attachment', PHP_INT_MAX, 2 );
function _imagify_ngg_optimize_attachment( $galleryID, $image_ids ) {
	$api_key = get_imagify_option( 'api_key', false );

	if ( ! empty( $api_key ) && get_imagify_option( 'auto_optimize', false ) ) {
		foreach ( $image_ids as $id ) {
			$body                  = array();
			$body['metadata']	   = 1;
			$body['context']       = 'NGG';
			$body['attachment_id'] = $id;
			$body['action']        = 'imagify_async_optimize_upload_new_media';
			$body['_ajax_nonce']   = wp_create_nonce( 'new_media-' . $id );

			imagify_do_async_job( $body );
		}
	}
}

/**
 * Delete the Imagify data when an image is deleted.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'ngg_delete_picture', '_imagify_ngg_delete_picture' );
function _imagify_ngg_delete_picture( $image_id ) {
	Imagify_NGG_DB()->delete( $image_id );
}

/**
 * Import Imagify data from a WordPress image to a new NGG image
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_filter( 'ngg_medialibrary_imported_image', '_imagify_ngg_media_library_imported_image_data', 10, 2 );
function _imagify_ngg_media_library_imported_image_data( $image, $attachment ) {
	$attachment = new Imagify_Attachment( $attachment->ID );

	if ( $attachment->is_optimized() ) {
		$full_size = $attachment->get_size_data();
		$data 	   = array(
			'stats' => array(
				'original_size'  => $full_size['original_size'],
				'optimized_size' => $full_size['optimized_size'],
				'percent'        => $full_size['percent']
			),
			'sizes' => array( 'full' => $full_size )
		);

		Imagify_NGG_DB()->update(
			$image->pid,
			array(
				'pid'                => $image->pid,
				'optimization_level' => $attachment->get_optimization_level(),
				'status'             => $attachment->get_status(),
				'data'				 => maybe_serialize( $data )
			)
		);

		$image = new Imagify_NGG_Attachment( $image->pid );
		$image->optimize_thumbnails();
	}
}