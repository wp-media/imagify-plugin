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
	 * The option name.
	 *
	 * @var   string
	 * @since 1.6.13
	 */
	protected $option_name;

	/**
	 * The default option values.
	 * These are the "zero state" values. The main infos are not the values here, but their type.
	 *
	 * @var   array
	 * @since 1.6.13
	 */
	protected $default_values = array(
		'version'                       => '',
		'api_key'                       => '',
		'optimization_level'            => 0,
		'auto_optimize'                 => 0,
		'backup'                        => 0,
		'resize_larger'                 => 0,
		'resize_larger_w'               => 0,
		'exif'                          => 0,
		'disallowed-sizes'              => array(),
		'admin_bar_menu'                => 0,
		'total_size_images_library'     => array(),
		'average_size_images_per_month' => array(),
	);

	/**
	 * The option values used when they are set the first time or reset.
	 * Values identical to default values are not listed.
	 *
	 * @var   array
	 * @since 1.6.13
	 */
	protected $reset_values = array(
		'optimization_level' => 1,
		'auto_optimize'      => 1,
		'backup'             => 1,
		'admin_bar_menu'     => 1,
	);

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
		$this->option_name = IMAGIFY_SLUG . '_settings';
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
	/** ONE OPTION ============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get an Imagify option.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $key     The option name.
	 * @param  mixed  $default The default value of the option.
	 * @return mixed           The option value.
	 */
	public function get( $key, $default = null ) {
		/**
		 * Pre-filter any Imagify option before read.
		 *
		 * @since 1.0
		 *
		 * @param mixed $value   Value to return instead of the option value. Default null to skip it.
		 * @param mixed $default The default value. Default false.
		 */
		$value = apply_filters( 'pre_get_imagify_option_' . $key, null, $default );

		if ( isset( $value ) ) {
			return $value;
		}

		// Get the value.
		$options = $this->get_all();
		$default = $this->get_default_value( $key, $default );
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : $default;

		// Cast the value.
		if ( is_array( $default ) && ! is_array( $value ) ) {
			$value = array();
		} elseif ( is_int( $default ) && ! is_int( $value ) ) {
			$value = (int) $value;
		} elseif ( is_bool( $default ) && ! is_bool( $value ) ) {
			$value = (bool) $value;
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
		return apply_filters( 'get_imagify_option_' . $key, $value, $default );
	}

	/**
	 * Set an Imagify option.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param string $key   The option name.
	 * @param mixed  $value The value of the option.
	 */
	public function set( $key, $value ) {
		$options = $this->get_all();

		$options[ $key ] = $value;

		$this->set_all( $options );
	}

	/**
	 * Delete an Imagify option.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param string $key The option name.
	 */
	public function delete( $key ) {
		$options = $this->get_all();

		if ( ! isset( $options[ $key ] ) ) {
			return;
		}

		unset( $options[ $key ] );

		$this->set_all( $options );
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

	/**
	 * Get a default value.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param  string $key     The option name.
	 * @param  mixed  $default The default value of the option.
	 * @return mixed
	 */
	public function get_default_value( $key, $default = null ) {
		if ( isset( $default ) ) {
			return $default;
		}

		return isset( $this->default_values[ $key ] ) ? $this->default_values[ $key ] : $default;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** ALL OPTIONS ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get all Imagify options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array The options.
	 */
	public function get_all() {
		$options = $this->get_raw();
		$options = is_array( $options ) ? $options : array();

		if ( ! $options ) {
			return $this->get_reset_values();
		}

		return array_merge( $this->get_default_values(), $options );
	}

	/**
	 * Set an Imagify option.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @param array $options The new options.
	 */
	public function set_all( $options ) {
		if ( ! is_array( $options ) ) {
			// PABKAC.
			return;
		}

		if ( ! $options ) {
			$this->delete_all();
			return;
		}

		imagify_is_active_for_network() ? update_site_option( $this->get_option_name(), $options ) : update_option( $this->get_option_name(), $options );
	}

	/**
	 * Delete all Imagify options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 */
	public function delete_all() {
		if ( false === $this->get_raw() ) {
			return;
		}

		imagify_is_active_for_network() ? delete_site_option( $this->get_option_name() ) : delete_option( $this->get_option_name() );
	}

	/**
	 * Get the raw value of all Imagify options.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return mixed The options.
	 */
	public function get_raw() {
		return imagify_is_active_for_network() ? get_site_option( $this->get_option_name() ) : get_option( $this->get_option_name() );
	}

	/**
	 * Get the default values.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_default_values() {
		return $this->default_values;
	}

	/**
	 * Get the values used when they are set the first time.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return array
	 */
	public function get_reset_values() {
		return array_merge( $this->default_values, $this->reset_values );
	}

	/**
	 * Get the option name.
	 *
	 * @since  1.6.13
	 * @author Grégory Viguier
	 * @access public
	 *
	 * @return string
	 */
	public function get_option_name() {
		return $this->option_name;
	}
}
