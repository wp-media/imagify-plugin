<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'ngg_manage_images_number_of_columns', '_imagify_ngg_manage_images_number_of_columns' );
/**
 * Add "Imagify" column in admin.php?page=nggallery-manage-gallery.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @param  int $count Number of columns.
 * @return int Incremented number of columns.
 */
function _imagify_ngg_manage_images_number_of_columns( $count ) {
	$count++;
	add_filter( 'ngg_manage_images_column_' . $count . '_header', '_imagify_ngg_manage_media_columns' );
	add_filter( 'ngg_manage_images_column_' . $count . '_content', '_imagify_ngg_manage_media_custom_column', 10, 2 );

	return $count;
}

/**
 * Get the column title.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @return string
 */
function _imagify_ngg_manage_media_columns() {
	return 'Imagify';
}

/**
 * Get the column content.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 *
 * @param  string $output The column content.
 * @param  object $image  An NGG Image object.
 * @return string
 */
function _imagify_ngg_manage_media_custom_column( $output, $image ) {
	$attachment = new Imagify_NGG_Attachment( $image );
	return get_imagify_media_column_content( $attachment, 'NGG' );
}
