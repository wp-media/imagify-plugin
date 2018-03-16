<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class handling everything that is related to "custom folders optimization".
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Files_Scan {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0.1';

	/**
	 * Get files (optimizable by Imagify) recursively from a specific folder.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $folder An absolute path to a folder.
	 * @return array|object   An array of absolute paths. A WP_Error object on error.
	 */
	public static function get_files_from_folder( $folder ) {
		$filesystem = imagify_get_filesystem();

		// Formate and validate the folder path.
		if ( ! is_string( $folder ) ) {
			return new WP_Error( 'invalid_folder', __( 'Invalid folder.', 'imagify' ) );
		}

		$folder = realpath( $folder );

		if ( ! $folder ) {
			return new WP_Error( 'folder_not_exists', __( 'This folder does not exist.', 'imagify' ) );
		}

		if ( ! $filesystem->is_dir( $folder ) ) {
			return new WP_Error( 'not_a_folder', __( 'This file is not a folder.', 'imagify' ) );
		}

		if ( self::is_path_forbidden( $folder ) ) {
			return new WP_Error( 'folder_forbidden', __( 'This folder is not allowed.', 'imagify' ) );
		}

		// Finally we made all our validations.
		if ( $filesystem->is_abspath( $folder ) ) {
			// For the site's root, we don't look in sub-folders.
			$dir    = new DirectoryIterator( $folder );
			$dir    = new Imagify_Files_Iterator( $dir, false );
			$images = array();

			foreach ( new IteratorIterator( $dir ) as $file ) {
				$images[] = $file->getPathname();
			}

			return $images;
		}

		/**
		 * 4096 stands for FilesystemIterator::SKIP_DOTS, which was introduced in php 5.3.0.
		 * 8192 stands for FilesystemIterator::UNIX_PATHS, which was introduced in php 5.3.0.
		 */
		$dir    = new RecursiveDirectoryIterator( $folder, 4096 | 8192 );
		$dir    = new Imagify_Files_Recursive_Iterator( $dir );
		$images = new RecursiveIteratorIterator( $dir );
		$images = array_keys( iterator_to_array( $images ) );

		return $images;
	}

	/**
	 * Tell if a path is autorized.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path A file or folder absolute path.
	 * @return bool
	 */
	public static function is_path_autorized( $file_path ) {
		return ! self::is_path_forbidden( $file_path );
	}

	/**
	 * Tell if a path is forbidden.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path A file or folder absolute path.
	 * @return bool
	 */
	public static function is_path_forbidden( $file_path ) {
		static $folders;

		$filesystem = imagify_get_filesystem();

		if ( self::is_filename_forbidden( $filesystem->file_name( $file_path ) ) ) {
			return true;
		}

		if ( $filesystem->is_symlinked( $file_path ) ) {
			// Files outside the site's folder are forbidden.
			return true;
		}

		if ( ! isset( $folders ) ) {
			$folders = self::get_forbidden_folders();
			$folders = array_map( 'strtolower', $folders );
			$folders = array_flip( $folders );
		}

		$file_path = self::normalize_path_for_comparison( $file_path );

		if ( isset( $folders[ $file_path ] ) ) {
			return true;
		}

		if ( self::get_forbidden_folder_patterns() ) {
			foreach ( self::get_forbidden_folder_patterns() as $pattern ) {
				if ( preg_match( '@^' . $pattern . '@', $file_path ) ) {
					return true;
				}
			}
		}

		foreach ( $folders as $folder ) {
			if ( strpos( $file_path, $folder ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the list of folders where Imagify won't look for files to optimize.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array A list of absolute paths.
	 */
	public static function get_forbidden_folders() {
		static $folders;

		if ( isset( $folders ) ) {
			return $folders;
		}

		$filesystem = imagify_get_filesystem();
		$folders    = array(
			// Server.
			$filesystem->get_abspath() . 'cgi-bin',        // `cgi-bin`
			// WordPress.
			$filesystem->get_abspath() . 'wp-admin',       // `wp-admin`
			$filesystem->get_abspath() . WPINC,            // `wp-includes`
			get_imagify_upload_basedir( true ),            // Media library.
			WP_CONTENT_DIR . '/languages',                 // Translations.
			WP_CONTENT_DIR . '/mu-plugins',                // MU plugins.
			WP_CONTENT_DIR . '/upgrade',                   // Upgrade.
			// Plugins.
			WP_CONTENT_DIR . '/backups',                   // A folder commonly used by backup plugins.
			WP_CONTENT_DIR . '/cache',                     // A folder commonly used by cache plugins.
			WP_CONTENT_DIR . '/bps-backup',                // BulletProof Security.
			WP_CONTENT_DIR . '/ngg',                       // NextGen Gallery.
			WP_CONTENT_DIR . '/ngg_styles',                // NextGen Gallery.
			WP_CONTENT_DIR . '/w3tc-config',               // W3 Total Cache.
			WP_CONTENT_DIR . '/wfcache',                   // WP Fastest Cache.
			WP_CONTENT_DIR . '/wp-rocket-config',          // WP Rocket.
			Imagify_Custom_Folders::get_backup_dir_path(), // Imagify "Custom folders" backup.
			IMAGIFY_PATH,                                  // Imagify plugin.
			self::get_wc_logs_path(),                      // WooCommerce Logs.
			self::get_ewww_tools_path(),                   // EWWW.
		);

		// NextGen Gallery.
		if ( ! is_multisite() ) {
			$folders[] = self::get_ngg_galleries_path();
		}

		$folders = array_map( 'trailingslashit', $folders );
		$folders = array_map( 'wp_normalize_path', $folders );

		/**
		 * Add folders to the list of forbidden ones.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $added_folders List of absolute paths.
		 * @param array $folders       List of folders already forbidden.
		 */
		$added_folders = apply_filters( 'imagify_add_forbidden_folders', array(), $folders );
		$added_folders = array_filter( (array) $added_folders );
		$added_folders = array_filter( $added_folders, 'is_string' );

		if ( ! $added_folders ) {
			return $folders;
		}

		$added_folders = array_map( 'trailingslashit', $added_folders );
		$added_folders = array_map( 'wp_normalize_path', $added_folders );

		$folders = array_merge( $folders, $added_folders );
		$folders = array_flip( array_flip( $folders ) );

		return $folders;
	}

	/**
	 * Get the list of folder patterns where Imagify won't look for files to optimize.
	 * `^` will be prepended to each pattern (aka, the pattern must match an absolute path). Pattern delimiter is `@`. Paths tested against these patterns are lower-cased.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array A list of regex patterns.
	 */
	public static function get_forbidden_folder_patterns() {
		static $folders;

		if ( isset( $folders ) ) {
			return $folders;
		}

		$folders = array();

		// NextGen Gallery.
		if ( is_multisite() ) {
			$folders[] = self::get_ngg_galleries_path();
		}

		if ( $folders ) {
			$folders = array_map( 'trailingslashit', $folders );
			$folders = array_map( 'wp_normalize_path', $folders );
			$folders = array_map( 'preg_quote', $folders, array_fill( 1, count( $folders ), '@' ) );

			// Must be done after `wp_normalize_path()` and `preg_quote()`.
			foreach ( $folders as $i => $folder ) {
				$folders[ $i ] = str_replace( '%BLOG_ID%', '\d+', $folder );
			}
		}

		/**
		 * Add folder patterns to the list of forbidden ones.
		 * Don't forget to use `trailingslashit()`, `wp_normalize_path()` and `preg_quote()`!
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $added_folders List of patterns.
		 * @param array $folders       List of patterns already forbidden.
		 */
		$added_folders = apply_filters( 'imagify_add_forbidden_folder_patterns', array(), $folders );
		$added_folders = array_filter( (array) $added_folders );
		$added_folders = array_filter( $added_folders, 'is_string' );

		if ( ! $added_folders ) {
			return $folders;
		}

		$folders = array_merge( $folders, $added_folders );
		$folders = array_flip( array_flip( $folders ) );

		return $folders;
	}

	/**
	 * Tell if a file/folder name is forbidden.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_name A file or folder name.
	 * @return bool
	 */
	public static function is_filename_forbidden( $file_name ) {
		static $file_names;

		if ( ! isset( $file_names ) ) {
			$file_names = array_flip( self::get_forbidden_file_names() );
		}

		return isset( $file_names[ strtolower( $file_name ) ] );
	}

	/**
	 * Get the list of file names that Imagify won't optimize.
	 * It can contain folder names. Names are case-lowered.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array A list of file names
	 */
	public static function get_forbidden_file_names() {
		static $file_names;

		if ( isset( $file_names ) ) {
			return $file_names;
		}

		$file_names = array(
			'.',
			'..',
			'.DS_Store',
			'.git',
			'.svn',
			'node_modules',
			'Thumbs.db',
		);
		$file_names = array_map( 'strtolower', $file_names );

		/**
		 * Add file names to the list of forbidden ones.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param array $added_file_names List of file names.
		 * @param array $file_names       List of file names already forbidden.
		 */
		$added_file_names = apply_filters( 'imagify_add_forbidden_file_names', array(), $file_names );

		if ( ! $added_file_names || ! is_array( $added_file_names ) ) {
			return $file_names;
		}

		$added_file_names = array_filter( $added_file_names, 'is_string' );
		$added_file_names = array_map( 'strtolower', $added_file_names );

		$file_names = array_merge( $file_names, $added_file_names );
		$file_names = array_flip( array_flip( $file_names ) );

		return $file_names;
	}

	/**
	 * Add a placeholder to a path.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path An absolute path.
	 * @return string            A "placeholdered" path.
	 */
	public static function add_placeholder( $file_path ) {
		$file_path = wp_normalize_path( $file_path );
		$locations = self::get_placeholder_paths();

		foreach ( $locations as $placeholder => $location_path ) {
			if ( strpos( $file_path, $location_path ) === 0 ) {
				return str_replace( $location_path, $placeholder, $file_path );
			}
		}

		// Should not happen.
		return $file_path;
	}

	/**
	 * Change a path with a placeholder into a real path or URL.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path A path with a placeholder.
	 * @param  string $type      What to return: 'path' or 'url'.
	 * @return string            An absolute path or a URL.
	 */
	public static function remove_placeholder( $file_path, $type = 'path' ) {
		if ( 'path' === $type ) {
			$locations = self::get_placeholder_paths();
		} else {
			$locations = self::get_placeholder_urls();
		}

		foreach ( $locations as $placeholder => $location_path ) {
			if ( strpos( $file_path, $placeholder ) === 0 ) {
				return str_replace( $placeholder, $location_path, $file_path );
			}
		}

		// Should not happen.
		return $file_path;
	}

	/**
	 * Get array of pairs of placeholder => corresponding path.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public static function get_placeholder_paths() {
		static $replacements;

		if ( isset( $replacements ) ) {
			return $replacements;
		}

		$replacements = array(
			'{{PLUGINS}}/'    => WP_PLUGIN_DIR . '/',
			'{{MU_PLUGINS}}/' => WPMU_PLUGIN_DIR . '/',
			'{{THEMES}}/'     => WP_CONTENT_DIR . '/themes/',
			'{{CONTENT}}/'    => WP_CONTENT_DIR . '/',
			'{{ABSPATH}}/'    => ABSPATH,
		);
		$replacements = array_map( 'wp_normalize_path', $replacements );

		return $replacements;
	}

	/**
	 * Get array of pairs of placeholder => corresponding URL.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public static function get_placeholder_urls() {
		static $replacements;

		if ( isset( $replacements ) ) {
			return $replacements;
		}

		$replacements = array(
			'{{PLUGINS}}/'    => plugins_url( '/' ),
			'{{MU_PLUGINS}}/' => plugins_url( '/', WPMU_PLUGIN_DIR . '/.' ),
			'{{THEMES}}/'     => content_url( 'themes/' ),
			'{{CONTENT}}/'    => content_url( '/' ),
			'{{ABSPATH}}/'    => site_url( '/' ),
		);

		return $replacements;
	}

	/**
	 * A file_exists() for paths with a placeholder.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return bool
	 */
	public static function placeholder_path_exists( $file_path ) {
		return imagify_get_filesystem()->is_readable( self::remove_placeholder( $file_path ) );
	}

	/**
	 * Normalize a file path, aiming for path comparison.
	 * The path is normalized, case-lowered, and a trailing slash is added.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string            The normalized file path.
	 */
	public static function normalize_path_for_comparison( $file_path ) {
		return strtolower( wp_normalize_path( trailingslashit( $file_path ) ) );
	}

	/**
	 * Get the path to NextGen galleries. On multisite, the path contains `%BLOG_ID%`, and must be used as a regex pattern.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string An absolute path.
	 */
	public static function get_ngg_galleries_path() {
		$ngg_options = get_site_option( 'ngg_options' );

		if ( empty( $ngg_options['gallerypath'] ) ) {
			if ( is_multisite() ) {
				return get_imagify_upload_basedir( true ) . 'sites/%BLOG_ID%/nggallery';
			}

			return WP_CONTENT_DIR . '/gallery/';
		}

		$ngg_root = defined( 'NGG_GALLERY_ROOT_TYPE' ) ? NGG_GALLERY_ROOT_TYPE : 'site';

		if ( 'content' === $ngg_root ) {
			$ngg_root = WP_CONTENT_DIR . '/';
		} else {
			$ngg_root = imagify_get_filesystem()->get_abspath();
		}

		if ( is_multisite() ) {
			return $ngg_root . str_replace( '%BLOG_NAME%', get_bloginfo( 'name' ), $ngg_options['gallerypath'] );
		}

		return $ngg_root . $ngg_options['gallerypath'];
	}

	/**
	 * Get the path to WooCommerce logs.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string An absolute path.
	 */
	public static function get_wc_logs_path() {
		if ( defined( 'WC_LOG_DIR' ) ) {
			return WC_LOG_DIR;
		}

		return get_imagify_upload_basedir( true ) . 'wc-logs/';
	}

	/**
	 * Get the path to EWWW optimization tools.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string An absolute path.
	 */
	public static function get_ewww_tools_path() {
		if ( defined( 'EWWW_IMAGE_OPTIMIZER_TOOL_PATH' ) ) {
			return EWWW_IMAGE_OPTIMIZER_TOOL_PATH;
		}

		return WP_CONTENT_DIR . '/ewww/';
	}
}
