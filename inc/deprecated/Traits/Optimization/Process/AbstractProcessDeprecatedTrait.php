<?php
namespace Imagify\Deprecated\Traits\Optimization\Process;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait containing deprecated methods of the class \Imagify\Optimization\Process\AbstractProcess.
 *
 * @since
 * @author Grégory Viguier
 */
trait AbstractProcessDeprecatedTrait {

	/**
	 * Get the File instance.
	 *
	 * @since  1.9
	 * @since   Deprecated
	 * @access public
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return File|false
	 */
	public function get_file() {
		$full_class = get_class( $this );
		$class_name = explode( '\\', trim( $full_class, '\\' ) );
		$class_name = end( $class_name );

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '', '( new \Imagify\Optimization\Process\\' . $class_name . '( $id ) )->get_fullsize_file()' );

		if ( isset( $this->file ) ) {
			return $this->file;
		}

		$this->file = false;

		if ( $this->get_media() ) {
			$this->file = new File( $this->get_media()->get_raw_fullsize_path() );
		}

		return $this->file;
	}
}
