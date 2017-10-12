<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/** --------------------------------------------------------------------------------------------- */
/** PHP ========================================================================================= */
/** --------------------------------------------------------------------------------------------- */

if ( ! function_exists( 'curl_file_create' ) ) :
	/**
	 * PHP-agnostic version of curl_file_create(): create a CURLFile object.
	 *
	 * @since  1.0
	 * @since  PHP 5.5
	 * @source http://dk2.php.net/manual/en/function.curl-file-create.php
	 *
	 * @param  string $filename Path to the file which will be uploaded.
	 * @param  string $mimetype Mimetype of the file.
	 * @param  string $postname Name of the file to be used in the upload data.
	 * @return string           The CURLFile object.
	 */
	function curl_file_create( $filename, $mimetype = '', $postname = '' ) {
		return "@$filename;filename="
			. ( $postname ? $postname : basename( $filename ) )
			. ( $mimetype ? ";type=$mimetype" : '' );
	}
endif;

if ( ! function_exists( 'array_replace' ) ) :
	/**
	 * PHP-agnostic version of array_replace(): replaces elements from passed arrays into the first array.
	 *
	 * @since  1.6.9
	 * @since  PHP 5.3
	 * @source http://dk2.php.net/manual/en/function.array-replace.php
	 *
	 * @param  array $target       The array in which elements are replaced.
	 * @param  array $replacements The array from which elements will be extracted.
	 *                             More arrays from which elements will be extracted. Values from later arrays overwrite the previous values.
	 * @return array|null          The resulting array. Null if an error occurs.
	 */
	function array_replace( $target = array(), $replacements = array() ) {
		$replacements = func_get_args();
		array_shift( $replacements );

		foreach ( $replacements as $i => $add ) {
			if ( ! is_array( $add ) ) {
				trigger_error( __FUNCTION__ . '(): Argument #' . ( $i + 2 ) . ' is not an array', E_USER_WARNING );
				return null;
			}

			foreach ( $add as $k => $v ) {
				$target[ $k ] = $v;
			}
		}

		return $target;
	}
endif;

