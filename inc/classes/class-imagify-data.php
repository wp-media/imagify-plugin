<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles the plugin data.
 *
 * @since 1.7
 */
class Imagify_Data extends Imagify_Options {

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
		'total_size_images_library'     => array(),
		'average_size_images_per_month' => array(),
	);

	/**
	 * The Imagify main option values used when they are set the first time or reset.
	 * Values identical to default values are not listed.
	 *
	 * @var    array
	 * @since  1.7
	 * @access protected
	 */
	protected $reset_values = array();

	/**
	 * Tell if the option should be autoloaded by WP.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $autoload = 'no';

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
	/** ONE OPTION OR DATA ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get an Imagify option.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $key The option name.
	 * @return mixed       The option value.
	 */
	public function get( $key ) {
		$default_values = $this->get_default_values();

		if ( ! isset( $default_values[ $key ] ) ) {
			return null;
		}

		$default = $default_values[ $key ];

		/**
		 * Pre-filter any Imagify option before read.
		 *
		 * @since 1.0
		 *
		 * @param mixed $value   Value to return instead of the option value. Default null to skip it.
		 * @param mixed $default The default value.
		 */
		$value = apply_filters( 'pre_get_imagify_' . $this->get_hook_identifier() . '_' . $key, null, $default );

		if ( isset( $value ) ) {
			return $value;
		}

		// Get the value.
		$values = $this->get_all();
		$value  = $values[ $key ];

		// Cast the value.
		if ( is_array( $default ) && ! is_array( $value ) ) {
			$value = array();
		} elseif ( is_int( $default ) ) {
			$value = (int) $value;
		} elseif ( is_bool( $default ) ) {
			$value = (bool) $value;
		}

		/**
		 * Filter any Imagify option after read.
		 *
		 * @since 1.0
		 *
		 * @param mixed $value   Value of the option.
		 * @param mixed $default The default value. Default false.
		*/
		return apply_filters( 'get_imagify_' . $this->get_hook_identifier() . '_' . $key, $value, $default );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GET / UPDATE / DELETE RAW VALUES ======================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the raw value of all Imagify options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array|bool The options. False if not set yet or invalid.
	 */
	public function get_raw() {
		$values = get_option( $this->get_option_name() );

		if ( false !== $values && ! is_array( $values ) ) {
			return array();
		}

		return $values;
	}

	/**
	 * Update the Imagify options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $values An array of option name / option value pairs.
	 */
	public function set_raw( $values ) {
		if ( ! $values ) {
			// The option is empty: delete it.
			$this->delete_raw();

		} elseif ( false === get_option( $this->get_option_name() ) ) {
			// Compat' with WP < 4.2 + autoload: the option doesn't exist in the database.
			add_option( $this->get_option_name(), $values, '', $this->autoload );
		} else {
			// Update the current value.
			update_option( $this->get_option_name(), $values, $this->autoload );
		}
	}

	/**
	 * Delete all Imagify options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_raw() {
		delete_option( $this->get_option_name() );
	}
}
