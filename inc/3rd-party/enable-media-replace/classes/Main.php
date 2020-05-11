<?php
namespace Imagify\ThirdParty\EnableMediaReplace;

use Imagify_Filesystem;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since 1.6.9
 */
class Main extends \Imagify_Enable_Media_Replace_Deprecated {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * The media ID.
	 *
	 * @var   int
	 * @since 1.9
	 */
	protected $media_id;

	/**
	 * The process instance for the current attachment.
	 *
	 * @var   ProcessInterface
	 * @since 1.9
	 */
	protected $process;

	/**
	 * The path to the old backup file.
	 *
	 * @var   string
	 * @since 1.6.9
	 */
	protected $old_backup_path;

	/**
	 * List of paths to the old webp files.
	 *
	 * @var   array
	 * @since 1.9.8
	 */
	protected $old_webp_paths = [];

	/**
	 * Launch the hooks before the files and data are replaced.
	 *
	 * @since 1.6.9
	 * @since 1.9.10 The parameter changed from boolean to array. The method doesn’t return anything.
	 *
	 * @param array $args An array containing the post ID.
	 */
	public function init( $args = [] ) {
		if ( is_array( $args ) && ! empty( $args['post_id'] ) ) {
			$this->media_id = $args['post_id'];
		} else {
			// Backward compatibility.
			$this->media_id = (int) filter_input( INPUT_POST, 'ID' );
			$this->media_id = max( 0, $this->media_id );

			if ( ! $this->media_id ) {
				return;
			}
		}

		// Store the old backup file path.
		$this->get_process();

		if ( ! $this->process ) {
			$this->media_id = 0;
			return;
		}

		$this->old_backup_path = $this->process->get_media()->get_backup_path();

		if ( ! $this->old_backup_path ) {
			$this->media_id = 0;
			return;
		}

		// Store the old backup file path.
		add_filter( 'emr_unique_filename', [ $this, 'store_old_backup_path' ], 10, 3 );
		// Delete the old backup file.
		add_action( 'imagify_before_auto_optimization',         [ $this, 'delete_backup' ] );
		add_action( 'imagify_not_optimized_attachment_updated', [ $this, 'delete_backup' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * When the user chooses to change the file name, store the old backup file path. This path will be used later to delete the file.
	 *
	 * @since 1.6.9
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

		$media       = $this->process->get_media();
		$backup_path = $media->get_backup_path();

		if ( $backup_path ) {
			$this->old_backup_path = $backup_path;

			// Keep track of existing webp files.
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
	 * Delete previous backup file. This is done after the images have been already replaced by Enable Media Replace.
	 * It will prevent having a backup file not corresponding to the current images.
	 *
	 * @since 1.8.4
	 *
	 * @param int $media_id The attachment ID.
	 */
	public function delete_backup( $media_id ) {
		if ( ! $this->old_backup_path || ! $this->media_id || $media_id !== $this->media_id ) {
			return;
		}

		$filesystem = Imagify_Filesystem::get_instance();

		if ( $filesystem->exists( $this->old_backup_path ) ) {
			$filesystem->delete( $this->old_backup_path );
			$this->old_backup_path = false;
		}

		if ( $this->old_webp_paths ) {
			// If the files have been renamed, delete old webp files.
			$this->old_webp_paths = array_filter( $this->old_webp_paths, [ $filesystem, 'exists' ] );
			array_map( [ $filesystem, 'delete' ], $this->old_webp_paths );
			$this->old_webp_paths = [];
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the optimization process corresponding to the current media.
	 *
	 * @since 1.9
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
