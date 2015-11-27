<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Process all thumbnails of a specific image with Imagify with the manual method.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_manual_upload'		, '_do_admin_post_imagify_manual_upload' );
add_action( 'admin_post_imagify_manual_upload'	, '_do_admin_post_imagify_manual_upload' );
function _do_admin_post_imagify_manual_upload() {
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

	$attachment = new Imagify_Attachment( $_GET['attachment_id'] );
	
	// Optimize it!!!!!
	$attachment->optimize();

	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	// Return the optimization statistics
	$output = get_imagify_attachment_optimization_text( $attachment->id );
	wp_send_json_success( $output );
}

/**
 * Process a manual upload by overriding the optimization level.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_manual_override_upload', '_do_admin_post_imagify_manual_override_upload' );
add_action( 'admin_post_imagify_manual_override_upload', '_do_admin_post_imagify_manual_override_upload' );
function _do_admin_post_imagify_manual_override_upload() {
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
	
	$attachment = new Imagify_Attachment( $_GET['attachment_id'] );
		
	// Restore the backup file
	$attachment->restore();

	// Optimize it!!!!!
	$attachment->optimize( (int) $_GET['optimization_level'] );

	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	// Return the optimization statistics
	$output = get_imagify_attachment_optimization_text( $attachment->id );
	wp_send_json_success( $output );
}

/**
 * Process a restoration to the original attachment.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_restore_upload', '_do_admin_post_imagify_restore_upload' );
add_action( 'admin_post_imagify_restore_upload', '_do_admin_post_imagify_restore_upload' );
function _do_admin_post_imagify_restore_upload() {
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
	
	$attachment = new Imagify_Attachment( $_GET['attachment_id'] );
	
	// Restore the backup file
	$attachment->restore();
	
	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}

	// Return the optimization button
	$output = '<a id="imagify-upload-' . $attachment->id . '" href="' . get_imagify_admin_url( 'manual-upload', $attachment->id ) . '" class="button-primary button-imagify-manual-upload" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Optimize', 'imagify' ) . '</a>';
	wp_send_json_success( $output );
}

/**
 * Get all unoptimized attachment ids.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_get_unoptimized_attachment_ids', '_do_wp_ajax_imagify_get_unoptimized_attachment_ids' );
function _do_wp_ajax_imagify_get_unoptimized_attachment_ids() {
	check_ajax_referer( 'imagify-bulk-upload', 'imagifybulkuploadnonce' );

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error();
	}
	
	$user = new Imagify_User();
	
	if ( $user->is_over_quota() ) {
		wp_send_json_error( array( 'message' => 'over-quota' ) );
	}
	
	set_time_limit( 0 );
	
	$args = array(
		'fields'                 => 'ids',
		'post_type'              => 'attachment',
		'post_status'            => 'any',
		'post_mime_type'         => array( 'image/jpeg', 'image/png' ), // TO DO - add gif later
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	);
	
	$data                       = array();
	$query                      = new WP_Query( $args );
	$ids                        = $query->posts;
	$optimization_level         = (int) $_GET['optimization_level'];
	
	// Save the optimization level in a transient to retrieve it later during the process
	set_transient( 'imagify_bulk_optimization_level', $optimization_level );
	
	foreach( $ids as $id ) {
		/** This filter is documented in inc/functions/process.php */
		$file_path = apply_filters( 'imagify_file_path', get_attached_file( $id ) );
		
		if ( file_exists( $file_path ) ) {
			$attachment        = new Imagify_Attachment( $id );
			$attachment_error  = $attachment->get_optimized_error();  
			$attachment_error  = trim( $attachment_error );
			$attachment_status = get_post_meta( $id, '_imagify_status', true );
			
			// Don't try to re-optimize if the optimization level is still the same
			if ( $optimization_level === $attachment->get_optimization_level() && ! $attachment->has_error() ) {
				continue;					
			}
			
			// Don't try to re-optimize if there is no backup file
			if ( $optimization_level !== $attachment->get_optimization_level() && ! $attachment->has_backup() && $attachment->is_optimized() ) {
				continue;					
			}
			
			// Don't try to re-optimize images already compressed
			if ( $attachment->get_optimization_level() > $optimization_level && false !== strpos( $attachment_error, 'This image is already compressed' ) ) {
				continue;	
			}
			
			// Don't try to re-optimize images with an empty error message
			if ( $attachment_status == 'error' && empty( $attachment_error ) ) {
				continue;
			}
									
			$data[ '_' . $id ] = wp_get_attachment_url( $id );	
		}
	}
	
	if ( (bool) $data ) {
		wp_send_json_success( $data );
	}
		
	wp_send_json_error( array( 'message' => 'no-images' ) );
}

