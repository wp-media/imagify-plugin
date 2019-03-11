<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_Notices.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Notices_Deprecated {

	/**
	 * Include the view file.
	 *
	 * @since  1.6.10 In Imagify_Notices
	 * @since  1.7 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $view The view ID.
	 * @param mixed  $data Some data to pass to the view.
	 */
	public function render_view( $view, $data = array() ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'notice-\' . $view, $data )' );

		Imagify_Views::get_instance()->print_template( 'notice-' . $view, $data );
	}
}