// SPL can be disabled on PHP 5.2.
if ( ! function_exists( 'spl_autoload_register' ) ) :
	$_wp_spl_autoloaders = array();

	/**
	 * Autoloader compatibility callback.
	 *
	 * @since  1.6.12
	 * @since  WP 4.6.0
	 * @source WordPress
	 *
	 * @param string $classname Class to attempt autoloading.
	 */
	function __autoload( $classname ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as $autoloader ) {
			if ( ! is_callable( $autoloader ) ) {
				// Avoid the extra warning if the autoloader isn't callable.
				continue;
			}

			call_user_func( $autoloader, $classname );

			// If it has been autoloaded, stop processing.
			if ( class_exists( $classname, false ) ) {
				return;
			}
		}
	}

	/**
	 * Registers a function to be autoloaded.
	 *
	 * @since  1.6.12
	 * @since  WP 4.6.0
	 * @source WordPress
	 *
	 * @throws Exception If the function to register is not callable.
	 *
	 * @param callable $autoload_function The function to register.
	 * @param bool     $throw             Optional. Whether the function should throw an exception
	 *                                    if the function isn't callable. Default true.
	 * @param bool     $prepend           Whether the function should be prepended to the stack.
	 *                                    Default false.
	 */
	function spl_autoload_register( $autoload_function, $throw = true, $prepend = false ) {
		if ( $throw && ! is_callable( $autoload_function ) ) {
			// String not translated to match PHP core.
			throw new Exception( 'Function not callable' );
		}

		global $_wp_spl_autoloaders;

		// Don't allow multiple registration.
		if ( in_array( $autoload_function, $_wp_spl_autoloaders, true ) ) {
			return;
		}

		if ( $prepend ) {
			array_unshift( $_wp_spl_autoloaders, $autoload_function );
		} else {
			$_wp_spl_autoloaders[] = $autoload_function;
		}
	}

	/**
	 * Unregisters an autoloader function.
	 *
	 * @since  1.6.12
	 * @since  WP 4.6.0
	 * @source WordPress
	 *
	 * @param callable $function The function to unregister.
	 * @return bool True if the function was unregistered, false if it could not be.
	 */
	function spl_autoload_unregister( $function ) {
		global $_wp_spl_autoloaders;
		foreach ( $_wp_spl_autoloaders as &$autoloader ) {
			if ( $autoloader === $function ) {
				unset( $autoloader );
				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the registered autoloader functions.
	 *
	 * @since  1.6.12
	 * @since  WP 4.6.0
	 * @source WordPress
	 *
	 * @return array List of autoloader functions.
	 */
	function spl_autoload_functions() {
		return $GLOBALS['_wp_spl_autoloaders'];
	}
endif;

/** --------------------------------------------------------------------------------------------- */
/** WORDPRESS =================================================================================== */
/** --------------------------------------------------------------------------------------------- */

if ( ! function_exists( 'wp_json_encode' ) ) :
	/**
	 * Encode a variable into JSON, with some sanity checks.
	 *
	 * @since 1.6.5
	 * @since WP 4.1.0
	 *
	 * @param  mixed $data    Variable (usually an array or object) to encode as JSON.
	 * @param  int   $options Optional. Options to be passed to json_encode(). Default 0.
	 * @param  int   $depth   Optional. Maximum depth to walk through $data. Must be greater than 0. Default 512.
	 * @return string|false  The JSON encoded string, or false if it cannot be encoded.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		/*
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
			$args = array( $data, $options, $depth );
		} elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			$args = array( $data, $options );
		} else {
			$args = array( $data );
		}

		// Prepare the data for JSON serialization.
		$args[0] = _wp_json_prepare_data( $data );

		$json = @call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) ) {
			return $json;
		}

		try {
			$args[0] = _wp_json_sanity_check( $data, $depth );
		} catch ( Exception $e ) {
			return false;
		}

		return call_user_func_array( 'json_encode', $args );
	}
endif;

if ( ! function_exists( '_wp_json_prepare_data' ) ) :
	/**
	 * Prepares response data to be serialized to JSON.
	 *
	 * This supports the JsonSerializable interface for PHP 5.2-5.3 as well.
	 *
	 * @since  1.6.5
	 * @since  WP 4.4.0
	 * @access private
	 *
	 * @param  mixed $data Native representation.
	 * @return bool|int|float|null|string|array Data ready for `json_encode()`.
	 */
	function _wp_json_prepare_data( $data ) {
		if ( ! defined( 'WP_JSON_SERIALIZE_COMPATIBLE' ) || WP_JSON_SERIALIZE_COMPATIBLE === false ) {
			return $data;
		}

		switch ( gettype( $data ) ) {
			case 'boolean':
			case 'integer':
			case 'double':
			case 'string':
			case 'NULL':
				// These values can be passed through.
				return $data;

			case 'array':
				// Arrays must be mapped in case they also return objects.
				return array_map( '_wp_json_prepare_data', $data );

			case 'object':
				// If this is an incomplete object (__PHP_Incomplete_Class), bail.
				if ( ! is_object( $data ) ) {
					return null;
				}

				if ( $data instanceof JsonSerializable ) {
					$data = $data->jsonSerialize();
				} else {
					$data = get_object_vars( $data );
				}

				// Now, pass the array (or whatever was returned from jsonSerialize through).
				return _wp_json_prepare_data( $data );

			default:
				return null;
		}
	}
endif;

if ( ! function_exists( '_wp_json_sanity_check' ) ) :
	/**
	 * Perform sanity checks on data that shall be encoded to JSON.
	 *
	 * @since  1.6.5
	 * @since  WP 4.1.0
	 * @access private
	 * @throws Exception If the depth limit is reached.
	 *
	 * @see wp_json_encode()
	 *
	 * @param  mixed $data  Variable (usually an array or object) to encode as JSON.
	 * @param  int   $depth Maximum depth to walk through $data. Must be greater than 0.
	 * @return mixed        The sanitized data that shall be encoded to JSON.
	 */
	function _wp_json_sanity_check( $data, $depth ) {
		if ( $depth < 0 ) {
			throw new Exception( 'Reached depth limit' );
		}

		if ( is_array( $data ) ) {
			$output = array();
			foreach ( $data as $id => $el ) {
				// Don't forget to sanitize the ID!
				if ( is_string( $id ) ) {
					$clean_id = _wp_json_convert_string( $id );
				} else {
					$clean_id = $id;
				}

				// Check the element type, so that we're only recursing if we really have to.
				if ( is_array( $el ) || is_object( $el ) ) {
					$output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
				} elseif ( is_string( $el ) ) {
					$output[ $clean_id ] = _wp_json_convert_string( $el );
				} else {
					$output[ $clean_id ] = $el;
				}
			}
		} elseif ( is_object( $data ) ) {
			$output = new stdClass();
			foreach ( $data as $id => $el ) {
				if ( is_string( $id ) ) {
					$clean_id = _wp_json_convert_string( $id );
				} else {
					$clean_id = $id;
				}

				if ( is_array( $el ) || is_object( $el ) ) {
					$output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
				} elseif ( is_string( $el ) ) {
					$output->$clean_id = _wp_json_convert_string( $el );
				} else {
					$output->$clean_id = $el;
				}
			}
		} elseif ( is_string( $data ) ) {
			return _wp_json_convert_string( $data );
		} else {
			return $data;
		} // End if().

		return $output;
	}
endif;

if ( ! function_exists( '_wp_json_convert_string' ) ) :
	/**
	 * Convert a string to UTF-8, so that it can be safely encoded to JSON.
	 *
	 * @since  1.6.5
	 * @since  WP 4.1.0
	 * @access private
	 *
	 * @see _wp_json_sanity_check()
	 *
	 * @staticvar bool $use_mb
	 *
	 * @param  string $string The string which is to be converted.
	 * @return string The checked string.
	 */
	function _wp_json_convert_string( $string ) {
		static $use_mb = null;
		if ( is_null( $use_mb ) ) {
			$use_mb = function_exists( 'mb_convert_encoding' );
		}

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $string, mb_detect_order(), true );
			if ( $encoding ) {
				return mb_convert_encoding( $string, 'UTF-8', $encoding );
			} else {
				return mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );
			}
		} else {
			return wp_check_invalid_utf8( $string, true );
		}
	}
