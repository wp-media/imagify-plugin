<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get user capacity to operate Imagify.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 *
 * @param  bool $force_mono Force capacity for mono-site.
 * @return string
 */
function imagify_get_capacity( $force_mono = false ) {
	if ( $force_mono || ! is_multisite() ) {
		$capacity = 'manage_options';
	} else {
		$capacity = imagify_is_active_for_network() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Filter the user capacity used to operate Imagify.
	 *
	 * @since 1.0
	 * @since 1.6.5 Added $force_mono parameter.
	 *
	 * @param string $capacity   The user capacity.
	 * @param bool   $force_mono Force capacity for mono-site.
	 */
	return apply_filters( 'imagify_capacity', $capacity, $force_mono );
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
