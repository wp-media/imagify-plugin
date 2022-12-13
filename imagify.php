<?php
/**
 * Plugin Name: Imagify
 * Plugin URI: https://wordpress.org/plugins/imagify/
 * Description: Dramatically reduce image file sizes without losing quality, make your website load faster, boost your SEO and save money on your bandwidth using Imagify, the new most advanced image optimization tool.
 * Version: 2.1
 * Requires at least: 5.3
 * Requires PHP: 7.0
 * Author: Imagify – Optimize Images & Convert WebP
 * Author URI: https://imagify.io
 * Licence: GPLv2
 *
 * Text Domain: imagify
 * Domain Path: languages
 *
 * Copyright 2022 WP Media
 */

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

// Imagify defines.
define( 'IMAGIFY_VERSION',        '2.1' );
define( 'IMAGIFY_SLUG',           'imagify' );
define( 'IMAGIFY_FILE',           __FILE__ );
define( 'IMAGIFY_PATH',           realpath( plugin_dir_path( IMAGIFY_FILE ) ) . '/' );
define( 'IMAGIFY_URL',            plugin_dir_url( IMAGIFY_FILE ) );
define( 'IMAGIFY_ASSETS_IMG_URL', IMAGIFY_URL . 'assets/images/' );
define( 'IMAGIFY_MAX_BYTES',      5242880 );
define( 'IMAGIFY_INT_MAX',        PHP_INT_MAX - 30 );
if ( ! defined( 'IMAGIFY_SITE_DOMAIN' ) ) {
	define( 'IMAGIFY_SITE_DOMAIN', 'https://imagify.io' );
}
if ( ! defined( 'IMAGIFY_APP_DOMAIN' ) ) {
	define( 'IMAGIFY_APP_DOMAIN',     'https://app.imagify.io' );
}
define( 'IMAGIFY_APP_API_URL',     IMAGIFY_APP_DOMAIN . '/api/' );


// Check for WordPress and PHP version.
if ( imagify_pass_requirements() ) {
	require_once IMAGIFY_PATH . 'inc/main.php';
}

/**
 * Check if Imagify is activated on the network.
 *
 * @since 1.0
 *
 * return bool True if Imagify is activated on the network.
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
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$is = is_plugin_active_for_network( plugin_basename( IMAGIFY_FILE ) );

	return $is;
}

/**
 * Check for WordPress and PHP version.
 *
 * @since  1.9
 * @author Grégory Viguier
 *
 * @return bool True if WP and PHP versions are OK.
 */
function imagify_pass_requirements() {
	static $check;

	if ( isset( $check ) ) {
		return $check;
	}

	require_once IMAGIFY_PATH . 'inc/classes/class-imagify-requirements-check.php';

	$requirement_checks = new Imagify_Requirements_Check(
		array(
			'plugin_name'    => 'Imagify',
			'plugin_file'    => IMAGIFY_FILE,
			'plugin_version' => IMAGIFY_VERSION,
			'wp_version'     => '5.3',
			'php_version'    => '7.0',
		)
	);

	$check = $requirement_checks->check();

	return $check;
}

/**
 * Load plugin translations.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
function imagify_load_translations() {
	static $done = false;

	if ( $done ) {
		return;
	}

	$done = true;

	load_plugin_textdomain( 'imagify', false, dirname( plugin_basename( IMAGIFY_FILE ) ) . '/languages/' );
}

/**
 * Set a transient on plugin activation, it will be used later to trigger activation hooks after the plugin is loaded.
 * The transient contains the ID of the user that activated the plugin.
 *
 * @since  1.9
 * @see    Imagify_Plugin->maybe_activate()
 * @author Grégory Viguier
 */
function imagify_set_activation() {
	if ( ! imagify_pass_requirements() ) {
		return;
	}

	if ( imagify_is_active_for_network() ) {
		set_site_transient( 'imagify_activation', get_current_user_id(), 30 );
	} else {
		set_transient( 'imagify_activation', get_current_user_id(), 30 );
	}
}
register_activation_hook( IMAGIFY_FILE, 'imagify_set_activation' );

/**
 * Trigger a hook on plugin deactivation.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
function imagify_deactivation() {
	if ( ! imagify_pass_requirements() ) {
		return;
	}

	/**
	 * Imagify deactivation.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	do_action( 'imagify_deactivation' );
}
register_deactivation_hook( IMAGIFY_FILE, 'imagify_deactivation' );

