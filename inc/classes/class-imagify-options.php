<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class that handles the plugin options.
 *
 * @since 1.6.13
 */
class Imagify_Options {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.6.13
	 */
	const VERSION = '1.0';

	/**
	 * Suffix used in the name of the option.
	 *
	 * @var    string
	 * @since  1.6.13
	 * @access protected
	 */
	protected $identifier = 'settings';

	/**
	 * The default values for the Imagify main options.
	 * These are the "zero state" values.
	 * Don't use null as value.
	 *
	 * @var    array
	 * @since  1.6.13
	 * @access protected
	 */
	protected $default_values = array(
		'version'            => '',
		'api_key'            => '',
		'optimization_level' => 0,
		'auto_optimize'      => 0,
		'backup'             => 0,
		'resize_larger'      => 0,
		'resize_larger_w'    => 0,
		'exif'               => 0,
		'disallowed-sizes'   => array(),
		'admin_bar_menu'     => 0,
	);

	/**
	 * The Imagify main option values used when they are set the first time or reset.
	 * Values identical to default values are not listed.
	 *
	 * @var    array
	 * @since  1.6.13
	 * @access protected
	 */
	protected $reset_values = array(
		'optimization_level' => 1,
		'auto_optimize'      => 1,
		'backup'             => 1,
		'admin_bar_menu'     => 1,
	);

	/**
	 * Tell if the option should be autoloaded by WP.
	 *
	 * @var    string
	 * @since  1.6.13
	 * @access protected
	 */
	protected $autoload = 'yes';

	/**
	 * Identifier used in the hook names.
	 *
	 * @var    string
	 * @since  1.6.13
	 * @access private
	 */
	private $hook_identifier;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.6.13
	 * @access protected
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected function __construct() {
		$this->hook_identifier = strtolower( str_replace( 'Imagify_', '', get_class( $this ) ) );

		if ( ! is_string( $this->autoload ) ) {
			$this->autoload = $this->autoload ? 'yes' : 'no';
		}
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.13
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
	 * @since  1.6.13
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
		}

		// If defined, use the constant for the API key.
		if ( 'api_key' === $key && defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
			$value = (string) IMAGIFY_API_KEY;
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

	/**
	 * Get all options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array The options.
	 */
	public function get_all() {
		$values = $this->get_raw();

		if ( ! $values ) {
			return $this->get_reset_values();
		}

		return self::merge_intersect( $values, $this->get_default_values() );
	}

	/**
	 * Set one or multiple options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $values An array of option name / option value pairs.
	 */
	public function set( $values ) {
		$args = func_get_args();

		if ( isset( $args[1] ) && is_string( $args[0] ) ) {
			$values = array( $args[0] => $args[1] );
		}

		if ( ! is_array( $values ) ) {
			// PABKAC.
			return;
		}

		$values = array_merge( (array) $this->get_raw(), $values );
		$values = array_intersect_key( $values, $this->get_default_values() );

		$this->set_raw( $values );
	}

	/**
	 * Delete one or multiple options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array|string $keys An array of option names or a single option name.
	 */
	public function delete( $keys ) {
		$values = $this->get_raw();

		if ( ! $values ) {
			if ( false !== $values ) {
				$this->delete_raw();
			}
			return;
		}

		$keys   = array_flip( (array) $keys );
		$values = array_diff_key( $values, $keys );

		$this->set_raw( $values );
	}

	/**
	 * Checks if the option with the given name exists or not.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $key The option name.
	 * @return bool
	 */
	public function has( $key ) {
		return null !== $this->get( $key );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GET / UPDATE / DELETE RAW VALUES ======================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the name of the option that stores the settings.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_option_name() {
		return IMAGIFY_SLUG . '_' . $this->identifier;
	}

	/**
	 * Get the identifier used in the hook names.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_hook_identifier() {
		return $this->hook_identifier;
	}

	/**
	 * Tell if the option is autoloaded.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_autoloaded() {
		return 'yes' === $this->autoload;
	}

	/**
	 * Get the raw value of all Imagify options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array|bool The options. False if not set yet or invalid.
	 */
	public function get_raw() {
		$values = imagify_is_active_for_network() ? get_site_option( $this->get_option_name() ) : get_option( $this->get_option_name() );

		if ( false !== $values && ! is_array( $values ) ) {
			return array();
		}

		return $values;
	}

	/**
	 * Update the Imagify options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $values An array of option name / option value pairs.
	 */
	public function set_raw( $values ) {
		if ( ! $values ) {
			// The option is empty: delete it.
			$this->delete_raw();

		} elseif ( imagify_is_active_for_network() ) {
			// Network option.
			update_site_option( $this->get_option_name(), $values );

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
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_raw() {
		imagify_is_active_for_network() ? delete_site_option( $this->get_option_name() ) : delete_option( $this->get_option_name() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DEFAULT + RESET VALUES ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get default option values.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_default_values() {
		/**
		 * Allow to add more default option values.
		 *
		 * @since  1.6.13
		 * @author Grégory Viguier
		 *
		 * @param array $new_values     New default option values.
		 * @param array $default_values Plugin default option values.
		 */
		$new_values = apply_filters( 'imagify_default_' . $this->get_hook_identifier() . '_values', array(), $this->default_values );
		$new_values = is_array( $new_values ) ? $new_values : array();

		if ( $new_values ) {
			// Don't allow new values to overwrite the plugin values.
			$new_values = array_diff_key( $new_values, $this->default_values );
		}

		if ( ! $new_values ) {
			return $this->default_values;
		}

		return array_merge( $this->default_values, $new_values );
	}

	/**
	 * Get the values used when the option is empty.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_reset_values() {
		$reset_values = array_merge( $this->get_default_values(), $this->reset_values );

		/**
		 * Allow to add more reset option values.
		 *
		 * @since  1.6.13
		 * @author Grégory Viguier
		 *
		 * @param array $new_values   New reset option values.
		 * @param array $reset_values Plugin reset option values.
		 */
		$new_values = apply_filters( 'imagify_reset_' . $this->get_hook_identifier() . '_values', array(), $reset_values );
		$new_values = is_array( $new_values ) ? $new_values : array();

		if ( $new_values ) {
			// Don't allow new values to overwrite the plugin values.
			$new_values = array_diff_key( $new_values, $this->default_values );
		}

		if ( ! $new_values ) {
			return $reset_values;
		}

		return array_merge( $reset_values, $new_values );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * `array_merge()` + `array_intersect_key()`.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $values  The array we're interested in.
	 * @param array $default The array we use as boundaries.
	 *
	 * @return array
	 */
	public static function merge_intersect( $values, $default ) {
		$values = array_merge( $default, (array) $values );
		return array_intersect_key( $values, $default );
	}
}
