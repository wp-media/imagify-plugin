<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_filter( 'attachment_fields_to_edit', '_imagify_attachment_fields_to_edit', IMAGIFY_INT_MAX, 2 );
/**
 * Add "Imagify" column in the Media Uploader
 *
 * @since 1.2
 * @author Jonathan Buttigieg
 *
 * @param  array  $form_fields An array of attachment form fields.
 * @param  object $post        The WP_Post attachment object.
 * @return array
 */
function _imagify_attachment_fields_to_edit( $form_fields, $post ) {
	global $pagenow;

	if ( 'post.php' === $pagenow ) {
		return $form_fields;
	}

	if ( ! imagify_get_context( 'wp' )->current_user_can( 'manual-optimize', $post->ID ) ) {
		return $form_fields;
	}

	$process = imagify_get_optimization_process( $post->ID, 'wp' );

	$form_fields['imagify'] = array(
		'label'         => 'Imagify',
		'input'         => 'html',
		'html'          => get_imagify_media_column_content( $process ),
		'show_in_edit'  => true,
		'show_in_modal' => true,
	);

	return $form_fields;
}

add_filter( 'media_row_actions', '_imagify_add_actions_to_media_list_row', IMAGIFY_INT_MAX, 2 );
/**
 * Add "Compare Original VS Optimized" link to the media row action
 *
 * @since  1.4.3
 * @author Geoffrey Crofte
 * @param  array  $actions An array of action links for each attachment.
 *                         Default 'Edit', 'Delete Permanently', 'View'.
 * @param  object $post    WP_Post object for the current attachment.
 * @return array
 */
function _imagify_add_actions_to_media_list_row( $actions, $post ) {
	if ( ! imagify_get_context( 'wp' )->current_user_can( 'manual-optimize', $post->ID ) ) {
		return $actions;
	}

	$process = imagify_get_optimization_process( $post->ID, 'wp' );

	if ( ! $process->is_valid() ) {
		return $actions;
	}

	$media = $process->get_media();

	// If this media is not an image, do nothing.
	if ( ! $media->is_supported() || ! $media->is_image() ) {
		return $actions;
	}

	$data = $process->get_data();

	// If Imagify license not valid, or image is not optimized, do nothing.
	if ( ! Imagify_Requirements::is_api_key_valid() || ! $data->is_optimized() ) {
		return $actions;
	}

	// If no backup, do nothing.
	if ( ! $media->has_backup() ) {
		return $actions;
	}

	$dimensions = $media->get_dimensions();

	// If full image is too small. See get_imagify_localize_script_translations().
	if ( $dimensions['width'] < 360 ) {
		return $actions;
	}

	// Else, add action link for comparison (JS triggered).
	$actions['imagify-compare'] = Imagify_Views::get_instance()->get_template( 'button/compare-images', [
		'url'          => get_edit_post_link( $media->get_id() ) . '#imagify-compare',
		'backup_url'   => $media->get_backup_url(),
		'original_url' => $media->get_original_url(),
		'media_id'     => $media->get_id(),
		'width'        => $dimensions['width'],
		'height'       => $dimensions['height'],
	] );

	return $actions;
}
