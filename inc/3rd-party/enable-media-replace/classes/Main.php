<?php
namespace Imagify\ThirdParty\EnableMediaReplace;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since  1.6.9
 * @author Grégory Viguier
 */
class Main extends \Imagify_Enable_Media_Replace_Deprecated {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.6.9
	 * @author Grégory Viguier
	 */
	const VERSION = '2.1';

	/**
	 * The media ID.
	 *
	 * @var    int
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $media_id;

	/**
	 * The process instance for the current attachment.
	 *
	 * @var    ProcessInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $process;

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
	 * Launch the hooks before the files and data are replaced.
	 *
	 * @since  1.6.9
	 * @author Grégory Viguier
	 *
	 * @param  bool $unfiltered Whether to allow filters when retrieving the file path.
	 * @return bool             The same value.
	 */
	public function init( $unfiltered = true ) {
		$this->media_id = (int) filter_input( INPUT_POST, 'ID' );
		$this->media_id = max( 0, $this->media_id );

		if ( ! $this->media_id || empty( $_FILES['userfile']['tmp_name'] ) ) {
			$this->media_id = 0;
			return $unfiltered;
		}

		$tmp_name = wp_unslash( $_FILES['userfile']['tmp_name'] );

		if ( ! is_uploaded_file( $tmp_name ) ) {
			$this->media_id = 0;
			return $unfiltered;
		}

		// Store the old backup file path.
		add_filter( 'emr_unique_filename', [ $this, 'store_old_backup_path' ], 10, 3 );
		// Delete the old backup file.
		add_action( 'imagify_before_auto_optimization',         [ $this, 'delete_backup' ] );
		add_action( 'imagify_not_optimized_attachment_updated', [ $this, 'delete_backup' ] );

		return $unfiltered;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * When the user chooses to change the file name, store the old backup file path. This path will be used later to delete the file.
	 *
	 * @since  1.6.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $new_filename The new file name.
	 * @param  string $current_path The current file path.
	 * @param  int    $post_id      The attachment ID.
	 * @return string               The same file name.
	 */
	public function store_old_backup_path( $new_filename, $current_path, $post_id ) {
		if ( ! $this->media_id || $post_id !== $this->media_id ) {
			return $new_filename;
		}

		$this->get_process();

		if ( ! $this->process ) {
			$this->media_id = 0;
			return $new_filename;
		}

		$backup_path = $this->process->get_media()->get_backup_path();

		if ( $backup_path ) {
			$this->old_backup_path = $backup_path;
		} else {
			$this->media_id        = 0;
			$this->old_backup_path = false;
		}

		return $new_filename;
	}

	/**
	 * Delete previous backup file. This is done after the images have been already replaced by Enable Media Replace.
	 * It will prevent having a backup file not corresponding to the current images.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $media_id The attachment ID.
	 */
	public function delete_backup( $media_id ) {
		if ( ! $this->old_backup_path || ! $this->media_id || $media_id !== $this->media_id ) {
			return;
		}

		$filesystem = \Imagify_Filesystem::get_instance();

		if ( ! $filesystem->exists( $this->old_backup_path ) ) {
			return;
		}

		$filesystem->delete( $this->old_backup_path );
		$this->old_backup_path = false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the optimization process corresponding to the current media.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access protected
	 *
	 * @return ProcessInterface|bool False if invalid.
	 */
	protected function get_process() {
		if ( isset( $this->process ) ) {
			return $this->process;
		}

		$this->process = imagify_get_optimization_process( $this->media_id, 'wp' );

		if ( ! $this->process->is_valid() ) {
			$this->process = false;
		}

		return $this->process;
	}
}
