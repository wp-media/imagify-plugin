<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
 * Tell if the current screen is what we're looking for.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param  string $identifier The screen "name".
 * @return bool
 */
function imagify_is_screen( $identifier ) {
	global $post_id;

	if ( ! $identifier ) {
		return false;
	}

	$current_screen = get_current_screen();

	if ( ! $current_screen || ! $current_screen->in_admin() ) {
		return false;
	}

	switch ( $identifier ) {
		case 'imagify-settings':
			// Imagify Settings or Imagify Network Settings.
			$slug = Imagify_Views::get_instance()->get_settings_page_slug();
			return 'settings_page_' . $slug === $current_screen->id || $slug . '_page_' . $slug . '-network' === $current_screen->id || 'toplevel_page_' . $slug . '-network' === $current_screen->id;

		case 'imagify-network-settings':
			// Imagify Network Settings.
			$slug = Imagify_Views::get_instance()->get_settings_page_slug();
			return $slug . '_page_' . $slug . '-network' === $current_screen->id || 'toplevel_page_' . $slug . '-network' === $current_screen->id;

		case 'library':
			// Media Library.
			return 'upload' === $current_screen->id;

		case 'upload':
			// Upload New Media.
			return 'media' === $current_screen->id;

		case 'post':
			// Edit Post, Page, Attachment, etc.
			return 'post' === $current_screen->base;

		case 'attachment':
		case 'post-attachment':
			// Edit Attachment.
			return 'post' === $current_screen->base && 'attachment' === $current_screen->id && $post_id && imagify_is_attachment_mime_type_supported( $post_id );

		case 'bulk':
		case 'bulk-optimization':
			// Bulk Optimization (any).
			$slug = Imagify_Views::get_instance()->get_bulk_page_slug();
			return 'toplevel_page_' . $slug . '-network' === $current_screen->id || 'media_page_' . $slug === $current_screen->id;

		case 'files-bulk-optimization':
			// Bulk Optimization (custom folders).
			$slug = Imagify_Views::get_instance()->get_bulk_page_slug();
			return 'toplevel_page_' . $slug . '-network' === $current_screen->id || 'media_page_' . $slug === $current_screen->id;

		case 'files':
		case 'files-list':
			// "Custom folders" files list.
			$slug = Imagify_Views::get_instance()->get_files_page_slug();
			return 'imagify_page_' . $slug . '-network' === $current_screen->id || 'media_page_' . $slug === $current_screen->id;

		case 'media-modal':
			// Media modal.
			return did_action( 'wp_enqueue_media' ) || doing_filter( 'wp_enqueue_media' );

		default:
			return $identifier === $current_screen->id;
	}
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
function get_imagify_admin_url( $action = 'settings', $arg = array() ) {
	if ( is_array( $arg ) ) {
		$id      = isset( $arg['attachment_id'] )      ? $arg['attachment_id']      : 0;
		$context = isset( $arg['context'] )            ? $arg['context']            : 'wp';
		$level   = isset( $arg['optimization_level'] ) ? $arg['optimization_level'] : '';
	}

	switch ( $action ) {
		case 'manual-override-upload':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_override_upload&attachment_id=' . $id . '&optimization_level=' . $level . '&context=' . $context ), 'imagify-manual-override-upload-' . $id . '-' . $context );

		case 'optimize-missing-sizes':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_optimize_missing_sizes&attachment_id=' . $id . '&context=' . $context ), 'imagify-optimize-missing-sizes-' . $id . '-' . $context );

		case 'manual-upload':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_manual_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-manual-upload-' . $id . '-' . $context );

		case 'restore-upload':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_restore_upload&attachment_id=' . $id . '&context=' . $context ), 'imagify-restore-upload-' . $id . '-' . $context );

		case 'dismiss-notice':
			return wp_nonce_url( admin_url( 'admin-post.php?action=imagify_dismiss_notice&notice=' . $arg ), Imagify_Notices::DISMISS_NONCE_ACTION );

		case 'optimize-file':
		case 'restore-file':
		case 'refresh-file-modified':
			$action = 'imagify_' . str_replace( '-', '_', $action );
			return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&id=' . $id ), $action );

		case 'reoptimize-file':
			$action = 'imagify_' . str_replace( '-', '_', $action );
			return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&id=' . $id . '&level=' . $level ), $action );

		case 'get-files-tree':
			return wp_nonce_url( admin_url( 'admin-ajax.php?action=imagify_get_files_tree' ), 'get-files-tree' );

		case 'bulk-optimization':
			return admin_url( 'upload.php?page=' . Imagify_Views::get_instance()->get_bulk_page_slug() );

		case 'files-bulk-optimization':
			$page = '?page=' . Imagify_Views::get_instance()->get_bulk_page_slug();
			return imagify_is_active_for_network() ? network_admin_url( 'admin.php' . $page ) : admin_url( 'upload.php' . $page );

		case 'files-list':
			$page = '?page=' . Imagify_Views::get_instance()->get_files_page_slug();
			return imagify_is_active_for_network() ? network_admin_url( 'admin.php' . $page ) : admin_url( 'upload.php' . $page );

		case 'folder-errors':
			switch ( $arg ) {
				case 'library':
					return add_query_arg( array(
						'mode'           => 'list',
						'imagify-status' => 'errors',
					), admin_url( 'upload.php' ) );

				case 'custom-folders':
					return add_query_arg( array(
						'status-filter' => 'errors',
					), get_imagify_admin_url( 'files-list' ) );
			}
			return '';

		default:
			$page = '?page=' . Imagify_Views::get_instance()->get_settings_page_slug();
			return imagify_is_active_for_network() ? network_admin_url( 'admin.php' . $page ) : admin_url( 'options-general.php' . $page );
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
	$width  = 0;
	$height = 0;
	$limit  = 9999;

	foreach ( get_imagify_thumbnail_sizes() as $_size ) {
		if ( $_size['width'] > $width && $_size['width'] < $limit ) {
			$width = $_size['width'];
		}

		if ( $_size['height'] > $height && $_size['height'] < $limit ) {
			$height = $_size['height'];
		}
	}

	return array(
		'width'  => $width,
		'height' => $height,
	);
}

/**
 * Get the default Bulk Optimization buffer size.
 *
 * @since  1.5.10
 * @since  1.7 Added $sizes parameter.
 * @author Jonathan Buttigieg
 *
 * @param  int $sizes Number of image sizes per item (attachment).
 * @return int        The buffer size.
 */
function get_imagify_bulk_buffer_size( $sizes = false ) {
	if ( ! $sizes ) {
		$sizes = count( get_imagify_thumbnail_sizes() );
	}

	switch ( true ) {
		case ( $sizes >= 10 ):
			return 1;

		case ( $sizes >= 8 ):
			return 2;

		case ( $sizes >= 6 ):
			return 3;

		default:
			return 4;
	}
}

/**
 * Simple helper to get the WP Rocket's site URL.
 * The URL is localized and contains some utm_*** parameters.
 *
 * @since  1.6.8
 * @since  1.6.9 Added $path and $query parameters.
 * @author Grégory Viguier
 *
 * @param  string $path  A path to add to the URL (URI). Not in use yet.
 * @param  array  $query An array of query arguments (utm_*).
 * @return string The URL.
 */
function imagify_get_wp_rocket_url( $path = false, $query = array() ) {
	$wprocket_url = 'https://wp-rocket.me/';

	// Current lang.
	$lang = imagify_get_current_lang_in( array( 'de', 'es', 'fr', 'it' ) );

	if ( 'en' !== $lang ) {
		$wprocket_url .= $lang . '/';
	}

	// URI.
	$paths = array(
		'pricing' => array(
			'de' => 'preise',
			'en' => 'pricing',
			'es' => 'precios',
			'fr' => 'offres',
			'it' => 'offerte',
		),
	);

	if ( $path ) {
		$path = trim( $path, '/' );

		if ( isset( $paths[ $path ] ) ) {
			$wprocket_url .= $paths[ $path ][ $lang ] . '/';
		} else {
			$wprocket_url .= $path . '/';
		}
	}

	// Query args.
	$query = array_merge( array(
		'utm_source'   => 'imagify-coupon',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'imagify',
	), $query );

	return add_query_arg( $query, $wprocket_url );
}

/**
 * Check for nonce.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string      $action Action nonce.
 * @param string|bool $query_arg Optional. Key to check for the nonce in `$_REQUEST`. If false, `$_REQUEST` values will be evaluated for '_ajax_nonce', and '_wpnonce' (in that order). Default false.
 */
function imagify_check_nonce( $action, $query_arg = false ) {
	if ( ! check_ajax_referer( $action, $query_arg, false ) ) {
		imagify_die();
	}
}

/**
 * Check for user capacity.
 *
 * @since  1.6.10
 * @since  1.6.11 Uses a capacity describer instead of a capacity itself.
 * @see    imagify_get_capacity()
 * @author Grégory Viguier
 *
 * @param string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
 * @param int    $post_id   A post ID.
 */
function imagify_check_user_capacity( $describer = 'manage', $post_id = null ) {
	if ( ! imagify_current_user_can( $describer, $post_id ) ) {
		imagify_die();
	}
}

/**
 * Die Today.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string $message A message to display.
 */
function imagify_die( $message = null ) {
	if ( ! isset( $message ) ) {
		/* translators: This sentense already exists in WordPress. */
		$message = __( 'Sorry, you are not allowed to do that.', 'imagify' );
	} elseif ( is_wp_error( $message ) ) {
		$message = imagify_translate_api_message( $message->get_error_message() );
	}

	if ( is_array( $message ) ) {
		if ( ! empty( $message['error'] ) ) {
			$message['error']  = imagify_translate_api_message( $message['error'] );
		} elseif ( ! empty( $message['detail'] ) ) {
			$message['detail'] = imagify_translate_api_message( $message['detail'] );
		}
	}

	if ( wp_doing_ajax() ) {
		wp_send_json_error( $message );
	}

	if ( is_array( $message ) ) {
		if ( ! empty( $message['error'] ) ) {
			$message = $message['error'];
		} elseif ( ! empty( $message['detail'] ) ) {
			$message = $message['detail'];
		} else {
			$message = reset( $message );
		}
	}

	if ( wp_get_referer() ) {
		$message .= '</p><p>';
		$message .= sprintf( '<a href="%s">%s</a>',
			esc_url( remove_query_arg( 'updated', wp_get_referer() ) ),
			/* translators: This sentense already exists in WordPress. */
			__( 'Go back', 'imagify' )
		);
	}

	/* translators: %s is the plugin name. */
	wp_die( $message, sprintf( __( '%s Failure Notice', 'imagify' ), 'Imagify' ), 403 );
}

/**
 * Redirect if not an ajax request.
 *
 * @since  1.6.10
 * @author Grégory Viguier
 *
 * @param string       $message     A message to display in an admin notice once redirected.
 * @param array|string $args_or_url An array of query args to add to the redirection URL. If a string, the complete URL.
 */
function imagify_maybe_redirect( $message = false, $args_or_url = array() ) {
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( $args_or_url && is_array( $args_or_url ) ) {
		$redirect = add_query_arg( $args_or_url, wp_get_referer() );
	} elseif ( $args_or_url && is_string( $args_or_url ) ) {
		$redirect = $args_or_url;
	} else {
		$redirect = wp_get_referer();
	}

	/**
	 * Filter the URL to redirect to.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 *
	 * @param string $redirect The URL to redirect to.
	 */
	$redirect = apply_filters( 'imagify_redirect_to', $redirect );

	if ( $message ) {
		if ( is_multisite() && strpos( $redirect, network_admin_url( '/' ) ) === 0 ) {
			Imagify_Notices::get_instance()->add_network_temporary_notice( $message );
		} else {
			Imagify_Notices::get_instance()->add_site_temporary_notice( $message );
		}
	}

	wp_safe_redirect( esc_url_raw( $redirect ) );
	die();
}

/**
 * Get cached Imagify user data.
 * This is usefull to prevent triggering an HTTP request to our server on every page load, but it can be used only where the data doesn't need to be in real time.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return object|bool An object on success. False otherwise.
 */
function imagify_get_cached_user() {
	if ( ! Imagify_Requirements::is_api_key_valid() ) {
		return false;
	}

	if ( imagify_is_active_for_network() ) {
		$user = get_site_transient( 'imagify_user' );
	} else {
		$user = get_transient( 'imagify_user' );
	}

	return is_object( $user ) ? $user : false;
}

/**
 * Cache Imagify user data for 5 minutes.
 * Runs every methods to store the results. Also stores formatted data like the quota and the next update date.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return object|bool An object on success. False otherwise.
 */
function imagify_cache_user() {
	if ( ! Imagify_Requirements::is_api_key_valid() ) {
		return false;
	}

	$user    = new Imagify_User();
	$data    = (object) get_object_vars( $user );
	$methods = get_class_methods( $user );

	foreach ( $methods as $method ) {
		if ( '__construct' !== $method ) {
			$data->$method = $user->$method();
		}
	}

	$data->quota_formatted            = imagify_size_format( $user->quota * pow( 1024, 2 ) );
	$data->next_date_update_formatted = date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) );

	if ( imagify_is_active_for_network() ) {
		set_site_transient( 'imagify_user', $data, 5 * MINUTE_IN_SECONDS );
	} else {
		set_transient( 'imagify_user', $data, 5 * MINUTE_IN_SECONDS );
	}

	return $data;
}
