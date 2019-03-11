<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_Abstract_DB.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Abstract_DB_Deprecated {

	/**
	 * Check if the given table exists.
	 *
	 * @since  1.5 In Imagify_Abstract_DB.
	 * @since  1.7 Deprecated.
	 * @access public
	 * @deprecated
	 *
	 * @param  string $table The table name.
	 * @return bool          True if the table name exists.
	 */
	public function table_exists( $table ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.7.0', 'Imagify_DB::table_exists( $table )' );

		return Imagify_DB::table_exists( $table );
	}

	/**
	 * Main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 * Well, actually it ensures nothing since it's not a full singleton pattern.
	 *
	 * @since  1.5 In Imagify_NGG_DB.
	 * @since  1.7 Deprecated.
	 * @access public
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @return object Main instance.
	 */
	public static function instance() {
		_deprecated_function( 'Imagify_Abstract_DB::instance()', '1.6.5', 'Imagify_Abstract_DB::get_instance()' );

		return self::get_instance();
	}
}
