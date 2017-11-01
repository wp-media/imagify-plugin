<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify.
 *
 * @since  1.6.5
 * @since  1.6.11 Uses a string as describer for the first argument.
 * @author Grégory Viguier
 *
 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @return string
 */
function imagify_get_capacity( $describer = 'manage' ) {
	static $edit_attachment_cap;

	// Back compat.
	if ( ! is_string( $describer ) ) {
		if ( $describer || ! is_multisite() ) {
			$describer = 'bulk-optimize';
		} else {
			$describer = 'manage';
		}
	}

	switch ( $describer ) {
		case 'manage':
			$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
			break;
		case 'bulk-optimize':
			$capacity = 'manage_options';
			break;
		case 'optimize':
			// This is a generic capacity: don't use it unless you have no other choices!
			if ( ! isset( $edit_attachment_cap ) ) {
				$edit_attachment_cap = get_post_type_object( 'attachment' );
				$edit_attachment_cap = $edit_attachment_cap ? $edit_attachment_cap->cap->edit_posts : 'edit_posts';
			}

			$capacity = $edit_attachment_cap;
			break;
		case 'manual-optimize':
			// Must be used with an Attachment ID.
			$capacity = 'edit_post';
			break;
		case 'auto-optimize':
			$capacity = 'upload_files';
			break;
		default:
			$capacity = $describer;
	}

	/**
	 * Filter the user capacity used to operate Imagify.
	 *
	 * @since 1.0
	 * @since 1.6.5  Added $force_mono parameter.
	 * @since 1.6.11 Replaced $force_mono by $describer.
	 *
	 * @param string $capacity  The user capacity.
	 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
	 */
	return apply_filters( 'imagify_capacity', $capacity, $describer );
}

/**
 * Tell if the current user has the required ability to operate Imagify.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
 * @param  int    $post_id   A post ID (a gallery ID for NGG).
 * @return bool
 */
function imagify_current_user_can( $describer = 'manage', $post_id = null ) {
	static $can_upload;

	$post_id  = $post_id ? $post_id : null;
	$capacity = imagify_get_capacity( $describer );
	$user_can = false;

	if ( 'manage' !== $describer && 'bulk-optimize' !== $describer ) {
		// Describers that are not 'manage' and 'bulk-optimize' need an additional test for 'upload_files'.
		if ( ! isset( $can_upload ) ) {
			$can_upload = current_user_can( 'upload_files' );
		}

		if ( $can_upload ) {
			if ( 'upload_files' === $capacity ) {
				// We already know it's true.
				$user_can = true;
			} else {
				$user_can = current_user_can( $capacity, $post_id );
			}
		}
	} else {
		$user_can = current_user_can( $capacity );
	}

	/**
	 * Filter the current user ability to operate Imagify.
	 *
	 * @since 1.6.11
	 *
	 * @param bool   $user_can  Tell if the current user has the required ability to operate Imagify.
	 * @param string $capacity  The user capacity.
	 * @param string $describer Capacity describer. Possible values are 'manage', 'bulk-optimize', 'manual-optimize', and 'auto-optimize'.
	 * @param int    $post_id   A post ID (a gallery ID for NGG).
	 */
	return apply_filters( 'imagify_current_user_can', $user_can, $capacity, $describer, $post_id );
}

/**
 * Sanitize an optimization context.
 *
 * @since  1.6.11
 * @author Grégory Viguier
 *
 * @param  string $context The context.
 * @return string
 */
function imagify_sanitize_context( $context ) {
	$context = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $context );
	return $context ? $context : 'wp';
}

/**
 * Classes autoloader.
 *
 * @since  1.6.12
 * @author Grégory Viguier
 *
 * @param string $class Name of the class to include.
 */
function imagify_autoload( $class ) {
	// Generic classes.
	$classes = array(
		'Imagify_Abstract_Attachment' => 1,
		'Imagify_Abstract_DB'         => 1,
		'Imagify_Admin_Ajax_Post'     => 1,
		'Imagify_Assets'              => 1,
		'Imagify_Attachment'          => 1,
		'Imagify_DB'                  => 1,
		'Imagify_Notices'             => 1,
		'Imagify_User'                => 1,
		'Imagify'                     => 1,
	);

	if ( isset( $classes[ $class ] ) ) {
		$class = str_replace( '_', '-', strtolower( $class ) );
		include IMAGIFY_CLASSES_PATH . 'class-' . $class . '.php';
		return;
	}

	// Third party classes.
	$classes = array(
		'Imagify_AS3CF_Attachment'     => 'amazon-s3-and-cloudfront',
		'Imagify_AS3CF'                => 'amazon-s3-and-cloudfront',
		'Imagify_Enable_Media_Replace' => 'enable-media-replace',
		'Imagify_Formidable_Pro'       => 'formidable-pro',
		'Imagify_NGG_Attachment'       => 'nextgen-gallery',
		'Imagify_NGG_DB'               => 'nextgen-gallery',
		'Imagify_NGG_Storage'          => 'nextgen-gallery',
		'Imagify_NGG'                  => 'nextgen-gallery',
	);

	if ( isset( $classes[ $class ] ) ) {
		$folder = $classes[ $class ];
		$class  = str_replace( '_', '-', strtolower( $class ) );
		include IMAGIFY_3RD_PARTY_PATH . $folder . '/inc/classes/class-' . $class . '.php';
	}
}

/**
 * Simple helper to get some external URLs, like to the documentation.
 *
 * @since  1.6.12
 * @author Grégory Viguier
 *
 * @param  string $target     What we want.
 * @param  array  $query_args An array of query arguments.
 * @return string The URL.
 */
function imagify_get_external_url( $target, $query_args = array() ) {
	$site_url = 'https://imagify.io/';
	$app_url  = 'https://app.imagify.io/#/';

	switch ( $target ) {
		case 'plugin':
			/* translators: Plugin URI of the plugin/theme */
			$url = __( 'https://wordpress.org/plugins/imagify/', 'imagify' );
			break;

		case 'rate':
			$url = 'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform';
			break;

		case 'share-twitter':
			$url = rawurlencode( imagify_get_external_url( 'plugin' ) );
			$url = 'https://twitter.com/intent/tweet?source=webclient&original_referer=' . $url . '&url=' . $url . '&related=imagify&hastags=performance,web,wordpress';
			break;

		case 'share-facebook':
			$url = rawurlencode( imagify_get_external_url( 'plugin' ) );
			$url = 'https://www.facebook.com/sharer/sharer.php?u=' . $url;
			break;

		case 'exif':
			/* translators: URL to a Wikipedia page explaining what EXIF means. */
			$url = __( 'https://en.wikipedia.org/wiki/Exchangeable_image_file_format', 'imagify' );
			break;

		case 'contact':
			$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$paths  = array(
				'default' => 'contact',
				'fr_FR'   => 'fr/contact',
			);

			$url = isset( $paths[ $locale ] ) ? $paths[ $locale ] : $paths['default'];
			$url = $site_url . $url . '/';
			break;

		case 'documentation':
			$url = $site_url . 'documentation/';
			break;

		case 'register':
			return $app_url . 'register';

		case 'subscription':
			return $app_url . 'subscription';

		case 'get-api-key':
			return $app_url . 'api';

		case 'payment':
			// Don't remove the trailing slash.
			return $app_url . 'plugin/';

		default:
			return '';
	}

	if ( $query_args ) {
		$url = add_query_arg( $query_args, $url );
	}

	return $url;
}
