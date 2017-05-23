<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Returns the main instance of Imagify to prevent the need to use globals.
 *
 * @since 1.5
 * @since 1.6.5 Deprecated.
 * @author Jonathan Buttigieg
 *
 * @return object A Imagify_NGG_DB object.
 */
function Imagify_NGG_DB() {
	_deprecated_function( __FUNCTION__, '1.6.5', 'imagify_ngg_db()' );
	return imagify_ngg_db();
}
