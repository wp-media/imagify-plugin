<?php 
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Process all thumbnails of a specific image with Imagify with the manual method.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'wp_ajax_imagify_ngg_manual_upload'		, '_do_admin_post_imagify_ngg_manual_upload' );
add_action( 'admin_post_imagify_ngg_manual_upload'	, '_do_admin_post_imagify_ngg_manual_upload' );
function _do_admin_post_imagify_ngg_manual_upload() {
	if ( defined( 'DOING_AJAX' ) ) {
		check_ajax_referer( 'imagify-manual-upload' );
	} else {
		check_admin_referer( 'imagify-manual-upload' );
	}
	
	if ( ! isset( $_GET['attachment_id'] ) || ! current_user_can( 'upload_files' ) ) {
		if ( defined( 'DOING_AJAX' ) ) {
			wp_send_json_error();
		} else {
			wp_nonce_ays( '' );
		}
	}
	
	$attachment_id = $_GET['attachment_id'];
	
	set_transient( 'imagify-async-ngg-in-progress-' . $attachment_id, true, 10 * MINUTE_IN_SECONDS );
	
	$attachment = new Imagify_NGG_Attachment( $attachment_id );
	
	// Optimize it!!!!!
	$attachment->optimize();
	
	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}
	
	// Return the optimization statistics
	$output = get_imagify_attachment_optimization_text( $attachment, 'ngg' );
	wp_send_json_success( $output );
}

/**
 * Process a manual upload by overriding the optimization level.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'wp_ajax_imagify_ngg_manual_override_upload', '_do_admin_post_imagify_ngg_manual_override_upload' );
add_action( 'admin_post_imagify_ngg_manual_override_upload', '_do_admin_post_imagify_ngg_manual_override_upload' );
function _do_admin_post_imagify_ngg_manual_override_upload() {
	if ( defined( 'DOING_AJAX' ) ) {
		check_ajax_referer( 'imagify-manual-override-upload' );
	} else {
		check_admin_referer( 'imagify-manual-override-upload' );
	}

	if ( ! isset( $_GET['attachment_id'] ) || ! current_user_can( 'upload_files' ) ) {
		if ( defined( 'DOING_AJAX' ) ) {
			wp_send_json_error();
		} else {
			wp_nonce_ays( '' );
		}
	}
	
	$attachment = new Imagify_NGG_Attachment( $_GET['attachment_id'] );
		
	// Restore the backup file
	$attachment->restore();

	// Optimize it!!!!!
	$attachment->optimize( (int) $_GET['optimization_level'] );

	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	// Return the optimization statistics
	$output = get_imagify_attachment_optimization_text( $attachment, 'ngg' );
	wp_send_json_success( $output );
}

/**
 * Process a restoration to the original attachment.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
add_action( 'wp_ajax_imagify_ngg_restore_upload', '_do_admin_post_imagify_ngg_restore_upload' );
add_action( 'admin_post_imagify_ngg_restore_upload', '_do_admin_post_imagify_ngg_restore_upload' );
function _do_admin_post_imagify_ngg_restore_upload() {
	if ( defined( 'DOING_AJAX' ) ) {
		check_ajax_referer( 'imagify-restore-upload' );
	} else {
		check_admin_referer( 'imagify-restore-upload' );
	}

	if ( ! isset( $_GET['attachment_id'] ) || ! current_user_can( 'upload_files' ) ) {
		if ( defined( 'DOING_AJAX' ) ) {
			wp_send_json_error();
		} else {
			wp_nonce_ays( '' );
		}
	}
	
	$attachment = new Imagify_NGG_Attachment( $_GET['attachment_id'] );
	
	// Restore the backup file
	$attachment->restore();
	
	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	// Return the optimization button
	$args = array(
		'attachment_id' => $attachment->id,
		'type' 			=> 'ngg'
	);
	
	$output = '<a id="imagify-upload-' . $attachment->id . '" href="' . get_imagify_admin_url( 'manual-upload', $args ) . '" class="button-primary button-imagify-manual-upload" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Optimize', 'imagify' ) . '</a>';
	wp_send_json_success( $output );
}
