<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since  1.6.9
 * @author Grégory Viguier
 */
class Imagify_Enable_Media_Replace {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * The attachment ID.
	 *
	 * @var int
	 */
	protected $attachment_id;

	/**
	 * The attachment.
	 *
	 * @var object A Imagify_Attachment object (or any class extending it).
	 */
	protected $attachment;

	/**
	 * The path to the old backup file.
	 *
	 * @var string
	 */
	protected $old_backup_path;

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * Get the main instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.6.9
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

	/**
	 * The class constructor.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

	/**
	 * Launch the hooks before the files and data are replaced.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param  bool $unfiltered Whether to allow filters when retrieving the file path.
	 * @return bool             The same value.
	 */
	public function init( $unfiltered = true ) {
		$this->attachment_id = ! empty( $_POST['ID'] ) ? absint( $_POST['ID'] ) : 0; // WPCS: CSRF ok.

		if ( ! $this->attachment_id || empty( $_FILES['userfile']['tmp_name'] ) || ! is_uploaded_file( $_FILES['userfile']['tmp_name'] ) ) {
			return $unfiltered;
		}

		// Remove the automatic optimization.
		remove_filter( 'wp_generate_attachment_metadata', '_imagify_optimize_attachment', IMAGIFY_INT_MAX );

		// Store the old backup file path.
		add_filter( 'emr_unique_filename', array( $this, 'store_old_backup_path' ), 10, 3 );
		// Optimize and delete the old backup file.
		add_action( 'emr_returnurl',       array( $this, 'optimize' ) );

		return $unfiltered;
	}

	/**
	 * When the user chooses to change the file name, store the old backup file path. This path will be used later to delete the file.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 * @see    $this->optimize()
	 *
	 * @param  string $new_filename The new file name.
	 * @param  string $current_path The current file path.
	 * @param  int    $post_id      The attachment ID.
	 * @return string               The same file name.
	 */
	public function store_old_backup_path( $new_filename, $current_path, $post_id ) {
		if ( $post_id !== $this->attachment_id ) {
			return $new_filename;
		}

		$backup_path = $this->get_attachment()->get_backup_path();

		if ( $backup_path ) {
			$this->old_backup_path = $backup_path;
		}

		return $new_filename;
	}

	/**
	 * Optimize the attachment files if the old ones were also optimized.
	 * Delete the old backup file.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 * @see    $this->store_old_backup_path()
	 *
	 * @param  string $return_url The URL the user will be redirected to.
	 * @return string             The same URL.
	 */
	public function optimize( $return_url ) {
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

		/**
		 * Delete the old backup file.
		 */
		if ( ! $this->old_backup_path || ! $this->filesystem->exists( $this->old_backup_path ) ) {
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
		$this->filesystem->delete( $this->old_backup_path );

		$this->old_backup_path = null;
		return $return_url;
	}

	/**
	 * Get the attachment.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @return object A Imagify_Attachment object (or any class extending it).
	 */
	protected function get_attachment() {
		if ( $this->attachment ) {
			return $this->attachment;
		}

		$this->attachment = get_imagify_attachment( 'wp', $this->attachment_id, 'enable_media_replace' );

		return $this->attachment;
	}
}
