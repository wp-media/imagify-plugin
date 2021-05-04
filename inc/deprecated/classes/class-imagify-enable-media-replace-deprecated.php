<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since 1.8.4
 * @deprecated
 */
class Imagify_Enable_Media_Replace_Deprecated {

	/**
	 * The attachment ID.
	 *
	 * @var   int
	 * @since 1.6.9
	 * @since 1.9 Deprecated
	 * @deprecated
	 */
	protected $attachment_id;

	/**
	 * The attachment.
	 *
	 * @var   Imagify_Attachment
	 * @since 1.6.9
	 * @since 1.9 Deprecated
	 * @deprecated
	 */
	protected $attachment;

	/**
	 * Tell if the attachment has data.
	 * No data means not processed by Imagify, or restored.
	 *
	 * @var   bool
	 * @since 1.8.4
	 * @since 1.9 Deprecated
	 * @deprecated
	 */
	protected $attachment_has_data;

	/**
	 * Filesystem object.
	 *
	 * @var   object Imagify_Filesystem
	 * @since 1.7.1
	 * @since 1.8.4 Deprecated
	 * @deprecated
	 */
	protected $filesystem;

	/**
	 * Optimize the attachment files if the old ones were also optimized.
	 * Delete the old backup file.
	 *
	 * @since 1.6.9
	 * @since 1.8.4 Deprecated.
	 * @see   $this->store_old_backup_path()
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
	 * When the user chooses to change the file name, store the old backup file path. This path will be used later to delete the file.
	 *
	 * @since 1.6.9
	 * @since 1.9.10 Deprecated.
	 * @deprecated
	 *
	 * @param  string $new_filename The new file name.
	 * @param  string $current_path The current file path.
	 * @param  int    $post_id      The attachment ID.
	 * @return string               The same file name.
	 */
	public function store_old_backup_path( $new_filename, $current_path, $post_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9.10' );

		if ( ! $this->media_id || $post_id !== $this->media_id ) {
			return $new_filename;
		}

		$this->get_process();

		if ( ! $this->process ) {
			$this->media_id = 0;
			return $new_filename;
		}

		$media       = $this->process->get_media();
		$backup_path = $media->get_backup_path();

		if ( $backup_path ) {
			$this->old_backup_path = $backup_path;

			// Keep track of existing WebP files.
			$media_files = $media->get_media_files();

			if ( $media_files ) {
				foreach ( $media_files as $media_file ) {
					$this->old_webp_paths[] = imagify_path_to_webp( $media_file['path'] );
				}
			}
		} else {
			$this->media_id        = 0;
			$this->old_backup_path = false;
			$this->old_webp_paths  = [];
		}

		return $new_filename;
	}

	/**
	 * Get the attachment.
	 *
	 * @since 1.6.9
	 * @since 1.9 Deprecated.
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
