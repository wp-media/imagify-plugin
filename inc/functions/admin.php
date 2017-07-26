<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Check if Imagify is activated on the network.
 *
 * @since 1.0
 *
 * return bool True if Imagify is activated on the network
 */
function imagify_is_active_for_network() {
	static $is;

	if ( isset( $is ) ) {
		return $is;
	}

	if ( ! is_multisite() ) {
		$is = false;
		return $is;
	}

	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$is = is_plugin_active_for_network( plugin_basename( IMAGIFY_FILE ) );

	return $is;
}

/**
 * Get the URL related to specific admin page or action.
 *
 * @since 1.0
 *
 * @param  string       $action An action.
 * @param  array|string $arg    An array of arguments. It can contain an attachment ID and/or a context.
 * @return string               The URL of the specific admin page or action.
 */
function get_imagify_admin_url( $action = 'options-general', $arg = array() ) {
	if ( is_array( $arg ) ) {
		$id      = isset( $arg['attachment_id'] )      ? $arg['attachment_id']      : 0;
		$context = isset( $arg['context'] )            ? $arg['context']            : 'wp';
		$level   = isset( $arg['optimization_level'] ) ? $arg['optimization_level'] : 0;
	}

	switch ( $action ) {
		case 'manual-override-upload':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_override_upload&attachment_id=' . $id . '&optimization_level=' . $level . '&context=' . $context ), 'imagify-manual-override-upload' );

		case 'manual-upload':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-manual-upload' );

		case 'restore-upload' :
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_restore_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-restore-upload' );

		case 'dismiss-notice':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_dismiss_notice&notice=' . $arg ), 'imagify-dismiss-notice' );

		case 'bulk-optimization':
			return admin_url( 'upload.php?page=' . IMAGIFY_SLUG . '-bulk-optimization' );

		default :
			$page = imagify_is_active_for_network() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
			return $page . '?page=' . IMAGIFY_SLUG;
	}
}

/**
 * Get maximal width and height from all thumbnails.
 *
 * @since 1.1
 *
 * @return array An array containing the max width and height.
 */
function get_imagify_max_intermediate_image_size() {
	global $_wp_additional_image_sizes;

	$width  = 0;
	$height = 0;
	$limit  = 9999;
	$sizes  = array( 'thumbnail' => 1, 'medium' => 1, 'large' => 1 );
	$get_intermediate_image_sizes = get_intermediate_image_sizes();

	// Create the full array with sizes and crop info.
	foreach ( $get_intermediate_image_sizes as $_size ) {
		if ( isset( $sizes[ $_size ] ) ) {
			$_size_width  = get_option( $_size . '_size_w' );
			$_size_height = get_option( $_size . '_size_h' );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$_size_width  = $_wp_additional_image_sizes[ $_size ]['width'];
			$_size_height = $_wp_additional_image_sizes[ $_size ]['height'];
		} else {
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
		'height' => $height,
	);
}

/**
 * Combine two arrays with some specific keys.
 * We use this function to combine the result of 2 SQL queries.
 *
 * @since 1.4.5
 * @since 1.6.7 Added the $keep_keys_order parameter.
 *
 * @param  array $keys            An array of keys.
 * @param  array $values          An array of arrays like array( 'id' => id, 'value' => value ).
 * @param  int   $keep_keys_order Set to true to return an array ordered like $keys instead of $values.
 * @return array                  The combined arrays.
 */
function imagify_query_results_combine( $keys, $values, $keep_keys_order = false ) {
	if ( ! $keys || ! $values ) {
		return array();
	}

	$result = array();
	$keys   = array_flip( $keys );

	foreach ( $values as $v ) {
		if ( isset( $keys[ $v['id'] ] ) ) {
			$result[ $v['id'] ] = $v['value'];
		}
	}

	if ( $keep_keys_order ) {
		$keys = array_intersect_key( $keys, $result );
		return array_replace( $keys, $result );
	}

	return $result;
}

/**
 * Get the default Bulk Optimization buffer size.
 *
 * @since  1.5.10
 * @author Jonathan Buttigieg
 *
 * @return int The buffer size.
 */
function get_imagify_bulk_buffer_size() {
	$sizes = count( get_imagify_thumbnail_sizes() );

	switch ( true ) {
		case ( $sizes >= 10 ) :
			return 1;

		case ( $sizes >= 8 ) :
			return 2;

		case ( $sizes >= 6 ) :
			return 3;

		default:
			return 4;
	}
}

/**
 * A helper to retrieve all values from one or several post metas, given a list of post IDs.
 * The $wpdb cache is flushed to save memory.
 *
 * @since  1.6.7
 * @author Grégory Viguier
 *
 * @param  array $metas An array of meta names like:
 *                      array(
 *                          'key1' => 'meta_name_1',
 *                          'key2' => 'meta_name_2',
 *                          'key3' => 'meta_name_3',
 *                      )
 *                      If a key contains 'data', the results will be unserialized.
 * @param  array $ids   An array of post IDs.
 * @return array        An array of arrays of results like:
 *                      array(
 *                          'key1' => array( post_id_1 => 'result_1', post_id_2 => 'result_2', post_id_3 => 'result_3' ),
 *                          'key2' => array( post_id_1 => 'result_4', post_id_3 => 'result_5' ),
 *                          'key3' => array( post_id_1 => 'result_6', post_id_2 => 'result_7' ),
 *                      )
 */
function imagify_get_wpdb_metas( $metas, $ids ) {
	global $wpdb;

	if ( ! $ids ) {
		return array_fill_keys( array_keys( $metas ), array() );
	}

	$sql_ids = implode( ',', $ids );

	foreach ( $metas as $result_name => $meta_name ) {
		$metas[ $result_name ] = $wpdb->get_results( // WPCS: unprepared SQL ok.
			"SELECT pm.post_id as id, pm.meta_value as value
			FROM $wpdb->postmeta as pm
			WHERE pm.meta_key = '$meta_name'
				AND pm.post_id IN ( $sql_ids )
			ORDER BY pm.post_id DESC",
			ARRAY_A
		);

		$wpdb->flush();
		$metas[ $result_name ] = imagify_query_results_combine( $ids, $metas[ $result_name ], true );

		if ( strpos( $result_name, 'data' ) !== false ) {
			$metas[ $result_name ] = array_map( 'maybe_unserialize', $metas[ $result_name ] );
		}
	}

	return $metas;
}

/**
 * Simple helper to get the WP Rocket's site URL.
 * The URL is localized and contains some utm_*** parameters.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @return string The URL.
 */
function imagify_get_wp_rocket_url() {
	$wprocket_url = 'https://wp-rocket.me/';
	$locale       = get_locale();
	$suffixes     = array(
		'fr_FR' => 'fr',
		'es_ES' => 'es',
		'it_IT' => 'it',
		'de_DE' => 'de',
	);

	if ( isset( $suffixes[ $locale ] ) ) {
		$wprocket_url .= $suffixes[ $locale ] . '/';
	}

	$wprocket_url .= '?utm_source=imagify-coupon&utm_medium=plugin&utm_campaign=imagify';

	return $wprocket_url;
}
