<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_action( 'attachment_submitbox_misc_actions', '_imagify_attachment_submitbox_misc_actions', PHP_INT_MAX );
/**
 * Add a "Optimize It" button or the Imagify optimization data in the attachment submit area.
 *
 * @since 1.0
 */
function _imagify_attachment_submitbox_misc_actions() {
	global $post;

	if ( ! current_user_can( imagify_get_capacity( true ) ) ) {
		return;
	}

	if ( ! imagify_is_attachment_mime_type_supported( $post->ID ) ) {
		return;
	}

	$class_name = get_imagify_attachment_class_name( 'wp', $post->ID, 'attachment_submitbox_misc_actions' );
	$attachment = new $class_name( $post->ID );

	if ( ! imagify_valid_key() && ! $attachment->is_optimized() ) {

		echo '<div class="misc-pub-section misc-pub-imagify"><h4>' . __( 'Imagify', 'imagify' ) . '</h4></div>';
		echo '<div class="misc-pub-section misc-pub-imagify">';
			echo __( 'Invalid API key', 'imagify' );
			echo '<br/>';
			echo '<a href="' . esc_url( get_imagify_admin_url( 'options-general' ) ) . '">' . __( 'Check your Settings', 'imagify' ) . '</a>';
		echo '</div>';

	} elseif ( $attachment->is_optimized() || $attachment->has_error() ) {

		echo '<div class="misc-pub-section misc-pub-imagify"><h4>' . __( 'Imagify', 'imagify' ) . '</h4></div>';
		echo get_imagify_attachment_optimization_text( $attachment );

	} elseif ( false !== get_transient( 'imagify-async-in-progress-' . $post->ID ) ) {

		echo '<div class="misc-pub-section misc-pub-imagify">';
			echo '<div class="button">';
				echo '<span class="imagify-spinner"></span>';
				_e( 'Optimizing...', 'imagify' );
			echo '</div>';
		echo '</div>';

	} else {

		$url = get_imagify_admin_url( 'manual-upload', array( 'attachment_id' => $post->ID ) );
		printf( '<div class="misc-pub-section misc-pub-imagify"><a class="button-primary" href="%s">%s</a></div>', esc_url( $url ), __( 'Optimize', 'imagify' ) );
	}

	if ( $attachment->is_optimized() ) {
		echo '<input id="imagify-full-original" type="hidden" value="' . esc_url( $attachment->get_backup_url() ) . '">';
		echo '<input id="imagify-full-original-size" type="hidden" value="' . esc_attr( $attachment->get_original_size() ) . '">';
	}
}
