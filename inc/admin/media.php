<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'attachment_fields_to_edit', '_imagify_attachment_fields_to_edit', PHP_INT_MAX, 2 );
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

	$class_name = get_imagify_attachment_class_name( 'wp', $post->ID, 'attachment_fields_to_edit' );
	$attachment = new $class_name( $post->ID );

	$form_fields['imagify'] = array(
		'label'         => 'Imagify',
		'input'         => 'html',
		'html'          => get_imagify_media_column_content( $attachment ),
		'show_in_edit'  => true,
		'show_in_modal' => true,
	);

	return $form_fields;
}

add_filter( 'media_row_actions', '_imagify_add_actions_to_media_list_row', PHP_INT_MAX, 2 );
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
	// If this attachment is not an image, do nothing.
	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $actions;
	}

	$class_name = get_imagify_attachment_class_name( 'wp', $post->ID, 'media_row_actions' );
	$attachment = new $class_name( $post->ID );

	// If Imagify license not valid, or image is not optimized, do nothing.
	if ( ! imagify_valid_key() || ! $attachment->is_optimized() ) {
		return $actions;
	}

	// If was not activated for that image, do nothing.
	if ( ! $attachment->get_backup_url() ) {
		return $actions;
	}

	$image = wp_get_attachment_image_src( $post->ID, 'full' );

	// If full image is too small.
	if ( ! $image || (int) $image[1] < 360 ) {
		return $actions;
	}

	// Else, add action link for comparison (JS triggered).
	$actions['imagify-compare'] = sprintf(
		'<a href="%1$s#imagify-compare" data-id="%2$d" data-backup-src="%3$s" data-full-src="%4$s" data-full-width="%5$d" data-full-height="%6$d" data-target="#imagify-comparison-%2$d" class="imagify-compare-images imagify-modal-trigger">%7$s</a>',
		esc_url( get_edit_post_link( $post->ID ) ),
		$post->ID,
		esc_url( $attachment->get_backup_url() ),
		esc_url( $image[0] ),
		$image[1],
		$image[2],
		esc_html__( 'Compare Original VS Optimized', 'imagify' )
	);

	return $actions;
}
