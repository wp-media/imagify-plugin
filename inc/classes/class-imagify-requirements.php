<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class used to check that Imagify has everything it needs.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 */
class Imagify_Requirements {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Cache the test results.
	 *
	 * @var    object
	 * @access protected
	 * @since  1.7.1
	 * @author Grégory Viguier
	 */
	protected static $supports = array();


	/** ----------------------------------------------------------------------------------------- */
	/** SERVER ================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Test for cURL.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function supports_curl( $reset_cache = false ) {
		if ( $reset_cache || ! isset( self::$supports['curl'] ) ) {
			self::$supports['curl'] = function_exists( 'curl_init' ) && function_exists( 'curl_exec' );
		}

		return self::$supports['curl'];
	}

	/**
	 * Test for imageMagick and GD.
	 * Similar to _wp_image_editor_choose(), but allows to test for multiple mime types at once.
	 *
	 * @since  1.7.1
	 * @access public
	 * @see    _wp_image_editor_choose()
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function supports_image_editor( $reset_cache = false ) {
		if ( ! $reset_cache && isset( self::$supports['image_editor'] ) ) {
			return self::$supports['image_editor'];
		}

		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		self::$supports['image_editor'] = false;

		$args = array(
			'path'       => IMAGIFY_PATH . 'assets/images/imagify-logo.png',
			'mime_types' => imagify_get_mime_types( 'image' ),
			'methods'    => Imagify_Attachment::get_editor_methods(),
		);

		/** This filter is documented in /wp-includes/media.php. */
		$implementations = apply_filters( 'wp_image_editors', array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		foreach ( $implementations as $implementation ) {
			if ( ! call_user_func( array( $implementation, 'test' ), $args ) ) {
				continue;
			}

			foreach ( $args['mime_types'] as $mime_type ) {
				if ( ! call_user_func( array( $implementation, 'supports_mime_type' ), $mime_type ) ) {
					continue 2;
				}
			}

			if ( array_diff( $args['methods'], get_class_methods( $implementation ) ) ) {
				continue;
			}

			self::$supports['image_editor'] = true;
			break;
		}

		return self::$supports['image_editor'];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WORDPRESS =============================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Test for the uploads directory.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function supports_uploads( $reset_cache = false ) {
		if ( ! $reset_cache && isset( self::$supports['uploads'] ) ) {
			return self::$supports['uploads'];
		}

		self::$supports['uploads'] = Imagify_Filesystem::get_instance()->get_upload_basedir();

		if ( self::$supports['uploads'] ) {
			self::$supports['uploads'] = Imagify_Filesystem::get_instance()->is_writable( self::$supports['uploads'] );
		}

		return self::$supports['uploads'];
	}

	/**
	 * Test if external requests are blocked for Imagify.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function is_imagify_blocked( $reset_cache = false ) {
		if ( ! $reset_cache && isset( self::$supports['imagify_blocked'] ) ) {
			return self::$supports['imagify_blocked'];
		}

		if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) {
			self::$supports['imagify_blocked'] = false;
			return self::$supports['imagify_blocked'];
		}

		if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
			self::$supports['imagify_blocked'] = true;
			return self::$supports['imagify_blocked'];
		}

		$accessible_hosts = explode( ',', WP_ACCESSIBLE_HOSTS );
		$accessible_hosts = array_map( 'trim', $accessible_hosts );
		$accessible_hosts = array_flip( $accessible_hosts );

		if ( isset( $accessible_hosts['*.imagify.io'] ) ) {
			self::$supports['imagify_blocked'] = false;
			return self::$supports['imagify_blocked'];
		}

		if ( isset( $accessible_hosts['imagify.io'], $accessible_hosts['app.imagify.io'], $accessible_hosts['storage.imagify.io'] ) ) {
			self::$supports['imagify_blocked'] = false;
			return self::$supports['imagify_blocked'];
		}

		self::$supports['imagify_blocked'] = true;
		return self::$supports['imagify_blocked'];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFY BACKUP DIRECTORIES ============================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Test for the attachments backup directory.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function attachments_backup_dir_is_writable( $reset_cache = false ) {
		if ( $reset_cache || ! isset( self::$supports['attachment_backups'] ) ) {
			self::$supports['attachment_backups'] = imagify_backup_dir_is_writable();
		}

		return self::$supports['attachment_backups'];
	}

	/**
	 * Test for the custom folders backup directory.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function custom_folders_backup_dir_is_writable( $reset_cache = false ) {
		if ( $reset_cache || ! isset( self::$supports['custom_folder_backups'] ) ) {
			self::$supports['custom_folder_backups'] = Imagify_Custom_Folders::backup_dir_is_writable();
		}

		return self::$supports['custom_folder_backups'];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** IMAGIFY API ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Determine if the Imagify API is available by checking the API version.
	 * The result is cached for 3 minutes.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function is_api_up( $reset_cache = false ) {
		if ( ! $reset_cache && isset( self::$supports['api_up'] ) ) {
			return self::$supports['api_up'];
		}

		$transient_name       = 'imagify_check_api_version';
		$transient_expiration = 3 * MINUTE_IN_SECONDS;
		$transient_value      = $reset_cache ? false : get_site_transient( $transient_name );

		if ( false !== $transient_value ) {
			self::$supports['api_up'] = (bool) $transient_value;
			return self::$supports['api_up'];
		}

		self::$supports['api_up'] = ! is_wp_error( get_imagify_api_version() );
		$transient_value          = (int) self::$supports['api_up'];

		set_site_transient( $transient_name, $transient_value, $transient_expiration );

		return self::$supports['api_up'];
	}

	/**
	 * Test for the Imagify API key validity.
	 * A positive result is cached for 1 year.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function is_api_key_valid( $reset_cache = false ) {
		if ( $reset_cache ) {
			self::reset_cache( 'api_key_valid' );
		}

		if ( isset( self::$supports['api_key_valid'] ) ) {
			return self::$supports['api_key_valid'];
		}

		if ( ! Imagify_Options::get_instance()->get( 'api_key' ) ) {
			self::$supports['api_key_valid'] = false;
			return self::$supports['api_key_valid'];
		}

		if ( get_site_transient( 'imagify_check_licence_1' ) ) {
			self::$supports['api_key_valid'] = true;
			return self::$supports['api_key_valid'];
		}

		if ( is_wp_error( get_imagify_user() ) ) {
			self::$supports['api_key_valid'] = false;
			return self::$supports['api_key_valid'];
		}

		self::$supports['api_key_valid'] = true;
		set_site_transient( 'imagify_check_licence_1', 1, YEAR_IN_SECONDS );

		return self::$supports['api_key_valid'];
	}

	/**
	 * Test for the Imagify account quota.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $reset_cache True to get a fresh value.
	 * @return bool
	 */
	public static function is_over_quota( $reset_cache = false ) {
		if ( ! $reset_cache && isset( self::$supports['over_quota'] ) ) {
			return self::$supports['over_quota'];
		}

		$user = new Imagify_User();

		self::$supports['over_quota'] = $user->is_over_quota();

		return self::$supports['over_quota'];
	}


	/** ----------------------------------------------------------------------------------------- */
	/** CLASS CACHE ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Reset a test cache.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $cache_key Cache key.
	 */
	public static function reset_cache( $cache_key ) {
		unset( self::$supports[ $cache_key ] );

		$transients = array(
			'api_up'        => 'imagify_check_api_version',
			'api_key_valid' => 'imagify_check_licence_1',
		);

		if ( isset( $transients[ $cache_key ] ) && get_site_transient( $transients[ $cache_key ] ) ) {
			delete_site_transient( $transients[ $cache_key ] );
		}
	}
}
