<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/*
 * Add "Imagify" column in the Media Uploader
 *
 * @since 1.2
 * @author Jonathan Buttigieg
 */
add_filter( 'attachment_fields_to_edit', '_imagify_attachment_fields_to_edit', PHP_INT_MAX, 2 );
function _imagify_attachment_fields_to_edit( $form_fields, $post ) {
	global $pagenow;
	if ( 'post.php' == $pagenow ) {
		return $form_fields;
	}
	
	$attachment = new Imagify_Attachment( $post->ID );

	$form_fields['imagify'] = array(
		'label'         => 'Imagify',
		'input'         => 'html',
		'html'          => get_imagify_media_column_content( $attachment ),
		'show_in_edit'  => true,
		'show_in_modal' => true,
	);

	return $form_fields;
}

/**
 * Add "Compare Original VS Optimized" link to the media row action
 *
 * @since  1.4.3
 * @author Geoffrey Crofte
 */
add_filter( 'media_row_actions', '_imagify_add_actions_to_media_list_row', PHP_INT_MAX, 2 );
function _imagify_add_actions_to_media_list_row( $actions, $post ) {
	// if this attachment is not an image, do nothing
	if ( ! wp_attachment_is_image( $post->ID ) ) {
		return $actions;
	}

	$attachment = new Imagify_Attachment();

	// if Imagify license not valid, or image is not optimized, do nothing
	if ( ! imagify_valid_key() || ! $attachment->is_optimized() ) {
		return $actions;
	}

	// if was not activated for that image, do nothing
	if ( '' === $attachment->get_backup_url() ) {
		return $actions;
	}

	$image = wp_get_attachment_image_src( $post->ID, 'full' );

	// if full image is too small
	if ( (int) $image[1] < 360 ) {
		return $actions;
	}

	// else, add action link for comparison (JS triggered)
	$actions['imagify-compare'] = '<a href="' . get_edit_post_link( $post->ID ) . '#imagify-compare" data-id="' . $post->ID . '" data-backup-src="' . $attachment->get_backup_url() . '" data-full-src="' . $image[0] . '" data-full-width="' . $image[1] . '" data-full-height="' . $image[2] . '" data-target="#imagify-comparison-' . $post->ID . '" class="imagify-compare-images imagify-modal-trigger">' . esc_html__('Compare Original VS Optimized', 'imagify') . '</a>';

	return $actions;
}