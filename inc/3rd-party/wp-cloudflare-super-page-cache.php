<?php
defined( 'ABSPATH' ) || die( 'Cheatin uh?' );

add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {

	$imagify_admin_pages = array(
		'media_page_imagify-bulk-optimization',
		'settings_page_imagify',
		'media_page_imagify-files',
		'nextgen-gallery_page_imagify-ngg-bulk-optimization',
	);

	if (
		! is_admin()
		||
		! class_exists( 'SWCFPC_Backend' )
		||
		! in_array( $hook_suffix, $imagify_admin_pages, true )
	) {
		return;
	}

	wp_deregister_script( 'swcfpc_sweetalert_js' );

}, 100 );
