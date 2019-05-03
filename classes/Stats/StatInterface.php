<?php
namespace Imagify\Stats;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to use to get and cache a stat.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface StatInterface {

	/**
	 * Get the stat value.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return mixed
	 */
	public function get_stat();

	/**
	 * Get and cache the stat value.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return mixed
	 */
	public function get_cached_stat();

	/**
	 * Clear the stat cache.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function clear_cache();
}
