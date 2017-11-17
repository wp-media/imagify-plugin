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
		'total_size_images_library'     => 0.0,
		'average_size_images_per_month' => 0.0,
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
		static $max_sizes;

		if ( ! isset( $default ) ) {
			$default_values = $this->get_default_values();
			$default        = $default_values[ $key ];
		}

		// Cast the value.
		$value = self::cast( $value, $default );

		if ( $value === $default ) {
			return $value;
		}

		switch ( $key ) {
			case 'total_size_images_library':
			case 'average_size_images_per_month':
				if ( $value <= 0 ) {
					// Invalid.
					return 0.0;
				}
				return $value;
		}

		return false;
	}

	/**
	 * Sanitize and validate Imagify' options before storing them.
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

		/**
		 * Generic sanitization and validation.
		 */
		if ( $values ) {
			foreach ( $default_values as $key => $default ) {
				if ( ! isset( $values[ $key ] ) ) {
					continue;
				}

				$values[ $key ] = $this->sanitize_and_validate( $key, $values[ $key ], $default );

				// No need to store values equal to the default values.
				if ( $default === $values[ $key ] ) {
					unset( $values[ $key ] );
				}
			}
		}

		return array_intersect_key( $values, $default_values );
	}
}
