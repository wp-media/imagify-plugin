<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the plugin data.
 *
 * @since 1.7
 */
class Imagify_Data extends Imagify_Abstract_Options {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.0';

	/**
	 * Suffix used in the name of the option.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $identifier = 'data';

	/**
	 * The default values for the Imagify main options.
	 * These are the "zero state" values.
	 * Don't use null as value.
	 *
	 * @var    array
	 * @since  1.7
	 * @access protected
	 */
	protected $default_values = array(
		'total_size_images_library'     => 0.0,
		'average_size_images_per_month' => 0.0,
		'previous_quota_percent'        => 0.0,
	);

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SANITIZATION, VALIDATION ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Sanitize and validate an option value. Basic casts have been made.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $key     The option key.
	 * @param  mixed  $value   The value.
	 * @param  mixed  $default The default value.
	 * @return mixed
	 */
	public function sanitize_and_validate_value( $key, $value, $default ) {
		switch ( $key ) {
			case 'total_size_images_library':
			case 'average_size_images_per_month':
				if ( $value <= 0 ) {
					// Invalid.
					return 0.0;
				}
				return $value;

			case 'previous_quota_percent':
				$value = round( $value, 1 );
				return min( max( 0, $value ), 100 );
		}

		return false;
	}
}
