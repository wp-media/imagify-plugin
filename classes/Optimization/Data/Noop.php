<?php
namespace Imagify\Optimization\Data;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Fallback class optimization data of "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class Noop implements DataInterface {

	/**
	 * Tell if the given entry can be accepted in the constructor.
	 * For example it can include `is_numeric( $id )` if the constructor accepts integers.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  mixed $id Whatever.
	 * @return bool
	 */
	public static function constructor_accepts( $id ) {
		return false;
	}

	/**
	 * Get the media instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return MediaInterface|false
	 */
	public function get_media() {
		return false;
	}

	/**
	 * Tell if the current media is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid() {
		return false;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION DATA ======================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Check if the main file is optimized (by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_optimized() {
		return false;
	}

	/**
	 * Check if the main file is optimized (NOT by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_already_optimized() {
		return false;
	}

	/**
	 * Check if the main file is optimized (by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_error() {
		return false;
	}

	/**
	 * Get the whole media optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array The data. See parent method for details.
	 */
	public function get_optimization_data() {
		return [
			'status' => '',
			'level'  => false,
			'sizes'  => [],
			'stats'  => [
				'original_size'  => 0,
				'optimized_size' => 0,
				'percent'        => 0,
			],
		];
	}

	/**
	 * Update the optimization data, level, and status for a size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $size The size name.
	 * @param array  $data The optimization data. See parent method for details.
	 */
	public function update_size_optimization_data( $size, array $data ) {}

	/**
	 * Delete the media optimization data, level, and status.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_optimization_data() {}

	/**
	 * Delete the optimization data for the given sizes.
	 * If all sizes are removed, all optimization data is deleted.
	 * Status and level are not modified nor removed if the "full" size is removed. This leaves the media in a Schrödinger state.
	 *
	 * @since  1.9.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $sizes A list of sizes to remove.
	 */
	public function delete_sizes_optimization_data( array $sizes ) {}

	/**
	 * Get the media's optimization level.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int|bool The optimization level. False if not optimized.
	 */
	public function get_optimization_level() {
		return false;
	}

	/**
	 * Get the media's optimization status (success or error).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The optimization status. An empty string if there is none.
	 */
	public function get_optimization_status() {
		return '';
	}

	/**
	 * Count number of optimized sizes.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int Number of optimized sizes.
	 */
	public function get_optimized_sizes_count() {
		return 0;
	}

	/**
	 * Get the original media's size (weight).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @return string|int
	 */
	public function get_original_size( $human_format = true, $decimals = 2 ) {
		return $human_format ? imagify_size_format( 0, $decimals ) : 0;
	}

	/**
	 * Get the file size of the full size file.
	 * If the WebP size is available, it is used.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @param  bool $use_webp     Use the WebP size if available.
	 * @return string|int
	 */
	public function get_optimized_size( $human_format = true, $decimals = 2, $use_webp = true ) {
		return $human_format ? imagify_size_format( 0, $decimals ) : 0;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OPTIMIZATION STATS ====================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get one or all statistics of a specific size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $size The thumbnail slug.
	 * @param  string $key  The specific data slug.
	 * @return array|string
	 */
	public function get_size_data( $size = 'full', $key = '' ) {
		return $key ? '' : [];
	}

	/**
	 * Get the overall statistics data or a specific one.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $key The specific data slug.
	 * @return array|string
	 */
	public function get_stats_data( $key = '' ) {
		return $key ? '' : [];
	}

	/**
	 * Get the optimized/original saving of the original image in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_saving_percent() {
		return round( (float) 0, 2 );
	}

	/**
	 * Get the overall optimized/original saving (original image + all thumbnails) in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_overall_saving_percent() {
		return round( (float) 0, 2 );
	}
}
