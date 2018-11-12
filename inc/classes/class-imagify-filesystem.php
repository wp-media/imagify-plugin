<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

/**
 * Class that enhance the WP filesystem class.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 */
class Imagify_Filesystem extends WP_Filesystem_Direct {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.2';

	/**
	 * Delimiter used for regex patterns.
	 *
	 * @var    string
	 * @since  1.8
	 * @author Grégory Viguier
	 */
	const PATTERN_DELIMITER = '@';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @access protected
	 */
	protected static $_instance;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCIATION =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Constructor.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function __construct() {
		// Define the permission constants if not already done.
		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
		}
		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
		}

		parent::__construct( '' );
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7.1
	 * @access public
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
	/** CUSTOM TOOLS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the file name.
	 * Replacement for basename().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return string|bool       The base name of the given path. False on failure.
	 */
	public function file_name( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		return wp_basename( $file_path );
	}

	/**
	 * Get the parent directory's path.
	 * Replacement for dirname().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return string|bool       The directory path with a trailing slash. False on failure.
	 */
	public function dir_path( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		$file_path = dirname( $file_path );

		return $this->is_root( $file_path ) ? $this->get_root() : trailingslashit( $file_path );
	}

	/**
	 * Get information about a file path.
	 * Replacement for pathinfo().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @param  string $option    If present, specifies a specific element to be returned; one of 'dir_path', 'file_name', 'extension' or 'file_base'.
	 *                           If option is not specified, returns all available elements.
	 * @return array|string|null If the option parameter is not passed, an associative array containing the following elements is returned: 'dir_path' (with trailing slash), 'file_name' (with extension), 'extension' (if any), and 'file_base' (without extension).
	 */
	public function path_info( $file_path, $option = null ) {
		if ( ! $file_path ) {
			if ( isset( $option ) ) {
				return '';
			}

			return array(
				'dir_path'  => '',
				'file_name' => '',
				'extension' => null,
				'file_base' => '',
			);
		}

		if ( isset( $option ) ) {
			$options = array(
				'dir_path'  => PATHINFO_DIRNAME,
				'file_name' => PATHINFO_BASENAME,
				'extension' => PATHINFO_EXTENSION,
				'file_base' => PATHINFO_FILENAME,
			);

			if ( ! isset( $options[ $option ] ) ) {
				return '';
			}

			$output = pathinfo( $file_path, $options[ $option ] );

			if ( 'dir_path' !== $option ) {
				return $output;
			}

			return $this->is_root( $output ) ? $this->get_root() : trailingslashit( $output );
		}

		$output = pathinfo( $file_path );

		$output['dirname']   = $this->is_root( $output['dirname'] ) ? $this->get_root()    : trailingslashit( $output['dirname'] );
		$output['extension'] = isset( $output['extension'] )        ? $output['extension'] : null;

		// '/www/htdocs/inc/lib.inc.php'
		return array(
			'dir_path'  => $output['dirname'],   // '/www/htdocs/inc/'
			'file_name' => $output['basename'],  // 'lib.inc.php'
			'extension' => $output['extension'], // 'php'
			'file_base' => $output['filename'],  // 'lib.inc'
		);
	}

	/**
	 * Recursive directory creation based on full path. Will attempt to set permissions on folders.
	 * Replacement for recursive mkdir().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $path Full path to attempt to create.
	 * @return bool         Whether the path was created. True if path already exists.
	 */
	public function make_dir( $path ) {
		/*
		* Safe mode fails with a trailing slash under certain PHP versions.
		*/
		$path = untrailingslashit( wp_normalize_path( $path ) );

		if ( $this->is_root( $path ) ) {
			return $this->is_dir( $this->get_root() ) && $this->is_writable( $this->get_root() );
		}

		if ( $this->exists( $path ) ) {
			return $this->is_dir( $path ) && $this->is_writable( $path );
		}

		$site_root = $this->get_site_root();

		if ( strpos( $path, $site_root ) !== 0 ) {
			return false;
		}

		$bits = str_replace( $site_root, '', $path );
		$bits = explode( '/', $bits );
		$path = untrailingslashit( $site_root );

		foreach ( $bits as $bit ) {
			$parent_path = $path;
			$path       .= '/' . $bit;

			if ( $this->exists( $path ) ) {
				if ( ! $this->is_dir( $path ) ) {
					return false;
				}
				continue;
			}

			if ( ! $this->is_writable( $parent_path ) ) {
				$this->chmod_dir( $parent_path );

				if ( ! $this->is_writable( $parent_path ) ) {
					return false;
				}
			}

			$this->mkdir( $path );

			if ( ! $this->exists( $path ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Set a file permissions using FS_CHMOD_FILE.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool              True on success, false on failure.
	 */
	public function chmod_file( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		return $this->chmod( $file_path, FS_CHMOD_FILE );
	}

	/**
	 * Set a directory permissions using FS_CHMOD_DIR.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the directory.
	 * @return bool              True on success, false on failure.
	 */
	public function chmod_dir( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		return $this->chmod( $file_path, FS_CHMOD_DIR );
	}

	/**
	 * Get a file mime type.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path A file path (prefered) or a filename.
	 * @return string|bool       A mime type. False on failure: the test is limited to mime types supported by Imagify.
	 */
	public function get_mime_type( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		$file_type = wp_check_filetype( $file_path, imagify_get_mime_types() );

		return $file_type['type'];
	}

	/**
	 * Get a file modification date, formated as "mysql". Fallback to current date.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return string            The date.
	 */
	public function get_date( $file_path ) {
		static $offset;

		if ( ! $file_path ) {
			return current_time( 'mysql' );
		}

		$date = $this->mtime( $file_path );

		if ( ! $date ) {
			return current_time( 'mysql' );
		}

		if ( ! isset( $offset ) ) {
			$offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}

		return gmdate( 'Y-m-d H:i:s', $date + $offset );
	}

	/**
	 * Tell if a file is symlinked.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path An absolute path.
	 * @return bool
	 */
	public function is_symlinked( $file_path ) {
		static $site_root;
		static $plugin_paths = array();
		global $wp_plugin_paths;

		if ( ! $file_path ) {
			return false;
		}

		$real_path = realpath( $file_path );

		if ( ! $real_path ) {
			return false;
		}

		if ( ! isset( $site_root ) ) {
			$site_root = $this->normalize_path_for_comparison( $this->get_site_root() );
		}

		$lower_file_path = $this->normalize_path_for_comparison( $real_path );

		if ( strpos( $lower_file_path, $site_root ) !== 0 ) {
			return true;
		}

		if ( $wp_plugin_paths && is_array( $wp_plugin_paths ) ) {
			if ( ! $plugin_paths ) {
				foreach ( $wp_plugin_paths as $dir => $real_dir ) {
					$dir = $this->normalize_path_for_comparison( $dir );
					$plugin_paths[ $dir ] = $this->normalize_path_for_comparison( $real_dir );
				}
			}

			$lower_file_path = $this->normalize_path_for_comparison( $file_path );

			foreach ( $plugin_paths as $dir => $real_dir ) {
				if ( strpos( $lower_file_path, $dir ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Tell if a file is a pdf.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool
	 */
	public function is_pdf( $file_path ) {
		if ( function_exists( 'finfo_fopen' ) ) {
			$finfo = finfo_open( FILEINFO_MIME );

			if ( $finfo ) {
				$mimetype = finfo_file( $finfo, $file_path );

				if ( false !== $mimetype ) {
					return 'application/pdf' === $mimetype;
				}
			}
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mimetype = mime_content_type( $file_path );
			return 'application/pdf' === $mimetype;
		}

		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** CLASS OVERWRITES ======================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Move a file and apply chmod.
	 * If the file failed to be moved once, a 2nd attempt is made after applying chmod.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $source      Path to the file to move.
	 * @param  string $destination Path to the destination.
	 * @param  bool   $overwrite   Allow to overwrite existing file at destination.
	 * @return bool                True on success, false on failure.
	 */
	public function move( $source, $destination, $overwrite = false ) {
		if ( parent::move( $source, $destination, $overwrite ) ) {
			return $this->chmod_file( $destination );
		}

		if ( ! $this->chmod_file( $destination ) ) {
			return false;
		}

		if ( parent::move( $source, $destination, $overwrite ) ) {
			return $this->chmod_file( $destination );
		}

		return false;
	}

	/**
	 * Determine if a file or directory is writable.
	 * This function is used to work around certain ACL issues in PHP primarily affecting Windows Servers.
	 * Replacement for is_writable().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool
	 */
	public function is_writable( $file_path ) {
		if ( ! $file_path ) {
			return false;
		}

		return wp_is_writable( $file_path );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WORK WITH IMAGES ======================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a file is an image.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return bool
	 */
	public function is_image( $file_path ) {
		if ( function_exists( 'finfo_fopen' ) ) {
			$finfo = finfo_open( FILEINFO_MIME );

			if ( $finfo ) {
				$mimetype = finfo_file( $finfo, $file_path );

				if ( false !== $mimetype ) {
					return strpos( $mimetype, 'image/' ) === 0;
				}
			}
		}

		if ( function_exists( 'exif_imagetype' ) ) {
			$mimetype = exif_imagetype( $file_path );
			return (bool) $mimetype;
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mimetype = mime_content_type( $file_path );
			return strpos( $mimetype, 'image/' ) === 0;
		}

		return false;
	}

	/**
	 * Get an image data.
	 * Replacement for getimagesize().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path Path to the file.
	 * @return array             The image data. An empty array on failure.
	 */
	public function get_image_size( $file_path ) {
		if ( ! $file_path ) {
			return array();
		}

		$size = @getimagesize( $file_path );

		if ( ! $size || ! isset( $size[0], $size[1] ) ) {
			return array();
		}

		return array(
			0          => (int) $size[0],
			1          => (int) $size[1],
			'width'    => (int) $size[0],
			'height'   => (int) $size[1],
			'type'     => (int) $size[2],
			'attr'     => $size[3],
			'channels' => isset( $size['channels'] ) ? (int) $size['channels'] : null,
			'bits'     => isset( $size['bits'] )     ? (int) $size['bits']     : null,
			'mime'     => $size['mime'],
		);
	}

	/**
	 * Tell if exif_read_data() is available.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_get_exif() {
		static $callable;

		if ( ! isset( $callable ) ) {
			$callable = is_callable( 'exif_read_data' );
		}

		return $callable;
	}

	/**
	 * Get the EXIF headers from an image file.
	 * Replacement for exif_read_data().
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 * @see    https://secure.php.net/manual/en/function.exif-read-data.php
	 *
	 * @param  string $file_path Path to the file.
	 * @param  string $sections  A comma separated list of sections that need to be present in file to produce a result array. See exif_read_data() documentation for values: FILE, COMPUTED, ANY_TAG, IFD0, THUMBNAIL, COMMENT, EXIF.
	 * @param  bool   $arrays    Specifies whether or not each section becomes an array. The sections COMPUTED, THUMBNAIL, and COMMENT always become arrays as they may contain values whose names conflict with other sections.
	 * @param  bool   $thumbnail When set to TRUE the thumbnail itself is read. Otherwise, only the tagged data is read.
	 * @return array             The EXIF headers. An empty array on failure.
	 */
	public function get_image_exif( $file_path, $sections = null, $arrays = false, $thumbnail = false ) {
		if ( ! $file_path || ! $this->can_get_exif() ) {
			return array();
		}

		$exif = @exif_read_data( $file_path, $sections, $arrays, $thumbnail );

		return is_array( $exif ) ? $exif : array();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** WORK WITH PATHS ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Make an absolute path relative to WordPress' root folder.
	 * Also works for files from registered symlinked plugins.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path An absolute path.
	 * @param  string $base      A base path to use instead of ABSPATH.
	 * @return string|bool       A relative path. Can return the absolute path or false in case of a failure.
	 */
	public function make_path_relative( $file_path, $base = '' ) {
		global $wp_plugin_paths;

		if ( ! $file_path ) {
			return false;
		}

		$file_path = wp_normalize_path( $file_path );
		$base      = $base ? $this->normalize_dir_path( $base ) : $this->get_site_root();
		$pos       = strpos( $file_path, $base );

		if ( false === $pos && $wp_plugin_paths && is_array( $wp_plugin_paths ) ) {
			// The file is probably part of a symlinked plugin.
			arsort( $wp_plugin_paths );

			foreach ( $wp_plugin_paths as $dir => $real_dir ) {
				if ( strpos( $file_path, $real_dir ) === 0 ) {
					$file_path = wp_normalize_path( $dir . substr( $file_path, strlen( $real_dir ) ) );
				}
			}

			$pos = strpos( $file_path, $base );
		}

		if ( false === $pos ) {
			// We're in trouble.
			return $file_path;
		}

		return substr_replace( $file_path, '', 0, $pos + strlen( $base ) );
	}

	/**
	 * Normalize a directory path.
	 * The path is normalized and a trailing slash is added.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string            The normalized dir path.
	 */
	public function normalize_dir_path( $file_path ) {
		return wp_normalize_path( trailingslashit( $file_path ) );
	}

	/**
	 * Normalize a file path, aiming for path comparison.
	 * The path is normalized, case-lowered, and a trailing slash is added.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string            The normalized file path.
	 */
	public function normalize_path_for_comparison( $file_path ) {
		return strtolower( $this->normalize_dir_path( $file_path ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** SOME WELL KNOWN PATHS AND URLS ========================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if WordPress is installed in its own directory: aka WP's path !== site's path.
	 *
	 * @since  1.8.1
	 * @access public
	 * @see    https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function has_wp_its_own_directory() {
		return $this->get_abspath() !== $this->get_site_root();
	}

	/**
	 * The path to the server's root is not always '/', it can also be '//' or 'C://'.
	 * I am get_root.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The path to the server's root.
	 */
	public function get_root() {
		static $groot;

		if ( isset( $groot ) ) {
			return $groot;
		}

		$groot = preg_replace( '@^((?:.:)?/+).*@', '$1', $this->get_site_root() );

		return $groot;
	}

	/**
	 * Tell if a path is the server's root.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $path The path.
	 * @return bool
	 */
	public function is_root( $path ) {
		$path = rtrim( $path, '/\\' );
		return '.' === $path || '' === $path || preg_match( '@^.:$@', $path );
	}

	/**
	 * Get the path to the site's root.
	 * This is an improved version of get_home_path() that *should* work in almost every cases.
	 * Because creating a constant like ABSPATH was too simple.
	 *
	 * @since  1.8.1
	 * @access public
	 * @see    get_home_path()
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_site_root() {
		static $root_path;

		if ( isset( $root_path ) ) {
			return $root_path;
		}

		/**
		 * Filter the path to the site's root.
		 *
		 * @since  1.8.1
		 * @author Grégory Viguier
		 *
		 * @param string $root_path Path to the site's root. Default is null.
		 */
		$root_path = apply_filters( 'imagify_site_root', null );

		if ( is_string( $root_path ) ) {
			$root_path = trailingslashit( wp_normalize_path( $root_path ) );

			return $root_path;
		}

		$home    = set_url_scheme( untrailingslashit( get_option( 'home' ) ), 'http' );
		$siteurl = set_url_scheme( untrailingslashit( get_option( 'siteurl' ) ), 'http' );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos                 = strripos( str_replace( '\\', '/', ABSPATH ), trailingslashit( $wp_path_rel_to_home ) );
			$root_path           = substr( ABSPATH, 0, $pos );
			$root_path           = trailingslashit( wp_normalize_path( $root_path ) );
			return $root_path;
		}

		if ( ! defined( 'PATH_CURRENT_SITE' ) || ! is_multisite() || is_main_site() ) {
			$root_path = $this->get_abspath();
			return $root_path;
		}

		/**
		 * For a multisite in its own directory, get_home_path() returns the expected path only for the main site.
		 *
		 * Friend, each time an attempt is made to improve this method, and especially this part, please increment the following counter.
		 * Improvement attempts: 3.
		 */
		$document_root     = realpath( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ); // `realpath()` is needed for those cases where $_SERVER['DOCUMENT_ROOT'] is totally different from ABSPATH.
		$document_root     = trailingslashit( str_replace( '\\', '/', $document_root ) );
		$path_current_site = trim( str_replace( '\\', '/', PATH_CURRENT_SITE ), '/' );
		$root_path         = trailingslashit( wp_normalize_path( $document_root . $path_current_site ) );

		return $root_path;
	}

	/**
	 * Get the URL of the site's root. It corresponds to the main site's home page URL.
	 *
	 * @since  1.8.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_site_root_url() {
		static $root_url;

		if ( isset( $root_url ) ) {
			return $root_url;
		}

		if ( ! is_multisite() || is_main_site() ) {
			$root_url = home_url( '/' );
			return $root_url;
		}

		$current_network = false;

		if ( function_exists( 'get_network' ) ) {
			$current_network = get_network();
		} elseif ( function_exists( 'get_current_site' ) ) {
			$current_network = get_current_site();
		}

		if ( ! $current_network ) {
			$root_url = home_url( '/' );
			return $root_url;
		}

		$root_url = is_ssl() ? 'https' : 'http';
		$root_url = set_url_scheme( 'http://' . $current_network->domain . $current_network->path, $root_url );
		$root_url = trailingslashit( $root_url );

		return $root_url;
	}

	/**
	 * Tell if a path is the site's root.
	 *
	 * @since  1.8.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $path The path.
	 * @return bool
	 */
	public function is_site_root( $path ) {
		return $this->normalize_dir_path( $path ) === $this->get_site_root();
	}

	/**
	 * Get a clean value of ABSPATH.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The path to WordPress' root folder.
	 */
	public function get_abspath() {
		static $abspath;

		if ( isset( $abspath ) ) {
			return $abspath;
		}

		$abspath = wp_normalize_path( ABSPATH );

		// Make sure ABSPATH is not messed up: it could be defined as a relative path for example (yeah, I know, but we've seen it).
		$test_file = wp_normalize_path( IMAGIFY_FILE );
		$pos       = strpos( $test_file, $abspath );

		if ( $pos > 0 ) {
			// ABSPATH has a wrong value.
			$abspath = substr( $test_file, 0, $pos ) . $abspath;

		} elseif ( false === $pos && class_exists( 'ReflectionClass' ) ) {
			// Imagify is symlinked (dude, you look for trouble).
			$reflector = new ReflectionClass( 'WP' );
			$test_file = $reflector->getFileName();
			$pos       = strpos( $test_file, $abspath );

			if ( 0 < $pos ) {
				// ABSPATH has a wrong value.
				$abspath = substr( $test_file, 0, $pos ) . $abspath;
			}
		}

		$abspath = trailingslashit( $abspath );

		if ( '/' !== substr( $abspath, 0, 1 ) && ':' !== substr( $abspath, 1, 1 ) ) {
			$abspath = '/' . $abspath;
		}

		return $abspath;
	}

	/**
	 * Tell if a path is WP's root (ABSPATH).
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $path The path.
	 * @return bool
	 */
	public function is_abspath( $path ) {
		return $this->normalize_dir_path( $path ) === $this->get_abspath();
	}

	/**
	 * Get the upload basedir.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $bypass_error True to return the path even if there is an error. This is used when we want to display this path in a message for example.
	 * @return string|bool        The path. False on failure.
	 */
	public function get_upload_basedir( $bypass_error = false ) {
		static $upload_basedir;
		static $upload_basedir_or_error;

		if ( isset( $upload_basedir ) ) {
			return $bypass_error ? $upload_basedir : $upload_basedir_or_error;
		}

		$uploads        = wp_upload_dir();
		$upload_basedir = $this->normalize_dir_path( $uploads['basedir'] );

		if ( false !== $uploads['error'] ) {
			$upload_basedir_or_error = false;
		} else {
			$upload_basedir_or_error = $upload_basedir;
		}

		return $bypass_error ? $upload_basedir : $upload_basedir_or_error;
	}

	/**
	 * Get the upload baseurl.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool The URL. False on failure.
	 */
	public function get_upload_baseurl() {
		static $upload_baseurl;

		if ( isset( $upload_baseurl ) ) {
			return $upload_baseurl;
		}

		$uploads = wp_upload_dir();

		if ( false !== $uploads['error'] ) {
			$upload_baseurl = false;
			return $upload_baseurl;
		}

		$upload_baseurl = trailingslashit( $uploads['baseurl'] );

		return $upload_baseurl;
	}

	/**
	 * Get the path to the uploads base directory of the main site.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_main_upload_basedir() {
		static $basedir;

		if ( isset( $basedir ) ) {
			return $basedir;
		}

		$basedir = get_imagify_upload_basedir( true );

		if ( is_multisite() ) {
			$pattern = '/' . $this->get_multisite_uploads_subdir_pattern() . '$';
			$basedir = preg_replace( self::PATTERN_DELIMITER . $pattern . self::PATTERN_DELIMITER, '/', $basedir );
		}

		return $basedir;
	}

	/**
	 * Get the URL of the uploads base directory of the main site.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_main_upload_baseurl() {
		static $baseurl;

		if ( isset( $baseurl ) ) {
			return $baseurl;
		}

		$baseurl = get_imagify_upload_baseurl( true );

		if ( is_multisite() ) {
			$pattern = '/' . $this->get_multisite_uploads_subdir_pattern() . '$';
			$baseurl = preg_replace( self::PATTERN_DELIMITER . $pattern . self::PATTERN_DELIMITER, '/', $baseurl );
		}

		return $baseurl;
	}

	/**
	 * Get the regex pattern used to match the uploads subdir on multisite in a file path.
	 * Pattern delimiter is `Imagify_Filesystem::PATTERN_DELIMITER`.
	 * Paths tested against these patterns are lower-cased.
	 *
	 * @since  1.8
	 * @access public
	 * @see    _wp_upload_dir()
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_multisite_uploads_subdir_pattern() {
		static $pattern;

		if ( isset( $pattern ) ) {
			return $pattern;
		}

		$pattern = '';

		if ( ! is_multisite() ) {
			return $pattern;
		}

		if ( ! get_site_option( 'ms_files_rewriting' ) ) {
			if ( defined( 'MULTISITE' ) ) {
				$pattern = 'sites/\d+/';
			} else {
				$pattern = '\d+/';
			}
		} elseif ( defined( 'UPLOADS' ) ) {
			$site_id = (string) get_current_blog_id();
			$path    = $this->get_upload_basedir( true ); // Something like `/absolute/path/to/wp-content/blogs.dir/3/files/`, also for site 1.
			$path    = strrev( $path );

			if ( preg_match( self::PATTERN_DELIMITER . '^.*' . strrev( $site_id ) . '[^/]*/' . self::PATTERN_DELIMITER . 'U', $path, $matches ) ) {
				$pattern = end( $matches );
				$pattern = ltrim( strtolower( strrev( $pattern ) ), '/' );
				$pattern = str_replace( $site_id, '\d+', $pattern );
			}
		}

		/**
		 * Filter the regex pattern used to match the uploads subdir on multisite in a file path.
		 * Pattern delimiter is `Imagify_Filesystem::PATTERN_DELIMITER`.
		 * Important: lowercase, no heading slash, mandatory trailing slash.
		 *
		 * @since  1.8
		 * @author Grégory Viguier
		 *
		 * @param string $pattern The regex pattern.
		 */
		$pattern = apply_filters( 'imagify_multisite_uploads_subdir_pattern', $pattern );

		return $pattern;
	}
}
