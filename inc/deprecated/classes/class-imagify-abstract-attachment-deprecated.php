<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify_Abstract_Attachment.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Abstract_Attachment_Deprecated {

	/**
	 * Maybe backup a file.
	 *
	 * @since  1.6.6 In Imagify_AS3CF_Attachment.
	 * @since  1.6.8 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $attachment_path  The file path.
	 * @return bool|null                True on success. False on failure. Null if backup is not needed.
	 */
	protected function maybe_backup( $attachment_path ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.6.8', 'imagify_backup_file()' );

		$result = imagify_backup_file( $attachment_path );

		if ( false === $result ) {
			return null;
		}

		return ! is_wp_error( $result );
	}
}
