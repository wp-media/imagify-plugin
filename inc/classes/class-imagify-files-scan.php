<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	const VERSION = '1.1.1';

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

		if ( self::is_path_forbidden( trailingslashit( $folder ) ) ) {
			return new WP_Error( 'folder_forbidden', __( 'This folder is not allowed.', 'imagify' ) );
		}

		// Finally we made all our validations.
		if ( $filesystem->is_site_root( $folder ) ) {
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


	/** ----------------------------------------------------------------------------------------- */
	/** FORBIDDEN FOLDERS AND FILES ============================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if a path is autorized.
	 * When testing a folder, the path MUST have a trailing slash.
	 *
	 * @since  1.7.1
	 * @since  1.8 The path must have a trailing slash if for a folder.
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
	 * When testing a folder, the path MUST have a trailing slash.
	 *
	 * @since  1.7
	 * @since  1.8 The path must have a trailing slash if  for a folder.
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

		$delim = Imagify_Filesystem::PATTERN_DELIMITER;

		foreach ( self::get_forbidden_folder_patterns() as $pattern ) {
			if ( preg_match( $delim . '^' . $pattern . $delim, $file_path ) ) {
				return true;
			}
		}

		foreach ( $folders as $folder => $i ) {
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
		$site_root  = $filesystem->get_site_root();
		$abspath    = $filesystem->get_abspath();
		$folders    = array(
			// Server.
			$site_root . 'cgi-bin',                        // `cgi-bin`
			// WordPress.
			$abspath . 'wp-admin',                         // `wp-admin`
			$abspath . WPINC,                              // `wp-includes`
			WP_CONTENT_DIR . '/mu-plugins',                // MU plugins.
			WP_CONTENT_DIR . '/upgrade',                   // Upgrade.
			// Plugins.
			WP_CONTENT_DIR . '/bps-backup',                // BulletProof Security.
			self::get_ewww_tools_path(),                   // EWWW: /wp-content/ewww.
			WP_CONTENT_DIR . '/ngg',                       // NextGen Gallery.
			WP_CONTENT_DIR . '/ngg_styles',                // NextGen Gallery.
			WP_CONTENT_DIR . '/w3tc-config',               // W3 Total Cache.
			WP_CONTENT_DIR . '/wfcache',                   // WP Fastest Cache.
			WP_CONTENT_DIR . '/wp-rocket-config',          // WP Rocket.
			Imagify_Custom_Folders::get_backup_dir_path(), // Imagify "Custom folders" backup: /imagify-backup.
			IMAGIFY_PATH,                                  // Imagify plugin: /wp-content/plugins/imagify.
			self::get_shortpixel_path(),                   // ShortPixel: /wp-content/uploads/ShortpixelBackups.
		);

		if ( ! is_multisite() ) {
			$uploads_dir   = $filesystem->get_upload_basedir( true );
			$ngg_galleries = self::get_ngg_galleries_path();

			if ( $ngg_galleries ) {
				$folders[] = $ngg_galleries;                   // NextGen Gallery: /wp-content/gallery.
			}

			$folders[] = $uploads_dir . 'formidable';          // Formidable Forms: /wp-content/uploads/formidable.
			$folders[] = get_imagify_backup_dir_path( true );  // Imagify Media Library backup: /wp-content/uploads/backup.
			$folders[] = self::get_wc_logs_path();             // WooCommerce Logs: /wp-content/uploads/wc-logs.
			$folders[] = $uploads_dir . 'woocommerce_uploads'; // WooCommerce uploads: /wp-content/uploads/woocommerce_uploads.
		}

		$folders = array_map( array( $filesystem, 'normalize_dir_path' ), $folders );

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

		$added_folders = array_map( array( $filesystem, 'normalize_dir_path' ), $added_folders );

		$folders = array_merge( $folders, $added_folders );
		$folders = array_flip( array_flip( $folders ) );

		return $folders;
	}

	/**
	 * Get the list of folder patterns where Imagify won't look for files to optimize. This is meant for paths that are dynamic.
	 * `^` will be prepended to each pattern (aka, the pattern must match an absolute path).
	 * Pattern delimiter is `Imagify_Filesystem::PATTERN_DELIMITER`.
	 * Paths tested against these patterns are lower-cased.
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

		// Media Library: /wp\-content/uploads/(sites/\d+/)?\d{4}/\d{2}/.
		$folders[] = self::get_media_library_pattern();

		if ( is_multisite() ) {
			/**
			 * On multisite we can't exclude Imagify's library backup folders, or any other folder located in the uploads folders (created by other plugins): there are too many ways it can fail.
			 * Only exception we're aware of so far is NextGen Gallery, because it provides a clear pattern to use.
			 */
			$ngg_galleries = self::get_ngg_galleries_multisite_pattern();

			if ( $ngg_galleries ) {
				// NextGen Gallery: /wp\-content/uploads/sites/\d+/nggallery/.
				$folders[] = $ngg_galleries;
			}
		}

		/**
		 * Add folder patterns to the list of forbidden ones.
		 * Don't forget to use `Imagify_Files_Scan::normalize_path_for_regex( $path )`!
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
			'backup',
			'backups',
			'cache',
			'lang',
			'langs',
			'languages',
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


	/** ----------------------------------------------------------------------------------------- */
	/** PLACEHOLDERS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

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
				return preg_replace( '@^' . preg_quote( $location_path, '@' ) . '@', $placeholder, $file_path );
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
				return preg_replace( '@^' . preg_quote( $placeholder, '@' ) . '@', $location_path, $file_path );
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

		$filesystem   = imagify_get_filesystem();
		$replacements = array(
			'{{PLUGINS}}/'    => WP_PLUGIN_DIR,
			'{{MU_PLUGINS}}/' => WPMU_PLUGIN_DIR,
			'{{THEMES}}/'     => WP_CONTENT_DIR . '/themes',
			'{{UPLOADS}}/'    => $filesystem->get_main_upload_basedir(),
			'{{CONTENT}}/'    => WP_CONTENT_DIR,
			'{{ROOT}}/'       => $filesystem->get_site_root(),
		);
		$replacements = array_map( array( $filesystem, 'normalize_dir_path' ), $replacements );

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

		$filesystem   = imagify_get_filesystem();
		$replacements = array(
			'{{PLUGINS}}/'    => plugins_url( '/' ),
			'{{MU_PLUGINS}}/' => plugins_url( '/', WPMU_PLUGIN_DIR . '/.' ),
			'{{THEMES}}/'     => content_url( 'themes/' ),
			'{{UPLOADS}}/'    => $filesystem->get_main_upload_baseurl(),
			'{{CONTENT}}/'    => content_url( '/' ),
			'{{ROOT}}/'       => $filesystem->get_site_root_url(),
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


	/** ----------------------------------------------------------------------------------------- */
	/** PATHS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the path to NextGen galleries on monosites.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool An absolute path. False if it can't be retrieved.
	 */
	public static function get_ngg_galleries_path() {
		$galleries_path = get_site_option( 'ngg_options' );

		if ( empty( $galleries_path['gallerypath'] ) ) {
			return false;
		}

		$filesystem     = imagify_get_filesystem();
		$galleries_path = $filesystem->normalize_dir_path( $galleries_path['gallerypath'] );
		$galleries_path = trim( $galleries_path, '/' ); // Something like `wp-content/gallery`.

		$ngg_root = defined( 'NGG_GALLERY_ROOT_TYPE' ) ? NGG_GALLERY_ROOT_TYPE : 'site';

		if ( $galleries_path && 'content' === $ngg_root ) {
			$ngg_root = $filesystem->normalize_dir_path( WP_CONTENT_DIR );
			$ngg_root = trim( $ngg_root, '/' ); // Something like `abs-path/to/wp-content`.

			$exploded_root      = explode( '/', $ngg_root );
			$exploded_galleries = explode( '/', $galleries_path );
			$first_gallery_dirname = reset( $exploded_galleries );
			$last_root_dirname     = end( $exploded_root );

			if ( $last_root_dirname === $first_gallery_dirname ) {
				array_shift( $exploded_galleries );
				$galleries_path = implode( '/', $exploded_galleries );
			}
		}

		if ( 'content' === $ngg_root ) {
			$ngg_root = $filesystem->normalize_dir_path( WP_CONTENT_DIR );
		} else {
			$ngg_root = $filesystem->get_abspath();
		}

		if ( strpos( $galleries_path, $ngg_root ) !== 0 ) {
			$galleries_path = $ngg_root . $galleries_path;
		}

		return $galleries_path . '/';
	}

	/**
	 * Get the path to WooCommerce logs on monosites.
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

		return imagify_get_filesystem()->get_upload_basedir( true ) . 'wc-logs/';
	}

	/**
	 * Get the path to EWWW optimization tools.
	 * It is the same for all sites on multisite.
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

	/**
	 * Get the path to ShortPixel backup folder.
	 * It is the same for all sites on multisite (and yes, you'll get a surprise if your upload base dir -aka uploads/sites/12/- is not 2 folders deeper than theuploads folder).
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string An absolute path.
	 */
	public static function get_shortpixel_path() {
		if ( defined( 'SHORTPIXEL_BACKUP_FOLDER' ) ) {
			return trailingslashit( SHORTPIXEL_BACKUP_FOLDER );
		}

		$filesystem = imagify_get_filesystem();
		$path       = $filesystem->get_upload_basedir( true );
		$path       = is_main_site() ? $path : $filesystem->dir_path( $filesystem->dir_path( $path ) );

		return $path . 'ShortpixelBackups/';
	}


	/** ----------------------------------------------------------------------------------------- */
	/** REGEX PATTERNS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the regex pattern used to match the paths to the media library.
	 * Pattern delimiter is `Imagify_Filesystem::PATTERN_DELIMITER`.
	 * Paths tested against these patterns are lower-cased.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string Something like `/wp\-content/uploads/(sites/\d+/)?\d{4}/\d{2}/`.
	 */
	public static function get_media_library_pattern() {
		$filesystem  = imagify_get_filesystem();
		$uploads_dir = self::normalize_path_for_regex( $filesystem->get_main_upload_basedir() );

		if ( ! is_multisite() ) {
			if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
				// In year/month folders.
				return $uploads_dir . '\d{4}/\d{2}/';
			}

			// Not in year/month folders.
			return $uploads_dir . '[^/]+$';
		}

		$pattern = $filesystem->get_multisite_uploads_subdir_pattern();

		if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
			// In year/month folders.
			return $uploads_dir . '(' . $pattern . ')?\d{4}/\d{2}/';
		}

		// Not in year/month folders.
		return $uploads_dir . '(' . $pattern . ')?[^/]+$';
	}

	/**
	 * Get the regex pattern used to match the paths to NextGen galleries on multisite.
	 * Pattern delimiter is `Imagify_Filesystem::PATTERN_DELIMITER`.
	 * Paths tested against these patterns are lower-cased.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string|bool Something like `/wp-content/uploads/sites/\d+/nggallery/`. False if it can't be retrieved.
	 */
	public static function get_ngg_galleries_multisite_pattern() {
		$galleries_path = self::get_ngg_galleries_path(); // Something like `wp-content/uploads/sites/%BLOG_ID%/nggallery/`.

		if ( ! $galleries_path ) {
			return false;
		}

		$galleries_path = self::normalize_path_for_regex( $galleries_path );
		$galleries_path = str_replace( array( '%blog_name%', '%blog_id%' ), array( '.+', '\d+' ), $galleries_path );

		return $galleries_path;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** NORMALIZATION TOOLS ===================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Normalize a file path, aiming for path comparison.
	 * The path is normalized and case-lowered.
	 *
	 * @since  1.7
	 * @since  1.8 No trailing slash anymore, because it can be used for files.
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string            The normalized file path.
	 */
	public static function normalize_path_for_comparison( $file_path ) {
		return strtolower( wp_normalize_path( $file_path ) );
	}

	/**
	 * Normalize a file path, aiming for use in a regex pattern.
	 * The path is normalized, case-lowered, and escaped.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The file path.
	 * @return string            The normalized file path.
	 */
	public static function normalize_path_for_regex( $file_path ) {
		return preg_quote( imagify_get_filesystem()->normalize_path_for_comparison( $file_path ), Imagify_Filesystem::PATTERN_DELIMITER );
	}
}
