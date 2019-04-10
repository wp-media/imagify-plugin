<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
	const VERSION = '1.0.1';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
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
	protected $table_version = 100;

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
			'folder_id' => '%d',
			'path'      => '%s',
			'active'    => '%d',
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
			'folder_id' => 0,
			'path'      => '',
			'active'    => 0,
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
			path varchar(191) NOT NULL default '',
			active tinyint(1) unsigned NOT NULL default 0,
			PRIMARY KEY  (folder_id),
			UNIQUE KEY path (path),
			KEY active (active)";
	}

	/**
	 * Tell if folders are selected in the plugin settings.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function has_active_folders() {
		global $wpdb;

		$column = esc_sql( $this->get_primary_key() );

		return (bool) $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE active = 1 LIMIT 1;" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Retrieve active folders (checked in the settings).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @return array
	 */
	public function get_active_folders_column( $column_select ) {
		global $wpdb;

		$column = esc_sql( $column_select );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE active = 1;" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}

	/**
	 * Retrieve active folders (checked in the settings) by the specified column / values.
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
	public function get_active_folders_column_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where IN ( $column_values ) AND active = 1;" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}

	/**
	 * Retrieve active folders (checked in the settings) by the specified column / values.
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
	public function get_active_folders_column_not_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where NOT IN ( $column_values ) AND active = 1;" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}

	/**
	 * Retrieve not active folders (not checked in the settings).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @return array
	 */
	public function get_inactive_folders_column( $column_select ) {
		global $wpdb;

		$column = esc_sql( $column_select );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE active != 1;" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}
}
