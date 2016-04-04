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
			$attachment = new Imagify_NGG_Attachment( $id );		
		
			// Optimize it!!!!!
			$attachment->optimize( null );	
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