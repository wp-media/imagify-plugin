<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Check if gravity form is active and no-conflict mode is enabled,
 * and you're in gravity form page.
 */
if ( is_plugin_active( 'gravityforms/gravityforms.php' )
	&& class_exists( 'GFCommon' )
	&& get_option( 'gform_enable_noconflict', false )
	&& GFForms::is_gravity_page()
) {
	add_filter( 'gform_noconflict_styles', 'imagify_gf_noconflict_styles' );
	add_filter( 'gform_noconflict_scripts', 'imagify_gf_noconflict_scripts' );
}

/**
 * Register imagify styles to gravity forms conflict styles
 *
 * @param array $styles Array fo registered styles.
 *
 * @return array
 */
function imagify_gf_noconflict_styles( $styles ) {
	$styles[] = 'imagify-admin-bar';
	$styles[] = 'imagify-admin';
	$styles[] = 'imagify-notices';
	$styles[] = 'imagify-pricing-modal';

	return $styles;
}

/**
 * Register Imagify scripts to gravity forms conflict scripts
 *
 * @param array $scripts Array fo registered scripts.
 *
 * @return array
 */
function imagify_gf_noconflict_scripts( $scripts ) {
	$scripts[] = 'imagify-admin-bar';
	$scripts[] = 'imagify-sweetalert';
	$scripts[] = 'imagify-admin';
	$scripts[] = 'imagify-notices';
	$scripts[] = 'imagify-pricing-modal';

	return $scripts;
}
