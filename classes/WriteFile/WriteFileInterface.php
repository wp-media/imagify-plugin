<?php
namespace Imagify\WriteFile;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to add and remove contents to a file.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface WriteFileInterface {

	/**
	 * Add new contents to the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True on success. A \WP_Error object on error.
	 */
	public function add();

	/**
	 * Remove the related contents from the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True on success. A \WP_Error object on error.
	 */
	public function remove();

	/**
	 * Get the path to the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_file_path();

	/**
	 * Tell if the file is writable.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool|\WP_Error True if writable. A \WP_Error object if not.
	 */
	public function is_file_writable();

	/**
	 * Get new contents to write into the file.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_new_contents();
}
