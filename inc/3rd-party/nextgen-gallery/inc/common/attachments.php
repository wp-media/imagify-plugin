<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

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