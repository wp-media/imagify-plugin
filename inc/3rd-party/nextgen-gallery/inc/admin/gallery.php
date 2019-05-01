<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'ngg_manage_images_number_of_columns', '_imagify_ngg_manage_images_number_of_columns' );
/**
 * Add "Imagify" column in admin.php?page=nggallery-manage-gallery.
 *
 * @since  1.5
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
 * @since  1.5
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
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param  string $output The column content.
 * @param  object $image  An NGG Image object.
 * @return string
 */
function _imagify_ngg_manage_media_custom_column( $output, $image ) {
	$process = imagify_get_optimization_process( $image, 'ngg' );

	return get_imagify_media_column_content( $process );
}

add_filter( 'imagify_display_missing_thumbnails_link', '_imagify_ngg_hide_missing_thumbnails_link', 10, 3 );
/**
 * Hide the "Optimize missing thumbnails" link.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param  bool   $display    True to display the link. False to not display it.
 * @param  object $attachment The attachement object.
 * @param  string $context    The context.
 * @return bool
 */
function _imagify_ngg_hide_missing_thumbnails_link( $display, $attachment, $context ) {
	return 'ngg' === $context ? false : $display;
}
