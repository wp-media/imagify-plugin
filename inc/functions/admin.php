<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Check if Imagify is activated on the network.
 *
 * @since 1.0
 *
 * return bool True if Imagify is activated on the network
 */
function imagify_is_active_for_network() {
    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    return is_plugin_active_for_network( 'imagify/imagify.php' );
}

/*
 * Get the URL related to specific admin page or action.
 *
 * @since 1.0
 *
 * @return string The URL of the specific admin page or action
 */
function get_imagify_admin_url( $action = 'options-general', $arg = array() ) {
	$url     = '';
	$id      = ( isset( $arg['attachment_id'] ) ) ? $arg['attachment_id'] : 0;
	$context = ( isset( $arg['context'] ) ) ? $arg['context'] : 'wp';

	switch( $action ) {
		case 'manual-override-upload':
			$level = ( isset( $arg['optimization_level'] ) ) ? $arg['optimization_level'] : 0;
			$url   = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_override_upload&attachment_id=' . $id . '&optimization_level=' . $level . '&context=' . $context ), 'imagify-manual-override-upload' );
		break;

		case 'manual-upload':
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-manual-upload' );
		break;

		case 'restore-upload' :
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_restore_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-restore-upload' );
		break;

		case 'dismiss-notice':
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_dismiss_notice&notice=' . $arg ), 'imagify-dismiss-notice' );
		break;

		case 'bulk-optimization':
			$url = admin_url( 'upload.php?page=' . IMAGIFY_SLUG . '-bulk-optimization' );
		break;

		case 'options-general':
		default :
			$page = imagify_is_active_for_network() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
			$url  = $page . '?page=' . IMAGIFY_SLUG;
		break;
	}

	return $url;
}

/*
 * Get maximal width and height from all thumbnails.
 *
 * @since 1.1
 *
 * @return array An array containing the max with and height.
 */
function get_imagify_max_intermediate_image_size() {
	global $_wp_additional_image_sizes;
	
	$width  = 0;
	$height = 0;
	$limit	= 9999;
	$get_intermediate_image_sizes = get_intermediate_image_sizes();
	
	// Create the full array with sizes and crop info
	foreach( $get_intermediate_image_sizes as $_size ) {
	    if ( in_array( $_size, array( 'thumbnail', 'medium', 'large' ) ) ) {
	        $_size_width  = get_option( $_size . '_size_w' );
	        $_size_height = get_option( $_size . '_size_h' );     
	    } elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
	        $_size_width  = $_wp_additional_image_sizes[ $_size ]['width'];
	        $_size_height = $_wp_additional_image_sizes[ $_size ]['height'];
	    }
	    
	    if ( ! isset( $_size_width, $_size_height ) ) {
		    continue;
	    }
	    
	    if ( $_size_width < $limit ) {
	       $width = max( $width, $_size_width ); 
        }
        
        if ( $_size_height < $limit ) {
        	$height = max( $height, $_size_height );
        } 
	}
	return array( 
		'width'  => $width, 
		'height' => $height
	);
}

/**
 * Renew a dismissed Imagify notice.
 *
 * @since 1.0
 *
 * @return void
 */
function imagify_renew_notice( $notice, $user_id = 0 ) {
	global $current_user;
	$user_id = ( 0 === $user_id ) ? $current_user->ID : $user_id;
	$notices = get_user_meta( $user_id, '_imagify_ignore_notices', true );

	if( $notices && false !== array_search( $notice, $notices ) ) {
		unset( $notices[array_search( $notice, $notices )] );
		update_user_meta( $user_id, '_imagify_ignore_notices', $notices );
	}
}

/**
 * Dismissed an Imagify notice.
 *
 * @since 1.0
 *
 * @return void
 */
function imagify_dismiss_notice( $notice, $user_id = 0 ) {
	global $current_user;
	$user_id   = ( 0 === $user_id ) ? $current_user->ID : $user_id;
	$notices   = get_user_meta( $user_id, '_imagify_ignore_notices', true );
	$notices[] = $notice;
	$notices   = array_filter( $notices );
	$notices   = array_unique( $notices );

	update_user_meta( $user_id, '_imagify_ignore_notices', $notices );
}

/**
 * Combine two arrays with some specific keys.
 * We use this function to combine the result of 2 SQL queries.
 *
 * @since 1.4.5
 *
 * @return array $result The combined array
 */
function imagify_query_results_combine( $keys, $values ) {
	if ( ! $values ) {
		return array();
	}
	
	$result = array();
	$keys   = array_flip( $keys );
	
	foreach ( $values as $v ) {
		if ( isset( $keys[ $v['id'] ] ) ) {
			$result[ $v['id'] ] = $v['value'];
		}
	}
	
	return $result;
}