endif;

if ( ! function_exists( 'wp_normalize_path' ) ) :
	/**
	 * Normalize a filesystem path.
	 *
	 * On windows systems, replaces backslashes with forward slashes
	 * and forces upper-case drive letters.
	 * Allows for two leading slashes for Windows network shares, but
	 * ensures that all other duplicate slashes are reduced to a single.
	 *
	 * @since 1.6.7
	 * @since WP 3.9.0
	 * @since WP 4.4.0 Ensures upper-case drive letters on Windows systems.
	 * @since WP 4.5.0 Allows for Windows network shares.
	 *
	 * @param  string $path Path to normalize.
	 * @return string Normalized path.
	 */
	function wp_normalize_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}
		return $path;
	}
endif;

if ( ! function_exists( 'wp_parse_url' ) ) :
	/**
	 * A wrapper for PHP's parse_url() function that handles consistency in the return
	 * values across PHP versions.
	 *
	 * PHP 5.4.7 expanded parse_url()'s ability to handle non-absolute url's, including
	 * schemeless and relative url's with :// in the path. This function works around
	 * those limitations providing a standard output on PHP 5.2~5.4+.
	 *
	 * Secondly, across various PHP versions, schemeless URLs starting containing a ":"
	 * in the query are being handled inconsistently. This function works around those
	 * differences as well.
	 *
	 * Error suppression is used as prior to PHP 5.3.3, an E_WARNING would be generated
	 * when URL parsing failed.
	 *
	 * @since 1.6.9
	 * @since WP 4.4.0
	 * @since WP 4.7.0 The $component parameter was added for parity with PHP's parse_url().
	 *
	 * @param (string) $url       The URL to parse.
	 * @param (int)    $component The specific component to retrieve. Use one of the PHP
	 *                            predefined constants to specify which one.
	 *                            Defaults to -1 (= return all parts as an array).
	 *                            @see http://php.net/manual/en/function.parse-url.php
	 *
	 * @return (mixed) False on parse failure; Array of URL components on success;
	 *                 When a specific component has been requested: null if the component
	 *                 doesn't exist in the given URL; a sting or - in the case of
	 *                 PHP_URL_PORT - integer when it does. See parse_url()'s return values.
	 */
	function wp_parse_url( $url, $component = -1 ) {
		$to_unset = array();
		$url = strval( $url );

		if ( '//' === substr( $url, 0, 2 ) ) {
			$to_unset[] = 'scheme';
			$url = 'placeholder:' . $url;
		} elseif ( '/' === substr( $url, 0, 1 ) ) {
			$to_unset[] = 'scheme';
			$to_unset[] = 'host';
			$url = 'placeholder://placeholder' . $url;
		}

		$parts = @parse_url( $url );

		if ( false === $parts ) {
			// Parsing failure.
			return $parts;
		}

		// Remove the placeholder values.
		if ( $to_unset ) {
			foreach ( $to_unset as $key ) {
				unset( $parts[ $key ] );
			}
		}

		return _get_component_from_parsed_url_array( $parts, $component );
	}
