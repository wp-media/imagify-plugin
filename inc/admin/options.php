<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

add_filter( 'option_page_capability_imagify', '_imagify_correct_capability_for_options_page' );
/**
 * Fix the capability for our capacity filter hook
 *
 * @author  Jonathan
 * @since 1.0
 */
function _imagify_correct_capability_for_options_page() {
	return imagify_get_capacity();
}

add_action( 'admin_init', '_imagify_register_setting' );
/**
 * Tell to WordPress to be confident with our setting, we are clean!
 *
 * @author Jonathan
 * @since 1.0
 */
function _imagify_register_setting() {
	register_setting( 'imagify', 'imagify_settings' );
}

add_filter( 'pre_update_option_' . IMAGIFY_SETTINGS_SLUG, '_imagify_pre_update_option', 10, 2 );
/**
 * Filter specific options before its value is (maybe) serialized and updated.
 *
 * @author Jonathan
 * @since 1.0
 *
 * @param  mixed $value     The new option value.
 * @param  mixed $old_value The old option value.
 * @return array The new option value.
 */
function _imagify_pre_update_option( $value, $old_value ) {
	$value = is_array( $value ) ? $value : array();

	// Store all sizes even if one of them isn't checked.
	if ( ! empty( $value['sizes'] ) && is_array( $value['sizes'] ) ) {
		$value['disallowed-sizes'] = array();

		foreach ( $value['sizes'] as $size_key => $size_value ) {
			if ( strpos( $size_key , '-hidden' ) ) {
				$key = str_replace( '-hidden', '', $size_key );

				if ( ! isset( $value['sizes'][ $key ] ) ) {
					$value['disallowed-sizes'][ $key ] = '1';
				}
			}
		}
	}

	unset( $value['sizes'] );

	// The max width for the "Resize larger images" option can't be 0.
	if ( empty( $value['resize_larger_w'] ) ) {
		$value['resize_larger_w'] = '';
		$value['resize_larger']   = 0;
	}

	// The max width for the "Resize larger images" option can't be less than the largest thumbnail width.
	$max_sizes = get_imagify_max_intermediate_image_size();

	if ( ! empty( $value['resize_larger_w'] ) && $value['resize_larger_w'] < $max_sizes['width'] ) {
		$value['resize_larger_w'] = $max_sizes['width'];
	}

	if ( ! isset( $value['total_size_images_library'] ) && isset( $old_value['total_size_images_library'] ) ) {
		$value['total_size_images_library'] = $old_value['total_size_images_library'];
	}

	if ( ! isset( $value['average_size_images_per_month'] ) && isset( $old_value['average_size_images_per_month'] ) ) {
		$value['average_size_images_per_month'] = $old_value['average_size_images_per_month'];
	}

	return $value;
}

if ( imagify_is_active_for_network() ) {
	add_filter( 'pre_update_site_option_' . IMAGIFY_SETTINGS_SLUG, '_imagify_maybe_set_redirection_before_save_options', 10, 2 );
} else {
	add_filter( 'pre_update_option_' . IMAGIFY_SETTINGS_SLUG, '_imagify_maybe_set_redirection_before_save_options', 10, 2 );
}
/**
 * If the user clicked the "Save & Go to Bulk Optimizer" button, set a redirection to the bulk optimizer.
 * We use this hook because it can be triggered even if the option value hasn't changed.
 *
 * @since  1.6.8
 * @author Grégory Viguier
 *
 * @param  mixed $value     The new, unserialized option value.
 * @param  mixed $old_value The old option value.
 * @return mixed            The option value.
 */
function _imagify_maybe_set_redirection_before_save_options( $value, $old_value ) {

	if ( ! is_admin() || ! isset( $_POST['submit-goto-bulk'] ) ) { // WPCS: CSRF ok.
		return $value;
	}

	$_REQUEST['_wp_http_referer'] = esc_url_raw( get_admin_url( get_current_blog_id(), 'upload.php?page=imagify-bulk-optimization' ) );

	return $value;
}

if ( imagify_is_active_for_network() ) {
	add_action( 'update_site_option_' . IMAGIFY_SETTINGS_SLUG, '_imagify_after_save_network_options', 10, 3 );
}
/**
 * Used to launch some actions after saving the network options.
 *
 * @author Grégory Viguier
 * @since 1.6.5
 *
 * @param string $option     Name of the network option.
 * @param mixed  $value      Current value of the network option.
 * @param mixed  $old_value  Old value of the network option.
 */
function _imagify_after_save_network_options( $option, $value, $old_value ) {
	_imagify_after_save_options( $old_value, $value );
}

if ( ! imagify_is_active_for_network() ) {
	add_action( 'update_option_' . IMAGIFY_SETTINGS_SLUG, '_imagify_after_save_options', 10, 2 );
}
/**
 * Used to launch some actions after saving the options.
 *
 * @author Jonathan
 * @since  1.0
 * @since  1.5   Used to redirect user to Bulk Optimizer (if requested).
 * @since  1.6.8 Not used to redirect user to Bulk Optimizer anymore: see _imagify_maybe_set_redirection_before_save_options().
 *
 * @param mixed $old_value The old option value.
 * @param mixed $value     The new option value.
 */
function _imagify_after_save_options( $old_value, $value ) {

	if ( ! $old_value || ! $value || isset( $old_value['api_key'], $value['api_key'] ) && $old_value['api_key'] === $value['api_key'] ) {
		return;
	}

	if ( is_wp_error( get_imagify_user() ) ) {
		imagify_renew_notice( 'wrong-api-key' );
		delete_site_transient( 'imagify_check_licence_1' );
	} else {
		imagify_dismiss_notice( 'wrong-api-key' );
	}
}

if ( imagify_is_active_for_network() ) :

	add_action( 'admin_post_update', '_imagify_update_site_option_on_network' );
	/**
	 * `options.php` do not handle site options. Let's use `admin-post.php` for multisite installations.
	 *
	 * @since 1.0
	 */
	function _imagify_update_site_option_on_network() {
		$option_group = IMAGIFY_SLUG;

		if ( ! isset( $_POST['option_page'] ) || $_POST['option_page'] !== $option_group ) { // WPCS: CSRF ok.
			return;
		}

		$capability = apply_filters( "option_page_capability_{$option_group}", 'manage_network_options' );

		if ( ! current_user_can( $capability ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
		}

		check_admin_referer( $option_group . '-options' );

		$whitelist_options = apply_filters( 'whitelist_options', array() );

		if ( ! isset( $whitelist_options[ $option_group ] ) ) {
			wp_die( __( '<strong>ERROR</strong>: options page not found.' ) );
		}

		$options = $whitelist_options[ $option_group ];

		if ( $options ) {
			foreach ( $options as $option ) {
				$option = trim( $option );
				$value  = null;

				if ( isset( $_POST[ $option ] ) ) {
					$value = $_POST[ $option ];
					if ( ! is_array( $value ) ) {
						$value = trim( $value );
					}
					$value = wp_unslash( $value );
				}

				update_site_option( $option, $value );
			}
		}

		/**
		 * Redirect back to the settings page that was submitted.
		 */
		$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
		wp_safe_redirect( $goback );
		exit;
	}

endif;
