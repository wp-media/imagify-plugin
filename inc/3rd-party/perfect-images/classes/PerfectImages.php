<?php

namespace Imagify\ThirdParty\PerfectImages;

use Imagify\Optimization\Process\WP as Process;
use Imagify\Optimization\Data\WP as Data;
use Imagify\Media\WP as Media;
use Imagify_Filesystem;
use Imagify_Options;

/**
 * Class that handles compatibility with Perfect Images plugin.
 */
class PerfectImages {

	/**
	 * The single instance of this class.
	 *
	 * @var PerfectImages
	 */
	private static $instance;

	/**
	 * An Imagify Filesystem instance.
	 *
	 * @var Imagify_Filesystem
	 */
	private $filesystem;

	/**
	 * Retina sizes to be optimized.
	 *
	 * @var array
	 */
	private $retina_sizes = [];

	/**
	 * Get the main Instance.
	 *
	 * @return PerfectImages Main instance.
	 */
	public static function get_instance(): PerfectImages {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * The constructor.
	 */
	protected function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

	/**
	 * Initialize the hooks.
	 */
	public function init() {
		add_action( 'wr2x_before_regenerate', [ $this, 'restore_originally_uploaded_image' ] );
		add_action( 'wr2x_retina_file_added', [ $this, 'add_retina_size' ], 10, 3 );
		add_action( 'wr2x_generate_retina', [ $this, 'optimize_retina_sizes' ] );
		add_action( 'wr2x_generate_retina', [ $this, 'reset_optimized_full_size_image' ] );
		add_action( 'imagify_media_files', [ $this, 'add_retina_sizes_meta' ] );

		add_action( 'wr2x_retina_file_removed', [ $this, 'remove_retina_webp_size' ], 10, 2 );
		add_action( 'wr2x_retina_file_removed', [ $this, 'remove_imagify_retina_data' ], 10, 2 );

		add_action( 'wr2x_before_generate_thumbnails', [ $this, 'restore_originally_uploaded_image' ] );
		add_action( 'wr2x_generate_thumbnails', [ $this, 'reoptimize_regenerated_images' ] );
		add_action( 'wr2x_generate_thumbnails', [ $this, 'reset_optimized_full_size_image' ] );
	}

	/**
	 * Restore the optimized full-sized file and replace it by the original backup file.
	 *
	 * This is to have the original user-uploaded (rather than the optimized full-size) image is in place
	 * when Perfect Images (re)generates any new images.
	 *
	 * @param int $media_id A media attachment ID.
	 */
	public function restore_originally_uploaded_image( int $media_id ) {
		$media = new Media( $media_id );

		$fullsize_path = $media->get_raw_fullsize_path();

		if ( empty( $fullsize_path ) ) {
			return;
		}

		$original_path = $media->get_original_path();

		if ( empty( $original_path ) ) {
			return;
		}

		/** If these are not the same, WP will rebuild the full-size from the original along with the thumbnails. */
		if ( $fullsize_path !== $original_path ) {
			return;
		}

		$backup_path = $media->get_raw_backup_path();

		if ( empty( $backup_path ) ) {
			return;
		}

		$data = new Data( $media );

		if ( ! $data->is_optimized() ) {
			return;
		}

		$tmp_file_path = $this->get_temporary_file_path( $fullsize_path );

		if ( $this->filesystem->exists( $fullsize_path ) ) {
			$this->filesystem->move( $fullsize_path, $tmp_file_path, true );
		}

		$this->filesystem->copy( $backup_path, $fullsize_path );
	}

	/**
	 * Add a newly generated retina size that will need to be optimized.
	 *
	 * @hooked wr2x_retina_file_added
	 *
	 * @param int    $media_id    The media attachment ID.
	 * @param string $retina_file The retina filename.
	 * @param string $size_name   The size name.
	 */
	public function add_retina_size( int $media_id, string $retina_file, string $size_name ) {
		$this->retina_sizes[] = [
			'media_id'    => $media_id,
			'retina_file' => $retina_file,
			'size_name'   => $size_name,
		];
	}

	/**
	 * Optimize newly generated retina sizes.
	 *
	 * @hooked wr2x_generate_retina
	 *
	 * @param int $media_id The attachment id of the retina images to optimize.
	 */
	public function optimize_retina_sizes( int $media_id ) {
		$sizes = [];

		foreach ( $this->retina_sizes as $size ) {
			if ( $media_id === $size['media_id'] ) {
				$sizes[] = $size['size_name'] . '@2x';
			}
		}

		if ( empty( $sizes ) ) {
			return;
		}

		$process = new Process( new Data( new Media( $media_id ) ) );

		$media_opt_level    = $process->get_data()->get_optimization_level();
		$optimization_level = $media_opt_level ?: Imagify_Options::get_instance()->get( 'optimization_level' );

		$process->optimize_sizes( $sizes, $optimization_level );
	}

	/**
	 * Filter a Media's get_media_files() response to include retina size data.
	 *
	 * @hooked imagify_media_files
	 *
	 * @param array $sizes The Media's size data.
	 *
	 * @return array Sizes data that includes retina sizes.
	 */
	public function add_retina_sizes_meta( array $sizes ): array {
		if ( ! function_exists( 'wr2x_get_retina' ) ) {
			return $sizes;
		}

		foreach ( $sizes as $size => $size_data ) {
			$retina_path = wr2x_get_retina( $size_data['path'] );

			if ( empty( $retina_path ) ) {
				continue;
			}

			$sizes[ $size . '@2x' ] = [
				'size'      => $size . '@2x',
				'path'      => $retina_path,
				'width'     => $size_data['width'] * 2,
				'height'    => $size_data['height'] * 2,
				'mime-type' => $size_data['mime-type'],
				'disabled'  => false,
			];
		}

		return $sizes;
	}

	/**
	 * Remove a retina-related webp file whose Perfect Images retina version has been deleted.
	 *
	 * @hooked wr2x_retina_file_removed
	 *
	 * @param int    $media_id    The media attachment ID.
	 * @param string $retina_file The retina filepath.
	 */
	public function remove_retina_webp_size( int $media_id, string $retina_file ) {
		$meta = wp_get_attachment_metadata( $media_id );

		$retina_webp_filepath = $this->get_retina_webp_filepath( $meta['file'], $retina_file );

		if ( file_exists( $retina_webp_filepath ) ) {
			unlink( $retina_webp_filepath );
		}
	}

	/**
	 * Remove retina-related imagify data concerning a deleted Perfect Images retina file.
	 *
	 * @hooked wr2x_retina_file_removed
	 *
	 * @param int    $media_id    The media attachment ID.
	 * @param string $retina_file The retina filepath.
	 */
	public function remove_imagify_retina_data( int $media_id, string $retina_file ) {
		$meta               = wp_get_attachment_metadata( $media_id );
		$retina_file_info   = pathinfo( $retina_file );
		$original_size_name = preg_replace(
			'/@2x/',
			'',
			$retina_file_info['filename']
		) . '.' . $retina_file_info['extension'];

		$imagify_size_names = $this->get_retina_imagify_data_size_names( $meta['sizes'], $original_size_name );

		if ( empty( $imagify_size_names ) ) {
			return;
		}

		$imagify_data = new Data( new Media( $media_id ) );
		$imagify_data->delete_sizes_optimization_data( $imagify_size_names );
	}

	/**
	 * Reoptimize regenerated thumbnail images.
	 *
	 * @param int $media_id The attachment ID of the media being processed.
	 */
	public function reoptimize_regenerated_images( int $media_id ) {
		$meta    = wp_get_attachment_metadata( $media_id );
		$process = new Process( new Data( new Media( $media_id ) ) );

		$sizes         = isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ? $meta['sizes'] : [];
		$media         = $process->get_media();
		$fullsize_path = $media->get_raw_fullsize_path();

		/** If full-size and original are not the same, we will need to re-optimize the full size, too. */
		if ( $fullsize_path && $media->get_original_path() !== $fullsize_path ) {
			$sizes['full'] = [];
		}

		if ( ! $sizes ) {
			return;
		}

		/**
		 * Optimize the sizes that have been regenerated.
		 */
		// If the media has WebP versions, recreate them for the sizes that have been regenerated.
		$optimization_data = $process->get_data()->get_optimization_data();

		if ( ! empty( $optimization_data['sizes'] ) ) {
			foreach ( array_keys( $optimization_data['sizes'] ) as $size_name ) {
				$non_webp_size_name = $process->is_size_webp( $size_name );

				if ( ! $non_webp_size_name || ! isset( $sizes[ $non_webp_size_name ] ) ) {
					continue;
				}

				// Add the WebP size.
				$sizes[ $size_name ] = [];
			}
		}

		$sizes              = array_keys( $sizes );
		$optimization_level = $process->get_data()->get_optimization_level();

		// Delete related optimization data or nothing will be optimized.
		$process->get_data()->delete_sizes_optimization_data( $sizes );
		$process->optimize_sizes( $sizes, $optimization_level );
	}

	/**
	 * Put the optimized full-sized file back.
	 *
	 * @param int $media_id A media attachment ID.
	 */
	public function reset_optimized_full_size_image( int $media_id ) {
		$media = new Media( $media_id );

		$file_path     = $media->get_raw_original_path();
		$tmp_file_path = $this->get_temporary_file_path( $file_path );

		if ( ! $this->filesystem->exists( $tmp_file_path ) ) {
			return;
		}

		$this->filesystem->move( $tmp_file_path, $file_path, true );
	}


	/**
	 * Get the retina webp filepath associated with a Perfect Images retina file.
	 *
	 * @param string $attachment_file The attachment file from WP's attachment meta.
	 * @param string $retina_file     The retina file from Perfect Images.
	 *
	 * @return string The full retina-webp file path.
	 */
	private function get_retina_webp_filepath( string $attachment_file, string $retina_file ): string {
		$pathinfo  = pathinfo( $attachment_file );
		$directory = $pathinfo['dirname'];
		$uploads   = wp_upload_dir();
		$basedir   = $uploads['basedir'];

		return trailingslashit( $basedir ) . trailingslashit( $directory ) . $retina_file . '.webp';
	}

	/**
	 * Get size names as used in an Imagify::AbstractData instance for retina images.
	 *
	 * Given an array of size data items from WP's attachment meta,
	 * and the filename of the original image derived from a Perfect Images retina filename,
	 * we get a list of size names for all retina-size images that will be found in Imagify's Data instance.
	 *
	 * @param array  $sizes              Sizes from WP's attachment meta.
	 * @param string $original_size_name The original image filename from which Perfect Images has created a retina filename.
	 *
	 * @return array A list of image size names related to the retina file in an Imagify Data Instance.
	 */
	private function get_retina_imagify_data_size_names( array $sizes, string $original_size_name ): array {
		$imagify_size_names = [];

		foreach ( $sizes as $size => $size_data ) {
			if ( $original_size_name === $size_data['file'] ) {
				$imagify_size_names[] = $size . '@2x';
				$imagify_size_names[] = $size . '@2x@imagify-webp';
			}
		}

		return $imagify_size_names;
	}

	/**
	 * Get the path to the temporary file.
	 *
	 * @param string $file_path The optimized full-sized file path.
	 *
	 * @return string A temporary file path for the optimized full-sized file.
	 */
	private function get_temporary_file_path( string $file_path ): string {
		return $file_path . '_backup';
	}
}
