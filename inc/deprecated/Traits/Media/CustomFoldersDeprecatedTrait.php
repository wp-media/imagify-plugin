<?php
namespace Imagify\Deprecated\Traits\Media;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait containing deprecated methods of the class \Imagify\Media\CustomFolders.
 *
 * @since  1.9.8
 * @author Grégory Viguier
 */
trait CustomFoldersDeprecatedTrait {

	/**
	 * Get the original media's URL.
	 *
	 * @since  1.9
	 * @since  1.9.8 Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return string|bool The file URL. False on failure.
	 */
	public function get_original_url() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9.8', '( new \Imagify\Media\CustomFolders( $id ) )->get_fullsize_url()' );

		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( $this->get_cdn() ) {
			return $this->get_cdn()->get_file_url();
		}

		$row = $this->get_row();

		if ( ! $row || empty( $row['path'] ) ) {
			return false;
		}

		return \Imagify_Files_Scan::remove_placeholder( $row['path'], 'url' );
	}
}
