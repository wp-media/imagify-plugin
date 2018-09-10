<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'wp_ajax_imagify_manual_upload',             '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'admin_post_imagify_manual_upload',          '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'wp_ajax_imagify_manual_override_upload',    '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'admin_post_imagify_manual_override_upload', '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'wp_ajax_imagify_restore_upload',            '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'admin_post_imagify_restore_upload',         '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'wp_ajax_imagify_get_folder_type_data',      '_do_admin_post_imagify_ngg_user_capacity', 5 );
add_action( 'wp_ajax_bulk_info_seen_callback',           '_do_admin_post_imagify_ngg_user_capacity', 5 );
/**
 * On manual optimization, manual re-optimization, and manual restoration, filter the user capacity to operate Imagify within NGG.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 */
function _do_admin_post_imagify_ngg_user_capacity() {
	if ( ! empty( $_GET['context'] ) && 'NGG' === $_GET['context'] ) { // WPCS: CSRF ok.
		add_filter( 'imagify_capacity', 'imagify_get_ngg_capacity', 10, 2 );
	}
}

add_filter( 'imagify_current_user_can', 'imagify_ngg_current_user_can', 10, 4 );
/**
 * Filter the current user capability to operate Imagify.
 *
 * @since  1.6.11
 * @see    imagify_get_capacity()
 * @author Grégory Viguier
 *
 * @param  bool   $user_can  Tell if the current user has the required capacity to operate Imagify.
 * @param  string $capacity  The user capacity.
 * @param  string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
 * @param  int    $post_id   A post ID (a gallery ID for NGG).
 * @return bool
 */
function imagify_ngg_current_user_can( $user_can, $capacity, $describer, $post_id ) {
	static $user_can_per_gallery = array();

	if ( ! $user_can || ! $post_id || 'NextGEN Manage gallery' !== $capacity ) {
		return $user_can;
	}

	$image = nggdb::find_image( $post_id );

	if ( isset( $user_can_per_gallery[ $image->galleryid ] ) ) {
		return $user_can_per_gallery[ $image->galleryid ];
	}

	$gallery_mapper = C_Gallery_Mapper::get_instance();
	$gallery        = $gallery_mapper->find( $image->galleryid, false );

	if ( get_current_user_id() === $gallery->author || current_user_can( 'NextGEN Manage others gallery' ) ) {
		// The user created this gallery or can edit others galleries.
		$user_can_per_gallery[ $image->galleryid ] = true;
		return $user_can_per_gallery[ $image->galleryid ];
	}

	// The user can't edit this gallery.
	$user_can_per_gallery[ $image->galleryid ] = false;
	return $user_can_per_gallery[ $image->galleryid ];
}

add_action( 'wp_ajax_imagify_ngg_get_unoptimized_attachment_ids', '_do_wp_ajax_imagify_ngg_get_unoptimized_attachment_ids' );
/**
 * Get all unoptimized attachment ids.
 *
 * @since  1.0
 * @author Jonathan Buttigieg
 */
function _do_wp_ajax_imagify_ngg_get_unoptimized_attachment_ids() {
	global $wpdb;

	$ajax_post = Imagify_Admin_Ajax_Post::get_instance();

	imagify_check_nonce( 'imagify-bulk-upload' );
	imagify_check_user_capacity( 'bulk-optimize' );
	$ajax_post->check_can_optimize();

	@set_time_limit( 0 );

	$optimization_level = $ajax_post->get_optimization_level();

	$storage   = C_Gallery_Storage::get_instance();
	$ngg_table = $wpdb->prefix . 'ngg_pictures';
	$data      = array();
	$images    = $wpdb->get_results( $wpdb->prepare( // WPCS: unprepared SQL ok.
		"SELECT picture.pid as id, picture.filename, idata.optimization_level, idata.status, idata.data
		 FROM $ngg_table as picture
		 LEFT JOIN $wpdb->ngg_imagify_data as idata
		 ON picture.pid = idata.pid
		 WHERE idata.pid IS NULL
			OR idata.optimization_level != %d
			OR idata.status = 'error'
		LIMIT %d",
		$optimization_level,
		imagify_get_unoptimized_attachment_limit()
	), ARRAY_A );

	if ( ! $images ) {
		wp_send_json_success( array() );
	}

	$filesystem = imagify_get_filesystem();

	foreach ( $images as $image ) {
		$id        = absint( $image['id'] );
		$file_path = $storage->get_image_abspath( $id );

		/** This filter is documented in inc/functions/process.php. */
		$file_path = apply_filters( 'imagify_file_path', $file_path );

		if ( ! $file_path || ! $filesystem->exists( $file_path ) ) {
			continue;
		}

		$attachment_data  = maybe_unserialize( $image['data'] );
		$attachment_error = '';

		if ( isset( $attachment_data['sizes']['full']['error'] ) ) {
			$attachment_error = $attachment_data['sizes']['full']['error'];
		}

		$attachment_error              = trim( $attachment_error );
		$attachment_status             = $image['status'];
		$attachment_optimization_level = $image['optimization_level'];
		$attachment_backup_path        = get_imagify_ngg_attachment_backup_path( $file_path );

		// Don't try to re-optimize if the optimization level is still the same.
		if ( $optimization_level === $attachment_optimization_level && is_string( $attachment_error ) ) {
			continue;
		}

		// Don't try to re-optimize if there is no backup file.
		if ( 'success' === $attachment_status && $optimization_level !== $attachment_optimization_level && ! $filesystem->exists( $attachment_backup_path ) ) {
			continue;
		}

		// Don't try to re-optimize images already compressed.
		if ( 'already_optimized' === $attachment_status && $attachment_optimization_level >= $optimization_level ) {
			continue;
		}

		// Don't try to re-optimize images with an empty error message.
		if ( 'error' === $attachment_status && empty( $attachment_error ) ) {
			continue;
		}

		$data[ '_' . $id ] = $storage->get_image_url( $id );
	} // End foreach().

	if ( ! $data ) {
		wp_send_json_success( array() );
	}

	wp_send_json_success( $data );
}
