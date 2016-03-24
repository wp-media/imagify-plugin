<?php
defined( 'ABSPATH' ) or	die( 'Cheatin&#8217; uh?' );

/*
 * Tell WP what to do when admin is loaded aka upgrader
 *
 * @since 1.0
 */
add_action( 'admin_init', '_imagify_upgrader' );
function _imagify_upgrader() {
	$current_version = get_imagify_option( 'version' );

	// You can hook the upgrader to trigger any action when Imagify is upgraded
	// first install
	if ( ! $current_version ) {
		do_action( 'imagify_first_install' );
	}
	// already installed but got updated
	elseif ( IMAGIFY_VERSION != $current_version ) {
		do_action( 'imagify_upgrade', IMAGIFY_VERSION, $current_version );
	}

	// If any upgrade has been done, we flush and update version #
	if ( did_action( 'imagify_first_install' ) || did_action( 'imagify_upgrade' ) ) {
		$options            = get_site_option( IMAGIFY_SETTINGS_SLUG ); // do not use get_imagify_option() here
		$options['version'] = IMAGIFY_VERSION;

		update_site_option( IMAGIFY_SETTINGS_SLUG, $options );
	}
}

/**
 * Keeps this function up to date at each version
 *
 * @since 1.0
 */
add_action( 'imagify_first_install', '_imagify_first_install' );
function _imagify_first_install() {	
	// Set a transient to know when we will have to display a notice to ask the user to rate the plugin.
	set_site_transient( 'imagify_seen_rating_notice', true, DAY_IN_SECONDS * 3 );
	
	// Create Options
	add_site_option( IMAGIFY_SETTINGS_SLUG,
		array(
			'api_key'            => '',
			'optimization_level' => 1,
			'auto_optimize'      => 1,
			'backup'             => 1,
			'resize_larger'		 => '',
			'resize_larger_w'	 => '',
			'exif'				 => 0,
			'disallowed-sizes'	 => array(),
			'admin_bar_menu'	 => 1
		)
	);
}

/**
 * What to do when Imagify is updated, depending on versions
 *
 * @since 1.0
 */
add_action( 'imagify_upgrade', '_imagify_new_upgrade', 10, 2 );
function _imagify_new_upgrade( $imagify_version, $current_version ) {	
	if ( version_compare( $current_version, '1.2', '<' ) ) {
		// Update all already optimized images status from 'error' to 'already_optimized'
		$query = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'			 => 'inherit',
				'post_mime_type'         => 'image',
				'meta_key'				 => '_imagify_status',
				'meta_value'			 => 'error',
				'posts_per_page'         => -1,
				'update_post_term_cache' => false,
				'no_found_rows'          => true,
				'fields'                 => 'ids'
			)
		);
		
		$ids = (array) $query->posts;
		
		foreach ( $ids as $id ) {
			$attachment         = new Imagify_Attachment( $id );
			$attachment_error   = $attachment->get_optimized_error();  
			$attachment_error   = trim( $attachment_error );
			$attachment_status	= get_post_meta( $id, '_imagify_status', true );
			
			if ( false !== strpos( $attachment_error, 'This image is already compressed' ) ) {
				update_post_meta( $id, '_imagify_status', 'already_optimized' );	
			}
		}
		
		// Auto-activate the Admin Bar option
		$options                   = get_site_option( IMAGIFY_SETTINGS_SLUG );
		$options['admin_bar_menu'] = 1;
		update_site_option( IMAGIFY_SETTINGS_SLUG, $options );
	}
	
	if ( version_compare( $current_version, '1.3.2', '<' ) ) {
		// Update all already optimized images status from 'error' to 'already_optimized'
		$query = new WP_Query(
			array(
				'post_type'              => 'attachment',
				'post_status'			 => 'inherit',
				'post_mime_type'         => 'image',
				'meta_query'			 => array(
					'relation' => 'AND',
					array(
						'key'     => '_imagify_data',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => '_imagify_optimization_level',
						'compare' => 'NOT EXISTS'
					),
				),
				'posts_per_page'         => -1,
				'update_post_term_cache' => false,
				'no_found_rows'          => true,
				'fields'                 => 'ids'
			)
		);
		
		$ids = (array) $query->posts;
		
		foreach ( $ids as $id ) {
			$attachment         = new Imagify_Attachment( $id );
			$attachment_stats   = $attachment->get_stats_data();  
			
			if ( isset( $attachment_stats['aggressive'] ) ) {
				update_post_meta( $id, '_imagify_optimization_level', (int) $attachment_stats['aggressive'] );
			}		
		}
	}
	
	if ( version_compare( $current_version, '1.4.5', '<' ) ) {
		// Delete all transients used for async optimization
		global $wpdb;
		$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "_transient_imagify-async-in-progress-%"' );
	}
}