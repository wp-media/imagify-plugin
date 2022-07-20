<?php

/**
 * Class that handles the plugin options.
 *
 * @since 1.7
 */
class Imagify_Options extends Imagify_Abstract_Options {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.1';

	/**
	 * Suffix used in the name of the option.
	 *
	 * @var    string
	 * @since  1.7
	 */
	protected $identifier = 'settings';

	/**
	 * The default values for the Imagify main options.
	 * These are the "zero state" values.
	 * Don't use null as value.
	 *
	 * @var    array
	 * @since  1.7
	 */
	protected $default_values = array(
		'api_key'             => '',
		'optimization_level'  => 2,
		'auto_optimize'       => 0,
		'backup'              => 0,
		'resize_larger'       => 0,
		'resize_larger_w'     => 0,
		'convert_to_webp'     => 0,
		'display_webp'        => 0,
		'display_webp_method' => 'picture',
		'cdn_url'             => '',
		'disallowed-sizes'    => array(),
		'admin_bar_menu'      => 0,
		'partner_links'       => 0,
	);

	/**
	 * The Imagify main option values used when they are set the first time or reset.
	 * Values identical to default values are not listed.
	 *
	 * @var    array
	 * @since  1.7
	 */
	protected $reset_values = array(
		'optimization_level' => 2,
		'auto_optimize'      => 1,
		'backup'             => 1,
		'convert_to_webp'    => 1,
		'admin_bar_menu'     => 1,
		'partner_links'      => 1,
	);

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 */
	protected static $_instance;

	/**
	 * The constructor.
	 * Side note: $this->hook_identifier value is "option".
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		if ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
			$this->default_values['api_key'] = (string) IMAGIFY_API_KEY;
		}

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$this->reset_values['resize_larger'] = 1;

			$filter_cb = [ imagify_get_context( 'wp' ), 'get_resizing_threshold' ];
			$filtered  = has_filter( 'big_image_size_threshold', $filter_cb );

			if ( $filtered ) {
				remove_filter( 'big_image_size_threshold', $filter_cb, IMAGIFY_INT_MAX );
			}

			/** This filter is documented in wp-admin/includes/image.php */
			$this->reset_values['resize_larger_w'] = (int) apply_filters( 'big_image_size_threshold', 2560, [ 0, 0 ], '', 0 );
			$this->reset_values['resize_larger_w'] = $this->sanitize_and_validate_value( 'resize_larger_w', $this->reset_values['resize_larger_w'], $this->default_values['resize_larger_w'] );

			if ( $filtered ) {
				add_filter( 'big_image_size_threshold', $filter_cb, IMAGIFY_INT_MAX );
			}
		}

		$this->network_option = imagify_is_active_for_network();

		parent::__construct();
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
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
	 *
	 * @param  string $key     The option key.
	 * @param  mixed  $value   The value.
	 * @param  mixed  $default The default value.
	 * @return mixed
	 */
	public function sanitize_and_validate_value( $key, $value, $default ) {
		static $max_sizes;

		switch ( $key ) {
			case 'api_key':
				if ( defined( 'IMAGIFY_API_KEY' ) && IMAGIFY_API_KEY ) {
					return (string) IMAGIFY_API_KEY;
				}
				return $value ? sanitize_key( $value ) : '';

			case 'optimization_level':
				if ( $value < 0 || $value > 2 ) {
					// For an invalid value, return the "reset" value.
					$reset_values = $this->get_reset_values();
					return $reset_values[ $key ];
				}
				return $value;

			case 'auto_optimize':
			case 'backup':
			case 'resize_larger':
			case 'convert_to_webp':
			case 'display_webp':
			case 'admin_bar_menu':
			case 'partner_links':
				return 1;

			case 'resize_larger_w':
				if ( $value <= 0 ) {
					// Invalid.
					return $default;
				}
				if ( ! isset( $max_sizes ) ) {
					$max_sizes = get_imagify_max_intermediate_image_size();
				}
				if ( $value < $max_sizes['width'] ) {
					// Invalid.
					return $max_sizes['width'];
				}
				return $value;

			case 'disallowed-sizes':
				if ( ! $value ) {
					return $default;
				}

				$value = array_keys( $value );
				$value = array_map( 'sanitize_text_field', $value );
				return array_fill_keys( $value, 1 );

			case 'display_webp_method':
				$values = [
					'picture' => 1,
					'rewrite' => 1,
				];
				if ( isset( $values[ $value ] ) ) {
					return $value;
				}
				// For an invalid value, return the "reset" value.
				$reset_values = $this->get_reset_values();
				return $reset_values[ $key ];

			case 'cdn_url':
				$cdn_source = \Imagify\Webp\Picture\Display::get_instance()->get_cdn_source( $value );

				if ( 'option' !== $cdn_source['source'] ) {
					/**
					 * If the URL is defined via constant or filter, unset the option.
					 * This is useful when the CDN is disabled: there is no need to do anything then.
					 */
					return '';
				}

				return $cdn_source['url'];
		}

		return false;
	}

	/**
	 * Validate Imagify's options before storing them. Basic sanitization and validation have been made, row by row.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 *
	 * @param  string $values The option value.
	 * @return array
	 */
	public function validate_values_on_update( $values ) {
		// The max width for the "Resize larger images" option can't be 0.
		if ( empty( $values['resize_larger_w'] ) ) {
			unset( $values['resize_larger'], $values['resize_larger_w'] );
		}

		// Don't display wepb if conversion is disabled.
		if ( empty( $values['convert_to_webp'] ) ) {
			unset( $values['convert_to_webp'], $values['display_webp'] );
		}

		return $values;
	}
}
