<?php
namespace Imagify\ThirdParty\EnableMediaReplace;

use Imagify\Traits\InstanceGetterTrait;
use Imagify_Enable_Media_Replace_Deprecated;
use Imagify_Filesystem;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since 1.6.9
 */
class Main extends Imagify_Enable_Media_Replace_Deprecated {
	use InstanceGetterTrait;

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
	 * List of paths to the old WebP files.
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

		/**
		 * Keep track of existing WebP files.
		 *
		 * Whether the user chooses to rename the files or not, we will need to delete the current WebP files before creating new ones:
		 * - Rename the files: the old ones must be removed, they are useless now.
		 * - Do not rename the files: the thumbnails may still get new names because of the suffix containing the image dimensions, which may differ (for example when thumbnails are scaled, not cropped).
		 * In this last case, the thumbnails with the old dimensions are removed from the drive and from the WP’s post meta, so there is no need of keeping orphan WebP files that would stay on the drive for ever, even after the attachment is deleted from WP.
		 */
		foreach ( $this->process->get_media()->get_media_files() as $media_file ) {
			$this->old_webp_paths[] = imagify_path_to_webp( $media_file['path'] );
		}

		// Delete the old backup file and old WebP files.
		add_action( 'imagify_before_auto_optimization',         [ $this, 'delete_backup' ] );
		add_action( 'imagify_not_optimized_attachment_updated', [ $this, 'delete_backup' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Delete previous backup file and WebP files.
	 * This is done after the images have been already replaced by Enable Media Replace.
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
			// Delete old backup file.
			$filesystem->delete( $this->old_backup_path );
			$this->old_backup_path = false;
		}

		if ( ! empty( $this->old_webp_paths ) ) {
			// Delete old WebP files.
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
