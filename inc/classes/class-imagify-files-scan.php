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
	const VERSION = '1.0';

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
		static $abspath;

		// Formate and validate the folder path.
		if ( ! is_string( $folder ) || '' === $folder || '/' === $folder || '\\' === $folder ) {
			return new WP_Error( 'invalid_folder', __( 'Invalid folder.', 'imagify' ) );
		}

		$folder = realpath( $folder );

		if ( ! $folder ) {
			return new WP_Error( 'folder_not_exists', __( 'This folder does not exist.', 'imagify' ) );
		}

		if ( ! imagify_get_filesystem()->is_dir( $folder ) ) {
			return new WP_Error( 'not_a_folder', __( 'This file is not a folder.', 'imagify' ) );
		}

		if ( self::is_path_forbidden( $folder ) ) {
			return new WP_Error( 'folder_forbidden', __( 'This folder is not allowed.', 'imagify' ) );
		}

		if ( ! isset( $abspath ) ) {
			$abspath = realpath( ABSPATH );
		}

		// Finally we made all our validations.
		if ( $folder === $abspath ) {
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
	 * Tell if a path is forbidden.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path     A file or folder absolute path.
	 * @param  bool   $check_parents If true, will check that the given file/folder is not inside a forbidden folder.
	 * @return bool
	 */
	public static function is_path_forbidden( $file_path, $check_parents = true ) {
		static $folders, $root_folders;

		if ( self::is_filename_forbidden( basename( $file_path ) ) ) {
			return true;
		}

		if ( imagify_file_is_symlinked( $file_path ) ) {
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

		if ( ! $check_parents ) {
			// Don't check if the file is located in a forbidden folder.
			if ( ! isset( $root_folders ) ) {
				$root_folders = self::get_forbidden_folder_roots();
				$root_folders = array_map( 'strtolower', $root_folders );
				$root_folders = array_flip( $root_folders );
			}

			// Since the user can select plugins and themes directly, disallow to select plugins and themes folders directly.
			return isset( $root_folders[ $file_path ] );
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
	 * It can contain non-folder paths.
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

		$folders = array(
			imagify_get_files_backup_dir_path(), // "Custom folders" backup.
			imagify_get_abspath() . 'wp-admin',  // `wp-admin`
			imagify_get_abspath() . WPINC,       // `wp-includes`
			get_imagify_upload_basedir( true ),  // Medias library.
			WP_CONTENT_DIR . '/gallery',         // NGG galleries.
			IMAGIFY_PATH,                        // Imagify plugin.
		);
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

		if ( ! $added_folders || ! is_array( $added_folders ) ) {
			return $folders;
		}

		$added_folders = array_filter( $added_folders, 'is_string' );
		$added_folders = array_map( 'trailingslashit', $added_folders );
		$added_folders = array_map( 'wp_normalize_path', $added_folders );

		$folders = array_merge( $folders, $added_folders );
		$folders = array_flip( array_flip( $folders ) );

		return $folders;
	}

	/**
	 * Get the list of folder "roots" where Imagify won't look for files to optimize.
	 * Folder "roots" are folders where Imagify can look for files in sub-folders, but not directly into these folders.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array A list of absolute paths.
	 */
	public static function get_forbidden_folder_roots() {
		static $folders;

		if ( isset( $folders ) ) {
			return $folders;
		}

		$filesystem = imagify_get_filesystem();
		$folders    = array(
			WP_PLUGIN_DIR,
		);

		foreach ( (array) get_theme_roots() as $theme_root ) {
			if ( $filesystem->exists( $theme_root ) ) {
				$folders[] = $theme_root;
			} else {
				$folders[] = WP_CONTENT_DIR . $theme_root;
			}
		}

		$folders = array_map( 'trailingslashit', $folders );
		$folders = array_map( 'wp_normalize_path', $folders );

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
		return imagify_get_filesystem()->exists( self::remove_placeholder( $file_path ) );
	}

	/**
	 * Get all theme roots.
	 * In most sites, only one will be returned.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array A list of theme roots. All heading and trailing slashes are trimed.
	 */
	public static function get_theme_roots() {
		static $theme_roots;

		if ( isset( $theme_roots ) ) {
			return $theme_roots;
		}

		$theme_roots = (array) get_theme_roots();
		$theme_roots = array_flip( array_flip( $theme_roots ) );
		$theme_roots = array_map( 'trim', $theme_roots, array_fill( 0 , count( $theme_roots ) , '/' ) );

		return $theme_roots;
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
}
