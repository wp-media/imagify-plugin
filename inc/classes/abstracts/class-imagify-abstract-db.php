<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify DB base class.
 *
 * @source  https://gist.github.com/pippinsplugins/e220a7f0f0f2fbe64608
 * @since   1.5
 */
abstract class Imagify_Abstract_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * The single instance of the class.
	 *
	 * @access  protected
	 * @since   1.5
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The name of our database table.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.5
	 */
	public $table_name;

	/**
	 * The version of our database table.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.5
	 */
	public $version;

	/**
	 * The name of the primary column.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.5
	 */
	public $primary_key;

	/**
	 * Get things started.
	 *
	 * @access  public
	 * @since   1.5
	 */
	protected function __construct() {}

	/**
	 * Get the main Instance.
	 *
	 * @access  public
	 * @since   1.6.5
	 * @author  Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Whitelist of columns.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @return  array
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Default column values.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @return  array
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieve a row by the primary key.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $row_id A primary key.
	 * @return object
	 */
	public function get( $row_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ), ARRAY_A ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Retrieve a row by a specific column / value.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $column A column name.
	 * @param  string $row_id A value.
	 * @return object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ), ARRAY_A ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Retrieve a specific column's value by the primary key.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $column A column name.
	 * @param  string $row_id A primary key.
	 * @return string
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $column       A column name.
	 * @param  string $column_where A column name.
	 * @param  string $column_value A value.
	 * @return string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column       = esc_sql( $column );
		$column_where = esc_sql( $column_where );
		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Insert a new row.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $data New data.
	 * @return int
	 */
	public function insert( $data ) {
		global $wpdb;

		// Set default values.
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Initialise column format array.
		$column_formats = $this->get_columns();

		// Force fields to lower case.
		$data = array_change_key_case( $data );

		// White list columns.
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data.
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		return $wpdb->insert_id;
	}

	/**
	 * Update a row.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $row_id A primary key.
	 * @param  array  $data   New data.
	 * @param  string $where  A column name.
	 * @return bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {
		global $wpdb;

		// Row ID must be positive integer.
		$row_id = absint( $row_id );

		if ( ! $row_id ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array.
		$column_formats = $this->get_columns();

		// Force fields to lower case.
		$data = array_change_key_case( $data );

		// White list columns.
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data.
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( false === (bool) $this->get_column( $this->primary_key, $row_id ) ) {
			$this->insert( $data );
			return true;
		}

		return (bool) $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats );
	}

	/**
	 * Delete a row identified by the primary key.
	 *
	 * @access  public
	 * @since   1.5
	 *
	 * @param  string $row_id A primary key.
	 * @return bool
	 */
	public function delete( $row_id = 0 ) {
		global $wpdb;

		// Row ID must be positive integer.
		$row_id = absint( $row_id );

		if ( ! $row_id ) {
			return false;
		}

		return (bool) $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Check if the given table exists.
	 *
	 * @since  1.5
	 *
	 * @param  string $table The table name.
	 * @return bool          True if the table name exists.
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '%s'", $table ) ) === $table;
	}

}
