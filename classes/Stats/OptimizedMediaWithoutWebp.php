<?php
namespace Imagify\Stats;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class to get and cache the number of optimized media without webp versions.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class OptimizedMediaWithoutWebp implements StatInterface {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Name of the transient storing the cached result.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	const NAME = 'imagify_stat_without_webp';

	/**
	 * Launch hooks.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'imagify_after_optimize',      [ $this, 'maybe_clear_cache_after_optimization' ], 10, 2 );
		add_action( 'imagify_after_restore_media', [ $this, 'maybe_clear_cache_after_restoration' ], 10, 4 );
		add_action( 'imagify_delete_media',        [ $this, 'maybe_clear_cache_on_deletion' ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** GET/CACHE THE STAT ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the number of optimized media without webp versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_stat() {
		$ajax_post = \Imagify_Admin_Ajax_Post::get_instance();
		$stat      = 0;

		// Sum the counts of each context.
		foreach ( imagify_get_context_names() as $context ) {
			$stat += $ajax_post->get_bulk_instance( $context )->has_optimized_media_without_webp();
		}

		return $stat;
	}

	/**
	 * Get and cache the number of optimized media without webp versions.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_cached_stat() {
		$contexts = implode( '|', imagify_get_context_names() );
		$stat     = get_transient( static::NAME );

		if ( isset( $stat['stat'], $stat['contexts'] ) && $stat['contexts'] === $contexts ) {
			// The number is stored and the contexts are the same.
			return (int) $stat['stat'];
		}

		$stat = [
			'contexts' => $contexts,
			'stat'     => $this->get_stat(),
		];

		set_transient( static::NAME, $stat, 2 * DAY_IN_SECONDS );

		return $stat['stat'];
	}

	/**
	 * Clear the stat cache.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function clear_cache() {
		delete_transient( static::NAME );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Clear cache after optimizing a media.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process The optimization process.
	 * @param array            $item    The item being processed.
	 */
	public function maybe_clear_cache_after_optimization( $process, $item ) {
		if ( ! $process->get_media()->is_image() || false === get_transient( static::NAME ) ) {
			return;
		}

		$sizes     = $process->get_data()->get_optimization_data();
		$sizes     = isset( $sizes['sizes'] ) ? (array) $sizes['sizes'] : [];
		$new_sizes = array_flip( $item['sizes_done'] );
		$new_sizes = array_intersect_key( $sizes, $new_sizes );
		$size_name = 'full' . $process::WEBP_SUFFIX;

		if ( ! isset( $new_sizes['full'] ) && ! empty( $new_sizes[ $size_name ]['success'] ) ) {
			/**
			 * We just successfully generated the webp version of the full size.
			 * The full size was not optimized at the same time, that means it was optimized previously.
			 * Meaning: we just added a webp version to a media that was previously optimized, so there is one less optimized media without webp.
			 */
			$this->clear_cache();
			return;
		}

		if ( ! empty( $new_sizes['full']['success'] ) && empty( $new_sizes[ $size_name ]['success'] ) ) {
			/**
			 * We now have a new optimized media without webp.
			 */
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache after restoring a media.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process The optimization process.
	 * @param bool|WP_Error    $response The result of the operation: true on success, a WP_Error object on failure.
	 * @param array            $files    The list of files, before restoring them.
	 * @param array            $data     The optimization data, before deleting it.
	 */
	public function maybe_clear_cache_after_restoration( $process, $response, $files, $data ) {
		if ( ! $process->get_media()->is_image() || false === get_transient( static::NAME ) ) {
			return;
		}

		$sizes     = isset( $data['sizes'] ) ? (array) $data['sizes'] : [];
		$size_name = 'full' . $process::WEBP_SUFFIX;

		if ( ! empty( $sizes['full']['success'] ) && empty( $sizes[ $size_name ]['success'] ) ) {
			/**
			 * This media had no webp versions.
			 */
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache on media deletion.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process An optimization process.
	 */
	public function maybe_clear_cache_on_deletion( $process ) {
		if ( false === get_transient( static::NAME ) ) {
			return;
		}

		$data      = $process->get_data()->get_optimization_data();
		$sizes     = isset( $data['sizes'] ) ? (array) $data['sizes'] : [];
		$size_name = 'full' . $process::WEBP_SUFFIX;

		if ( ! empty( $sizes['full']['success'] ) && empty( $sizes[ $size_name ]['success'] ) ) {
			/**
			 * This media had no webp versions.
			 */
			$this->clear_cache();
		}
	}
}