/**
 * Process all thumbnails of a specific image with Imagify with the bulk method.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_bulk_upload', '_do_wp_ajax_imagify_bulk_upload' );
function _do_wp_ajax_imagify_bulk_upload() {
	check_ajax_referer( 'imagify-bulk-upload', 'imagifybulkuploadnonce' );
	
	if ( ! isset( $_POST['image'] ) || ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error();
	}

	$attachment_id      = (int) $_POST['image'];
	$attachment         = new Imagify_Attachment( $_POST['image'] );
	$optimization_level = get_transient( 'imagify_bulk_optimization_level' );
	
	// Restore it if the optimization level is updated
	if ( $optimization_level !== $attachment->get_optimization_level() ) {
		$attachment->restore();
	}
	
	// Optimize it!!!!!
	$attachment->optimize( $optimization_level );

	// Return the optimization statistics
	$fullsize_data         = $attachment->get_size_data();
	$stats_data            = $attachment->get_stats_data();
	$saving_data           = imagify_count_saving_data();
	$user		   		   = new Imagify_User();
	$data                  = array(
		'global_already_optimized_attachments' => $saving_data['count'],
		'global_optimized_attachments'         => imagify_count_optimized_attachments(),
		'global_unoptimized_attachments'       => imagify_count_unoptimized_attachments(),
		'global_errors_attachments'            => imagify_count_error_attachments(),
		'global_optimized_attachments_percent' => imagify_percent_optimized_attachments(),
		'global_optimized_percent'             => $saving_data['percent'],
		'global_original_human'                => size_format( $saving_data['original_size'], 1 ),
		'global_optimized_human'               => size_format( $saving_data['optimized_size'], 1 ),
		'global_unconsumed_quota'              => $user->get_percent_unconsumed_quota(),
	);
	
	if ( ! $attachment->is_optimized() ) {
		$data['success'] 		= false;
		$data['error']   		= $fullsize_data['error'];
		
		wp_send_json_error( $data );
	}
	
	$data['success']               = true;
	$data['original_size']         = $fullsize_data['original_size'];
	$data['new_size']              = $fullsize_data['optimized_size'];
	$data['percent']               = $fullsize_data['percent'];
	$data['overall_saving'] 	   = $stats_data['original_size'] - $stats_data['optimized_size'];
	$data['original_overall_size'] = $stats_data['original_size'];
	$data['new_overall_size']      = $stats_data['optimized_size'];
	$data['thumbnails']            = $attachment->get_optimized_sizes_count();
		
	wp_send_json_success( $data );
}

/**
 * Create a new Imagify account.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_signup', '_do_wp_ajax_imagify_signup' );
function _do_wp_ajax_imagify_signup() {
	check_ajax_referer( 'imagify-signup', 'imagifysignupnonce' );

	if ( ! isset( $_GET['email'] ) ) {
		wp_send_json_error();
	}

	$data = array(
		'email'    => $_GET['email'],
		'password' => wp_generate_password( 12, false ),
		'lang'	   => get_locale()
	);

	$response = add_imagify_user( $data );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}

	wp_send_json_success();
}

/**
 * Process an API key check validity.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_check_api_key_validity', '_do_wp_ajax_imagify_check_api_key_validity' );
function _do_wp_ajax_imagify_check_api_key_validity() {
	check_ajax_referer( 'imagify-check-api-key', 'imagifycheckapikeynonce' );

	if ( ! isset( $_GET['api_key'] ) ) {
		wp_send_json_error();
	}

	$response = get_imagify_status( $_GET['api_key'] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( $response->get_error_message() );
	}
	
	$options            = get_site_option( IMAGIFY_SETTINGS_SLUG );
	$options['api_key'] = sanitize_key( $_GET['api_key'] );

	update_site_option( IMAGIFY_SETTINGS_SLUG, $options );

	wp_send_json_success();
}

/**
 * Process a dismissed notice.
 *
 * @since 1.0
 */
add_action( 'wp_ajax_imagify_dismiss_notice', '_do_admin_post_imagify_dismiss_notice' );
add_action( 'admin_post_imagify_dismiss_notice', '_do_admin_post_imagify_dismiss_notice' );
function _do_admin_post_imagify_dismiss_notice() {
	if ( defined( 'DOING_AJAX' ) ) {
		check_ajax_referer( 'imagify-dismiss-notice' );
	} else {
		check_admin_referer( 'imagify-dismiss-notice' );
	}

	if ( ! isset( $_GET['notice'] ) || ! current_user_can( 'manage_options' ) ) {
		if ( defined( 'DOING_AJAX' ) ) {
			wp_send_json_error();
		} else {
			wp_nonce_ays( '' );
		}
	}

	imagify_dismiss_notice( $_GET['notice'] );
	
	if ( ! defined( 'DOING_AJAX' ) ) {
		wp_safe_redirect( wp_get_referer() );
		die();
	}
	
	wp_send_json_success();
}

/**
 * Disable a plugin which can be in conflict with Imagify
 *
 * @since 1.2
 */
add_action( 'admin_post_imagify_deactivate_plugin', '_imagify_deactivate_plugin' );
function _imagify_deactivate_plugin() {
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'imagifydeactivatepluginnonce' ) ) {
		wp_nonce_ays( '' );
	}

	deactivate_plugins( $_GET['plugin'] );

	wp_safe_redirect( wp_get_referer() );
	die();
}