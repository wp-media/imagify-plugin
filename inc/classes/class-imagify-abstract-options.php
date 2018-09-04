<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Abstract class to handle a part of the plugin options.
 *
 * @since 1.7
 */
abstract class Imagify_Abstract_Options {

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
	protected $identifier;

	/**
	 * The default values for the Imagify main options.
	 * These are the "zero state" values.
	 * Don't use null as value.
	 *
	 * @var    array
	 * @since  1.7
	 * @access protected
	 */
	protected $default_values;

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
	 * Possible values are 'yes' and 'no'.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $autoload = 'yes';

	/**
	 * Tell if the option should be a network option.
	 *
	 * @var    bool
	 * @since  1.7
	 * @access protected
	 */
	protected $network_option = false;

	/**
	 * Identifier used in the hook names.
	 *
	 * @var    string
	 * @since  1.7
	 * @access private
	 */
	private $hook_identifier;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected function __construct() {
		$this->hook_identifier = rtrim( strtolower( str_replace( 'Imagify_', '', get_class( $this ) ) ), 's' );

		if ( ! is_string( $this->autoload ) ) {
			$this->autoload = $this->autoload ? 'yes' : 'no';
		}

		$this->default_values = array_merge( array(
			'version' => '',
		), $this->default_values );
	}

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

	/**
	 * Launch the hooks.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function init() {
		add_filter( 'sanitize_option_' . $this->get_option_name(), array( $this, 'sanitize_and_validate_on_update' ), 50 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GET/SET/DELETE OPTION(S) ================================================================ */
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

		// Get all values.
		$values = $this->get_all();

		// Sanitize and validate the value.
		$value = $this->sanitize_and_validate( $key, $values[ $key ], $default );

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
	 * Get all options (no cast, no sanitization, no validation).
	 *
	 * @since  1.7
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

		return imagify_merge_intersect( $values, $this->get_default_values() );
	}

	/**
	 * Set one or multiple options.
	 *
	 * @since  1.7
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

		$values = array_merge( $this->get_all(), $values );
		$values = array_intersect_key( $values, $this->get_default_values() );

		$this->set_raw( $values );
	}

	/**
	 * Delete one or multiple options.
	 *
	 * @since  1.7
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
	 * @since  1.7
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
	 * @since  1.7
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
	 * @since  1.7
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
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_autoloaded() {
		return 'yes' === $this->autoload;
	}

	/**
	 * Tell if the option is a network option.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return bool
	 */
	public function is_network_option() {
		return (bool) $this->network_option;
	}

	/**
	 * Get the raw value of all Imagify options.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array|bool The options. False if not set yet. An empty array if invalid.
	 */
	public function get_raw() {
		$values = $this->is_network_option() ? get_site_option( $this->get_option_name() ) : get_option( $this->get_option_name() );

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

		} elseif ( $this->is_network_option() ) {
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
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_raw() {
		$this->is_network_option() ? delete_site_option( $this->get_option_name() ) : delete_option( $this->get_option_name() );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** DEFAULT + RESET VALUES ================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get default option values.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_default_values() {
		$default_values = $this->default_values;

		if ( ! empty( $default_values['cached'] ) ) {
			unset( $default_values['cached'] );
			return $default_values;
		}

		/**
		 * Allow to add more default option values.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $new_values     New default option values.
		 * @param array $default_values Plugin default option values.
		 */
		$new_values = apply_filters( 'imagify_default_' . $this->get_hook_identifier() . '_values', array(), $default_values );
		$new_values = is_array( $new_values ) ? $new_values : array();

		if ( $new_values ) {
			// Don't allow new values to overwrite the plugin values.
			$new_values = array_diff_key( $new_values, $default_values );
		}

		if ( $new_values ) {
			$default_values       = array_merge( $default_values, $new_values );
			$this->default_values = $default_values;
		}

		$this->default_values['cached'] = 1;

		return $default_values;
	}

	/**
	 * Get the values used when the option is empty.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_reset_values() {
		$reset_values = $this->reset_values;

		if ( ! empty( $reset_values['cached'] ) ) {
			unset( $reset_values['cached'] );
			return $reset_values;
		}

		$default_values = $this->get_default_values();
		$reset_values   = array_merge( $default_values, $reset_values );

		/**
		 * Allow to filter the "reset" option values.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $reset_values Plugin reset option values.
		 */
		$new_values = apply_filters( 'imagify_reset_' . $this->get_hook_identifier() . '_values', $reset_values );

		if ( $new_values && is_array( $new_values ) ) {
			$reset_values = array_merge( $reset_values, $new_values );
		}

		$this->reset_values = $reset_values;
		$this->reset_values['cached'] = 1;

		return $reset_values;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SANITIZATION, VALIDATION ================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Sanitize and validate an option value.
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
	public function sanitize_and_validate( $key, $value, $default = null ) {
		if ( ! isset( $default ) ) {
			$default_values = $this->get_default_values();
			$default        = $default_values[ $key ];
		}

		// Cast the value.
		$value = self::cast( $value, $default );

		if ( $value === $default ) {
			return $value;
		}

		// Version.
		if ( 'version' === $key ) {
			return sanitize_text_field( $value );
		}

		return $this->sanitize_and_validate_value( $key, $value, $default );
	}

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
	abstract public function sanitize_and_validate_value( $key, $value, $default );

	/**
	 * Sanitize and validate Imagify's options before storing them.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $values The option value.
	 * @return array
	 */
	public function sanitize_and_validate_on_update( $values ) {
		$values         = is_array( $values ) ? $values : array();
		$default_values = $this->get_default_values();

		if ( $values ) {
			foreach ( $default_values as $key => $default ) {
				if ( isset( $values[ $key ] ) ) {
					$values[ $key ] = $this->sanitize_and_validate( $key, $values[ $key ], $default );
				}
			}
		}

		$values = array_intersect_key( $values, $default_values );

		// Version.
		if ( empty( $values['version'] ) ) {
			$values['version'] = IMAGIFY_VERSION;
		}

		return $this->validate_values_on_update( $values );
	}

	/**
	 * Validate Imagify's options before storing them. Basic sanitization and validation have been made, row by row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $values The option value.
	 * @return array
	 */
	public function validate_values_on_update( $values ) {
		return $values;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Cast a value, depending on its default value type.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  mixed $value   The value to cast.
	 * @param  mixed $default The default value.
	 * @return mixed
	 */
	public static function cast( $value, $default ) {
		if ( is_array( $default ) ) {
			return is_array( $value ) ? $value : array();
		}

		if ( is_int( $default ) ) {
			return (int) $value;
		}

		if ( is_bool( $default ) ) {
			return (bool) $value;
		}

		if ( is_float( $default ) ) {
			return round( (float) $value, 3 );
		}

		return $value;
	}

	/**
	 * Cast a float like 3.000 into an integer.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  float $float The float.
	 * @return float|int
	 */
	public static function maybe_cast_float_as_int( $float ) {
		return ( $float / (int) $float ) === (float) 1 ? (int) $float : $float;
	}
}
