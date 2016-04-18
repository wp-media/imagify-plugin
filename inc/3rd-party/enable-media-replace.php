<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

if ( function_exists( 'emr_delete_current_files' ) ) :

/**
 * Re-Optimize an attachment after replace it with Enable Media Replace.
 *
 * @since 1.0
*/
add_action( 'enable-media-replace-upload-done', '_imagify_optimize_enable_media_replace' );
function _imagify_optimize_enable_media_replace( $guid ) {	
	global $wpdb;
	$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $guid ) );
	
	// Stop if the attachment wasn't optimized yet by Imagify
	if ( ! get_post_meta( $attachment_id, '_imagify_data', true ) ) {
		return;
	}
	
	$optimization_level = get_post_meta( $attachment_id, '_imagify_optimization_level', true );
	
	// Remove old optimization data
	delete_post_meta( $attachment_id, '_imagify_data' );
	delete_post_meta( $attachment_id, '_imagify_status' );
	delete_post_meta( $attachment_id, '_imagify_optimization_level' );
	
	// Optimize it!!!!!
	$attachment = new Imagify_Attachment( $attachment_id );
	$attachment->optimize( $optimization_level );
}

endif;