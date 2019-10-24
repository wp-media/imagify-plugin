<?php
namespace Imagify\Deprecated\Traits\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait containing deprecated methods of the class \Imagify\Media\Noop.
 *
 * @since
 * @author Grégory Viguier
 */
trait NoopDeprecatedTrait {

	/**
	 * Get the original media's URL.
	 *
	 * @since  1.9
	 * @since   Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_original_url() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '', '( new \Imagify\Media\Noop( $id ) )->get_fullsize_url()' );

		return false;
	}
}
