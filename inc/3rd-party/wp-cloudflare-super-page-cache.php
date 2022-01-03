<?php
defined( 'ABSPATH' ) || die( 'Cheatin uh?' );

add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
	if (
		! is_admin()
		||
		'media_page_imagify-bulk-optimization' !== $hook_suffix
		||
		! class_exists( 'SWCFPC_Backend' )
	) {
		return;
	}

	wp_deregister_script( 'swcfpc_sweetalert_js' );
}, 100 );
