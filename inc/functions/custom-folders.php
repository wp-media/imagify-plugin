<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get the path to the backups directory (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return string Path to the backups directory.
 */
function imagify_get_files_backup_dir_path() {
	static $backup_dir;

	if ( isset( $backup_dir ) ) {
		return $backup_dir;
	}

	$backup_dir = imagify_get_abspath() . 'imagify-backup/';

	/**
	 * Filter the backup directory path (custom folders).
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 *
	 * @param string $backup_dir The backup directory path.
	*/
	$backup_dir = apply_filters( 'imagify_files_backup_directory', $backup_dir );
	$backup_dir = trailingslashit( wp_normalize_path( $backup_dir ) );

	return $backup_dir;
}

/**
 * Tell if the folder containing the backups is writable (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return bool
 */
function imagify_files_backup_dir_is_writable() {
	if ( ! get_imagify_backup_dir_path() ) {
		return false;
	}

	$filesystem     = imagify_get_filesystem();
	$has_backup_dir = wp_mkdir_p( imagify_get_files_backup_dir_path() );

	return $has_backup_dir && $filesystem->is_writable( imagify_get_files_backup_dir_path() );
}

/**
 * Get the backup path of a specific file (custom folders).
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @param  string $file_path The file path.
 * @return string|bool       The backup path. False on failure.
 */
function imagify_get_file_backup_path( $file_path ) {
	$file_path  = wp_normalize_path( (string) $file_path );
	$abspath    = imagify_get_abspath();
	$backup_dir = imagify_get_files_backup_dir_path();

	if ( ! $file_path ) {
		return false;
	}

	return str_replace( $abspath, $backup_dir, $file_path );
}

/**
 * Get installed theme paths.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return array A list of installed theme paths in the form of '{{THEMES}}/twentyseventeen/' => '/abspath/to/wp-content/themes/twentyseventeen/'.
 */
function imagify_get_theme_folders() {
	static $themes;

	if ( isset( $themes ) ) {
		return $themes;
	}

	$all_themes = wp_get_themes();
	$themes     = array();

	if ( $all_themes ) {
		foreach ( $all_themes as $stylesheet => $theme ) {
			if ( ! $theme->exists() ) {
				continue;
			}

			$path = trailingslashit( $theme->get_stylesheet_directory() );

			if ( Imagify_Files_Scan::is_path_forbidden( $path ) ) {
				continue;
			}

			$placeholder = Imagify_Files_Scan::add_placeholder( $path );

			$themes[ $placeholder ] = $path;
		}
	}

	return $themes;
}

/**
 * Get installed plugin paths.
 *
 * @since  1.7
 * @author Grégory Viguier
 *
 * @return array A list of installed plugin paths in the form of '{{PLUGINS}}/imagify/' => '/abspath/to/wp-content/plugins/imagify/'.
 */
function imagify_get_plugin_folders() {
	static $plugins, $plugins_path;

	if ( isset( $plugins ) ) {
		return $plugins;
	}

	if ( ! isset( $plugins_path ) ) {
		$plugins_path = Imagify_Files_Scan::remove_placeholder( '{{PLUGINS}}/' );
	}

	$all_plugins = get_plugins();
	$plugins     = array();

	if ( $all_plugins ) {
		$filesystem = imagify_get_filesystem();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$path = $plugins_path . $plugin_file;

			if ( ! $filesystem->exists( $path ) || Imagify_Files_Scan::is_path_forbidden( $path ) ) {
				continue;
			}

			$plugin_file = dirname( $plugin_file ) . '/';
			$plugins[ '{{PLUGINS}}/' . $plugin_file ] = $plugins_path . $plugin_file;
		}
	}

	return $plugins;
}
