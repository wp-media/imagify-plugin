<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since  1.6.9
 * @author Grégory Viguier
 */
class Imagify_Enable_Media_Replace extends Imagify_Enable_Media_Replace_Deprecated {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.6.9
	 * @author Grégory Viguier
	 */
	const VERSION = '2.0';

	/**
	 * The attachment ID.
	 *
	 * @var    int
	 * @since  1.6.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachment_id;

	/**
	 * The attachment.
	 *
	 * @var    object A Imagify_Attachment object (or any class extending it).
	 * @since  1.6.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachment;

	/**
	 * Tell if the attachment has data.
	 * No data means not processed by Imagify, or restored.
	 *
	 * @var    bool
	 * @since  1.8.4
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachment_has_data;

	/**
	 * The path to the old backup file.
	 *
	 * @var    string
	 * @since  1.6.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $old_backup_path;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.6.9
	 * @access protected
	 * @author Grégory Viguier
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
	protected function __construct() {}

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

		// Store the old backup file path.
		add_filter( 'emr_unique_filename', array( $this, 'store_old_backup_path' ), 10, 3 );
		// Delete the old backup file.
		add_action( 'imagify_before_auto_optimization_launch',  array( $this, 'delete_backup' ) );
		add_action( 'imagify_not_optimized_attachment_updated', array( $this, 'delete_backup' ) );

		return $unfiltered;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

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
			$this->old_backup_path     = $backup_path;
			$this->attachment_has_data = $this->get_attachment()->get_data();
		} else {
			$this->attachment_id       = null;
			$this->old_backup_path     = null;
			$this->attachment_has_data = null;
		}

		return $new_filename;
	}

	/**
	 * Delete previous backup file. This is done after the images have been already replaced by Enable Media Replace.
	 * It will prevent having a backup file not corresponding to the current images.
	 *
	 * @since  1.8.4
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function delete_backup( $attachment_id ) {
		if ( $attachment_id !== $this->attachment_id ) {
			return;
		}

		$filesystem = Imagify_Filesystem::get_instance();

		if ( ! $this->old_backup_path || ! $filesystem->exists( $this->old_backup_path ) ) {
			return;
		}

		$filesystem->delete( $this->old_backup_path );
		$this->old_backup_path = null;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

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
