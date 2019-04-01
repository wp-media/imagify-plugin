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

	if ( ! imagify_current_user_can( 'manual-optimize', $post->ID ) ) {
		return $form_fields;
	}

	$attachment = get_imagify_attachment( 'wp', $post->ID, 'attachment_fields_to_edit' );

	$form_fields['imagify'] = array(
		'label'         => 'Imagify',
		'input'         => 'html',
		'html'          => get_imagify_media_column_content( $attachment ),
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
	if ( ! imagify_current_user_can( 'manual-optimize', $post->ID ) ) {
		return $actions;
	}

	$attachment = get_imagify_attachment( 'wp', $post->ID, 'media_row_actions' );

	// If this attachment is not an image, do nothing.
	if ( ! $attachment->is_extension_supported() || ! $attachment->is_image() ) {
		return $actions;
	}

	// If Imagify license not valid, or image is not optimized, do nothing.
	if ( ! Imagify_Requirements::is_api_key_valid() || ! $attachment->is_optimized() ) {
		return $actions;
	}

	// If was not activated for that image, do nothing.
	if ( ! $attachment->get_backup_url() ) {
		return $actions;
	}

	$image = wp_get_attachment_image_src( $post->ID, 'full' );

	// If full image is too small. See get_imagify_localize_script_translations().
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
