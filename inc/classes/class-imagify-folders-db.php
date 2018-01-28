<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * DB class that handles files in "custom folders".
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Folders_DB extends Imagify_Abstract_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * The single instance of the class.
	 *
	 * @since  1.7
	 * @access protected
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * The suffix used in the name of the database table (so, without the wpdb prefix).
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $table = 'imagify_folders';

	/**
	 * The version of our database table.
	 *
	 * @var    int
	 * @since  1.7
	 * @access protected
	 */
	protected $table_version = 10;

	/**
	 * Tell if the table is the same for each site of a Multisite.
	 *
	 * @var    bool
	 * @since  1.7
	 * @access protected
	 */
	protected $table_is_global = true;

	/**
	 * The name of the primary column.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $primary_key = 'folder_id';

	/**
	 * Get the main Instance.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
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
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'folder_id'          => '%d',
			'path'               => '%s',
			'optimization_level' => '%d',
		);
	}

	/**
	 * Default column values.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'folder_id'          => 0,
			'path'               => '',
			'optimization_level' => null,
		);
	}

	/**
	 * Get the query to create the table fields.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	protected function get_table_schema() {
		return "
			folder_id bigint(20) unsigned NOT NULL auto_increment,
			path varchar(100) NOT NULL default '',
			optimization_level int(1) default NULL,
			PRIMARY KEY (folder_id),
			UNIQUE KEY path (path),
			KEY optimization_level (optimization_level)";
	}

	/**
	 * Retrieve folders "in sync" (where an optimization level is set).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @return array
	 */
	public function get_optimized_folders_column( $column_select ) {
		global $wpdb;

		$column = esc_sql( $column_select );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE optimization_level IS NOT NULL;" ); // WPCS: unprepared SQL ok.

		if ( ! $result ) {
			return array();
		}

		foreach ( $result as $i => $value ) {
			$result[ $i ] = $this->cast( $value, $column_select );
		}

		return $result;
	}

	/**
	 * Retrieve folders "in sync" (where an optimization level is set) by the specified column / values.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @param  string $column_where  A column name.
	 * @param  array  $column_values An array of values.
	 * @return array
	 */
	public function get_optimized_folders_column_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where IN ( $column_values ) AND optimization_level IS NOT NULL;" ); // WPCS: unprepared SQL ok.

		if ( ! $result ) {
			return array();
		}

		foreach ( $result as $i => $value ) {
			$result[ $i ] = $this->cast( $value, $column_select );
		}

		return $result;
	}

	/**
	 * Retrieve folders "in sync" (where an optimization level is set) by the specified column / values.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @param  string $column_where  A column name.
	 * @param  array  $column_values An array of values.
	 * @return array
	 */
	public function get_optimized_folders_column_not_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where NOT IN ( $column_values ) AND optimization_level IS NOT NULL;" ); // WPCS: unprepared SQL ok.

		if ( ! $result ) {
			return array();
		}

		foreach ( $result as $i => $value ) {
			$result[ $i ] = $this->cast( $value, $column_select );
		}

		return $result;
	}

	/**
	 * Retrieve folders "not in sync" (where an optimization level is not set).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @return array
	 */
	public function get_unoptimized_folders_column( $column_select ) {
		global $wpdb;

		$column = esc_sql( $column_select );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE optimization_level IS NULL;" ); // WPCS: unprepared SQL ok.

		if ( ! $result ) {
			return array();
		}

		foreach ( $result as $i => $value ) {
			$result[ $i ] = $this->cast( $value, $column_select );
		}

		return $result;
	}
}
