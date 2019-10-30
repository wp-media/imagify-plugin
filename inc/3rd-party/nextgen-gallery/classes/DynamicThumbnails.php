<?php
namespace Imagify\ThirdParty\NGG;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the optimization of thumbnails dynamically generated.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class DynamicThumbnails {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * The queue containing the sizes, grouped by image ID.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $sizes = [];

	/**
	 * A list of NGG image objects, grouped by image ID.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $images = [];

	/**
	 * Add a dynamically generated thumbnail to the background process queue.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param object $image A NGG image object.
	 * @param string $size  The thumbnail size name.
	 */
	public function push_to_queue( $image, $size ) {
		static $done = false;

		if ( empty( $image->pid ) ) {
			// WUT?
			return;
		}

		if ( empty( static::$sizes[ $image->pid ] ) ) {
			static::$sizes[ $image->pid ] = [];
		}

		static::$sizes[ $image->pid ][] = $size;

		if ( empty( static::$images[ $image->pid ] ) ) {
			static::$images[ $image->pid ] = $image;
		}

		if ( $done ) {
			return;
		}

		$done = true;

		add_action( 'shutdown', [ $this, 'optimize' ], 555 ); // Must come before 666 (see Imagify_Abstract_Background_Process->init()).
	}

	/**
	 * Launch the optimizations.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function optimize() {
		if ( empty( static::$sizes ) ) {
			// ¯\(°_o)/¯
			return;
		}

		foreach ( static::$sizes as $image_id => $sizes ) {
			if ( empty( static::$images[ $image_id ] ) ) {
				// ¯\(°_o)/¯
				continue;
			}

			$sizes = array_filter( $sizes );

			if ( empty( $sizes ) ) {
				continue;
			}

			$process = imagify_get_optimization_process( static::$images[ $image_id ], 'ngg' );

			if ( ! $process->is_valid() || ! $process->get_media()->is_supported() ) {
				continue;
			}

			$data = $process->get_data();

			if ( ! $data->is_optimized() ) {
				// The main image is not optimized.
				continue;
			}

			$sizes = array_unique( $sizes );

			foreach ( $sizes as $i => $size ) {
				$size_status = $data->get_size_data( $size, 'success' );

				if ( $size_status ) {
					// This thumbnail has already been processed.
					unset( $sizes[ $i ] );
				}
			}

			if ( empty( $sizes ) ) {
				continue;
			}

			$optimization_level = $process->get_data()->get_optimization_level();
			$args               = [
				'hook_suffix' => 'optimize_generated_image',
			];

			$process->optimize_sizes( $sizes, $optimization_level, $args );
		}
	}
}
