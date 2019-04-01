<?php
namespace Imagify\Traits;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Trait to use to connect medias and database.
 * It also cache the results.
 * Classes using that trait must define a protected property $db_class_name (string) containing the media SQL DB class name.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
trait MediaRowTrait {

	/**
	 * The media SQL data row.
	 *
	 * @var    array
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $row;

	/**
	 * The media ID.
	 *
	 * @var    int
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $id;

	/**
	 * Get the row.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_row() {
		if ( isset( $this->row ) ) {
			return $this->row;
		}

		if ( ! $this->db_class_name || $this->id <= 0 ) {
			return $this->invalidate_row();
		}

		$this->row = $this->get_row_db_instance()->get( $this->id );

		if ( ! $this->row ) {
			return $this->invalidate_row();
		}

		return $this->row;
	}

	/**
	 * Update the row.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $data The data to update.
	 */
	public function update_row( $data ) {
		if ( ! $this->db_class_name || $this->id <= 0 ) {
			return;
		}

		$this->get_row_db_instance()->update( $this->id, $data );

		$this->reset_row_cache();
	}

	/**
	 * Delete the row.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function delete_row() {
		if ( ! $this->db_class_name || $this->id <= 0 ) {
			return;
		}

		$this->get_row_db_instance()->delete( $this->id );

		$this->invalidate_row();
	}

	/**
	 * Shorthand to get the DB table instance.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return \Imagify\DB\DBInterface The DB table instance.
	 */
	public function get_row_db_instance() {
		return call_user_func( [ $this->db_class_name, 'get_instance' ] );
	}

	/**
	 * Invalidate the row, by setting it to an empty array.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array The row.
	 */
	public function invalidate_row() {
		$this->row = [];
		return $this->row;
	}

	/**
	 * Reset the row cache.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return null The row.
	 */
	public function reset_row_cache() {
		$this->row = null;
		return $this->row;
	}
}
