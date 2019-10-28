<?php
namespace Imagify\Deprecated\Traits\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait containing deprecated methods of the class \Imagify\Media\NGG.
 *
 * @since
 * @author Grégory Viguier
 */
trait NGGDeprecatedTrait {

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
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '', '( new \Imagify\Media\NGG( $id ) )->get_fullsize_url()' );

		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( $this->get_cdn() ) {
			return $this->get_cdn()->get_file_url();
		}

		return ! empty( $this->image->imageURL ) ? $this->image->imageURL : false;
	}
}
