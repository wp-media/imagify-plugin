<?php

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the constant is defined.
 *
 * NOTE: This function allows mocking constants when testing.
 *
 * @since 1.9.11
 *
 * @param string $constant_name Name of the constant to check.
 *
 * @return bool true when constant is defined; else, false.
 */
function imagify_has_constant( $constant_name ) {
	return defined( $constant_name );
}

/**
 * Gets the constant is defined.
 *
 * NOTE: This function allows mocking constants when testing.
 *
 * @since 1.9.11
 *
 * @param string     $constant_name Name of the constant to check.
 * @param mixed|null $default Optional. Default value to return if constant is not defined.
 *
 * @return bool true when constant is defined; else, false.
 */
function imagify_get_constant( $constant_name, $default = null ) {
	if ( ! imagify_has_constant( $constant_name ) ) {
		return $default;
	}

	return constant( $constant_name );
}

/**
 * Get the permissions to apply to files and folders.
 *
 * Reminder:
 * `$perm = fileperms( $file );`
 *
 *  WHAT                                         | TYPE   | FILE   | FOLDER |
 * ----------------------------------------------+--------+--------+--------|
 * `$perm`                                       | int    | 33188  | 16877  |
 * `substr( decoct( $perm ), -4 )`               | string | '0644' | '0755' |
 * `substr( sprintf( '%o', $perm ), -4 )`        | string | '0644' | '0755' |
 * `$perm & 0777`                                | int    | 420    | 493    |
 * `decoct( $perm & 0777 )`                      | string | '644'  | '755'  |
 * `substr( sprintf( '%o', $perm & 0777 ), -4 )` | string | '644'  | '755'  |
 *
 * @since  1.9.11
 *
 * @param  string $type The type: 'dir' or 'file'.
 *
 * @return int          Octal integer.
 */
function imagify_get_filesystem_perms( $type ) {
	static $perms = [];

	if ( imagify_get_constant( 'IMAGIFY_IS_TESTING', false ) ) {
		$perms = [];
	}

	// Allow variants.
	switch ( $type ) {
		case 'dir':
		case 'dirs':
		case 'folder':
		case 'folders':
			$type = 'dir';
			break;

		case 'file':
		case 'files':
			$type = 'file';
			break;

		default:
			return 0755;
	}

	if ( isset( $perms[ $type ] ) ) {
		return $perms[ $type ];
	}

	// If the constants are not defined, use fileperms() like WordPress does.
	if ( 'dir' === $type ) {
		$fs_chmod_dir   = (int) imagify_get_constant( 'FS_CHMOD_DIR', 0 );
		$perms[ $type ] = $fs_chmod_dir > 0
			? $fs_chmod_dir
			: fileperms( imagify_get_constant( 'ABSPATH' ) ) & 0777 | 0755;
	} else {
		$fs_chmod_file  = (int) imagify_get_constant( 'FS_CHMOD_FILE', 0 );
		$perms[ $type ] = $fs_chmod_file > 0
			? $fs_chmod_file
			: fileperms( imagify_get_constant( 'ABSPATH' ) . 'index.php' ) & 0777 | 0644;
	}

	return $perms[ $type ];
}
