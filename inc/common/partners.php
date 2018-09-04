<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

add_action( 'update_option_' . Imagify_Options::get_instance()->get_option_name(), 'imagify_maybe_delete_partner_on_option_update', 10, 2 );
/**
 * After the first API key has been successfully added, make sure the partner ID is deleted.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @param mixed $old_value The old option value.
 * @param mixed $new_value The new option value.
 */
function imagify_maybe_delete_partner_on_option_update( $old_value, $new_value ) {
	if ( empty( $old_value['api_key'] ) && ! empty( $new_value['api_key'] ) ) {
		imagify_delete_partner();
	}
}

add_action( 'update_site_option_' . Imagify_Options::get_instance()->get_option_name(), 'imagify_maybe_delete_partner_on_network_option_update', 10, 3 );
/**
 * After the first API key has been successfully added to the network option, make sure the partner ID is deleted.
 *
 * @since  1.6.14
 * @author Grégory Viguier
 *
 * @param string $option    Name of the network option.
 * @param mixed  $new_value The new network option value.
 * @param mixed  $old_value The old network option value.
 */
function imagify_maybe_delete_partner_on_network_option_update( $option, $new_value, $old_value ) {
	imagify_maybe_delete_partner_on_option_update( $old_value, $new_value );
}
