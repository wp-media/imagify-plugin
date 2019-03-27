<?php
namespace Imagify\DB;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Interface to interact with the database.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
interface DBInterface {

	/**
	 * Get the main Instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return DBInterface Main instance.
	 */
	public static function get_instance();

	/**
	 * Retrieve a row by the primary key.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $row_id A primary key.
	 * @return array
	 */
	public function get( $row_id );

	/**
	 * Update a row.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int    $row_id A primary key.
	 * @param  array  $data   New data.
	 * @param  string $where  A column name.
	 * @return bool
	 */
	public function update( $row_id, $data = [], $where = '' );

	/**
	 * Delete a row identified by the primary key.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $row_id A primary key.
	 * @return bool
	 */
	public function delete( $row_id );

	/**
	 * Default column values.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_column_defaults();

	/**
	 * Get the primary column name.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_primary_key();
}
