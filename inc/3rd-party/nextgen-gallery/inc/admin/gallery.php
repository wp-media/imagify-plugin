<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Add "Imagify" column in admin.php?page=nggallery-manage-gallery
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_filter( 'ngg_manage_images_number_of_columns', '_imagify_ngg_manage_images_number_of_columns' );
function _imagify_ngg_manage_images_number_of_columns( $count ) {
	$count++;
	add_filter( 'ngg_manage_images_column_' . $count . '_header', '_imagify_ngg_manage_media_columns' );
	add_filter( 'ngg_manage_images_column_' . $count . '_content', '_imagify_ngg_manage_media_custom_column', 10, 2 );

	return $count;
}

function _imagify_ngg_manage_media_columns() {
	return 'Imagify';
}

function _imagify_ngg_manage_media_custom_column( $output, $image ) {
	$attachment = new Imagify_NGG_Attachment( $image );
	echo get_imagify_media_column_content( $attachment, 'NGG' );
}