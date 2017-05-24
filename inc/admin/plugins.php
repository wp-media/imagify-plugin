<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'plugin_action_links_' . plugin_basename( IMAGIFY_FILE ),               '_imagify_plugin_action_links' );
add_filter( 'network_admin_plugin_action_links_' . plugin_basename( IMAGIFY_FILE ), '_imagify_plugin_action_links' );
/**
 * Add link to the plugin configuration pages.
 *
 * @since 1.0
 *
 * @param  array $actions An array of action links.
 * @return array
 */
function _imagify_plugin_action_links( $actions ) {
	array_unshift( $actions, sprintf( '<a href="%s">%s</a>', esc_url( get_imagify_admin_url( 'bulk-optimization' ) ), __( 'Bulk Optimization', 'imagify' ) ) );
	array_unshift( $actions, sprintf( '<a href="%s">%s</a>', esc_url( get_imagify_admin_url() ), __( 'Settings' ) ) );
	return $actions;
}
