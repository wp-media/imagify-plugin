<?php
namespace Imagify\Optimization\Data;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to use to handle the optimization data of "media groups" (aka attachments).
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface DataInterface {

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
	public static function constructor_accepts( $id );

	/**
	 * Get the media instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return MediaInterface|false
	 */
	public function get_media();

	/**
	 * Tell if the current media is valid.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_valid();


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
	public function is_optimized();

	/**
	 * Check if the main file is optimized (NOT by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_already_optimized();

	/**
	 * Check if the main file is optimized (by Imagify).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the media is optimized.
	 */
	public function is_error();

	/**
	 * Get the whole media optimization data.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array {
	 *     The data.
	 *
	 *     @type string   $status The optimization status of the whole media: 'success', 'already_optimized', or 'error'.
	 *                            It is the same as the main file’s status.
	 *     @type int|bool $level  The optimization level (0=normal, 1=aggressive, 2=ultra). False if not set.
	 *     @type array    $sizes  {
	 *         A list of size data, keyed by size name, and containing:
	 *
	 *         @type bool   $success        Whether the optimization has been successful.
	 *         If a success:
	 *         @type int    $original_size  The file size before optimization.
	 *         @type int    $optimized_size The file size after optimization.
	 *         @type int    $percent        Saving in percent.
	 *         If an error or 'already_optimized':
	 *         @type string $error          An error message.
	 *     }
	 *     @type array    $stats  {
	 *         @type int $original_size  Overall size before optimization.
	 *         @type int $optimized_size Overall size after optimization.
	 *         @type int $percent        Overall saving in percent.
	 *     }
	 * }
	 */
	public function get_optimization_data();

	/**
	 * Update the optimization data, level, and status for a size.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string $size The size name.
	 * @param array  $data {
	 *     The optimization data.
	 *
	 *     @type int    $level          The optimization level.
	 *     @type string $status         The status: 'success', 'already_optimized', 'error'.
	 *     @type bool   $success        True if successfully optimized. False on error or if already optimized.
	 *     @type string $error          An error message.
	 *     @type int    $original_size  The weight of the file, before optimization.
	 *     @type int    $optimized_size The weight of the file, after optimization.
	 * }
	 */
	public function update_size_optimization_data( $size, array $data );

	/**
	 * Delete the media optimization data, level, and status.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_optimization_data();

	/**
	 * Get the media's optimization level.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int|bool The optimization level. False if not optimized.
	 */
	public function get_optimization_level();

	/**
	 * Get the media's optimization status (success or error).
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string The optimization status. An empty string if there is none.
	 */
	public function get_optimization_status();

	/**
	 * Count number of optimized sizes.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int Number of optimized sizes.
	 */
	public function get_optimized_sizes_count();

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
	public function get_original_size( $human_format = true, $decimals = 2 );

	/**
	 * Get the file size of the full size file.
	 * If the webp size is available, it is used.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool $human_format True to display the image human format size (1Mb).
	 * @param  int  $decimals     Precision of number of decimal places.
	 * @param  bool $use_webp     Use the webp size if available.
	 * @return string|int
	 */
	public function get_optimized_size( $human_format = true, $decimals = 2, $use_webp = true );


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
	public function get_size_data( $size = 'full', $key = '' );

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
	public function get_stats_data( $key = '' );

	/**
	 * Get the optimized/original saving of the original image in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_saving_percent();

	/**
	 * Get the overall optimized/original saving (original image + all thumbnails) in percent.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return float A 2-decimals float.
	 */
	public function get_overall_saving_percent();
}