endif;

if ( ! function_exists( '_get_component_from_parsed_url_array' ) ) :
	/**
	 * Retrieve a specific component from a parsed URL array.
	 *
	 * @since 1.6.9
	 * @since WP 4.7.0
	 *
	 * @param (array|false) $url_parts The parsed URL. Can be false if the URL failed to parse.
	 * @param (int)         $component The specific component to retrieve. Use one of the PHP
	 *                                 predefined constants to specify which one.
	 *                                 Defaults to -1 (= return all parts as an array).
	 * @see http://php.net/manual/en/function.parse-url.php
	 *
	 * @return (mixed) False on parse failure; Array of URL components on success;
	 *                 When a specific component has been requested: null if the component
	 *                 doesn't exist in the given URL; a sting or - in the case of
	 *                 PHP_URL_PORT - integer when it does. See parse_url()'s return values.
	 */
	function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
		if ( -1 === $component ) {
			return $url_parts;
		}

		$key = _wp_translate_php_url_constant_to_key( $component );

		if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
			return $url_parts[ $key ];
		} else {
			return null;
		}
	}
endif;

if ( ! function_exists( '_wp_translate_php_url_constant_to_key' ) ) :
	/**
	 * Translate a PHP_URL_* constant to the named array keys PHP uses.
	 *
	 * @since 1.6.9
	 * @since WP 4.7.0
	 * @see   http://php.net/manual/en/url.constants.php
	 *
	 * @param (int) $constant PHP_URL_* constant.
	 *
	 * @return (string|bool) The named key or false.
	 */
	function _wp_translate_php_url_constant_to_key( $constant ) {
		$translation = array(
			PHP_URL_SCHEME   => 'scheme',
			PHP_URL_HOST     => 'host',
			PHP_URL_PORT     => 'port',
			PHP_URL_USER     => 'user',
			PHP_URL_PASS     => 'pass',
			PHP_URL_PATH     => 'path',
			PHP_URL_QUERY    => 'query',
			PHP_URL_FRAGMENT => 'fragment',
		);

		if ( isset( $translation[ $constant ] ) ) {
			return $translation[ $constant ];
		} else {
			return false;
		}
	}
endif;

if ( ! function_exists( 'wp_get_additional_image_sizes' ) ) :
	/**
	 * Retrieve additional image sizes.
	 *
	 * @since 1.6.10
	 * @since WP 4.7.0
	 *
	 * @global array $_wp_additional_image_sizes
	 *
	 * @return array Additional images size data.
	 */
	function wp_get_additional_image_sizes() {
		global $_wp_additional_image_sizes;
		if ( ! $_wp_additional_image_sizes ) {
			$_wp_additional_image_sizes = array(); // WPCS: override ok.
		}
		return $_wp_additional_image_sizes;
	}
endif;

if ( ! function_exists( 'doing_filter' ) ) :
	/**
	 * Retrieve the name of a filter currently being processed.
	 *
	 * The function current_filter() only returns the most recent filter or action
	 * being executed. did_action() returns true once the action is initially
	 * processed.
	 *
	 * This function allows detection for any filter currently being
	 * executed (despite not being the most recent filter to fire, in the case of
	 * hooks called from hook callbacks) to be verified.
	 *
	 * @since 1.6.11
	 * @since WP 3.9.0
	 *
	 * @see current_filter()
	 * @see did_action()
	 * @global array $wp_current_filter Current filter.
	 *
	 * @param null|string $filter Optional. Filter to check. Defaults to null, which
	 *                            checks if any filter is currently being run.
	 * @return bool Whether the filter is currently in the stack.
	 */
	function doing_filter( $filter = null ) {
		global $wp_current_filter;

		if ( null === $filter ) {
			return ! empty( $wp_current_filter );
		}

		return in_array( $filter, $wp_current_filter, true );
	}
endif;

if ( ! function_exists( 'wp_scripts' ) ) :
	/**
	 * Initialize $wp_scripts if it has not been set.
	 *
	 * @global WP_Scripts $wp_scripts
	 *
	 * @since 1.6.11
	 * @since WP 4.2.0
	 *
	 * @return WP_Scripts WP_Scripts instance.
	 */
	function wp_scripts() {
		global $wp_scripts;
		if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
			$wp_scripts = new WP_Scripts(); // WPCS: override ok.
		}
		return $wp_scripts;
	}
endif;
