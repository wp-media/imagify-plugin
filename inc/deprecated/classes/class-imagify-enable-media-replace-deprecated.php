<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Enable_Media_Replace_Deprecated {

	/**
	 * The attachment ID.
	 *
	 * @var    int
	 * @since  1.6.9
	 * @since  1.9 Deprecated
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 */
	protected $attachment_id;

	/**
	 * The attachment.
	 *
	 * @var    Imagify_Attachment
	 * @since  1.6.9
	 * @since  1.9 Deprecated
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 */
	protected $attachment;

	/**
	 * Tell if the attachment has data.
	 * No data means not processed by Imagify, or restored.
	 *
	 * @var    bool
	 * @since  1.8.4
	 * @since  1.9 Deprecated
	 * @access protected
	 * @author Grégory Viguier
	 * @deprecated
	 */
	protected $attachment_has_data;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @access protected
	 * @deprecated
	 */
	protected $filesystem;

	/**
	 * Optimize the attachment files if the old ones were also optimized.
	 * Delete the old backup file.
	 *
	 * @since  1.6.9
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->store_old_backup_path()
	 * @access protected
	 * @deprecated
	 *
	 * @param  string $return_url The URL the user will be redirected to.
	 * @return string             The same URL.
	 */
	public function optimize( $return_url ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4' );

		$attachment = $this->get_attachment();

		if ( $attachment->get_data() ) {
			/**
			 * The old images have been optimized in the past.
			 */
			// Use the same otimization level for the new ones.
			$optimization_level = $attachment->get_optimization_level();

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			// Optimize and overwrite the previous backup file if exists and needed.
			add_filter( 'imagify_backup_overwrite_backup', '__return_true', 42 );
			$attachment->optimize( $optimization_level );
			remove_filter( 'imagify_backup_overwrite_backup', '__return_true', 42 );
		}

		$filesystem = Imagify_Filesystem::get_instance();

		/**
		 * Delete the old backup file.
		 */
		if ( ! $this->old_backup_path || ! $filesystem->exists( $this->old_backup_path ) ) {
			// The user didn't choose to rename the files, or there is no old backup.
			$this->old_backup_path = null;
			return $return_url;
		}

		$new_backup_path = $attachment->get_raw_backup_path();

		if ( $new_backup_path === $this->old_backup_path ) {
			// We don't want to delete the new backup.
			$this->old_backup_path = null;
			return $return_url;
		}

		// Finally, delete the old backup file.
		$filesystem->delete( $this->old_backup_path );

		$this->old_backup_path = null;
		return $return_url;
	}

	/**
	 * Get the attachment.
	 *
	 * @since  1.6.9
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @access protected
	 * @deprecated
	 *
	 * @return object A Imagify_Attachment object (or any class extending it).
	 */
	protected function get_attachment() {
		if ( $this->attachment ) {
			return $this->attachment;
		}

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9', '\\Imagify\\ThirdParty\\EnableMediaReplace\\Main::get_instance()->get_process()' );

		$this->attachment = get_imagify_attachment( 'wp', $this->attachment_id, 'enable_media_replace' );

		return $this->attachment;
	}
}
