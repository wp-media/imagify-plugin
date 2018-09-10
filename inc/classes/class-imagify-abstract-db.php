<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify DB base class.
 *
 * @since  1.5
 * @source https://gist.github.com/pippinsplugins/e220a7f0f0f2fbe64608
 */
abstract class Imagify_Abstract_DB extends Imagify_Abstract_DB_Deprecated {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.1';

	/**
	 * Suffix used in the name of the options that store the table versions.
	 *
	 * @var string
	 * @since 1.7
	 */
	const TABLE_VERSION_OPTION_SUFFIX = '_db_version';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.5
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
	protected $table;

	/**
	 * The version of our database table.
	 *
	 * @var    int
	 * @since  1.5
	 * @since  1.7 Not public anymore, now an integer.
	 * @access protected
	 */
	protected $table_version;

	/**
	 * Tell if the table is the same for each site of a Multisite.
	 *
	 * @var    bool
	 * @since  1.7
	 * @access protected
	 */
	protected $table_is_global;

	/**
	 * The name of the primary column.
	 *
	 * @var    string
	 * @since  1.5
	 * @since  1.7 Not public anymore.
	 * @access protected
	 */
	protected $primary_key;

	/**
	 * The name of our database table.
	 *
	 * @var    string
	 * @since  1.5
	 * @since  1.7 Not public anymore.
	 * @access protected
	 */
	protected $table_name = '';

	/**
	 * Tell if the table has been created.
	 *
	 * @var    bool
	 * @since  1.7
	 * @access protected
	 */
	protected $table_created = false;

	/**
	 * Stores the list of columns that must be (un)serialized.
	 *
	 * @var    array
	 * @since  1.7
	 * @access protected
	 */
	protected $to_serialize;

	/**
	 * Get things started.
	 *
	 * @since  1.5
	 * @access protected
	 */
	protected function __construct() {
		global $wpdb;

		$prefix = $this->table_is_global ? $wpdb->base_prefix : $wpdb->prefix;

		$this->table_name = $prefix . $this->table;

		if ( ! $this->table_is_up_to_date() ) {
			/**
			 * The option doesn't exist or is not up-to-date: we must upgrade the table before declaring it ready.
			 * See self::maybe_upgrade_table() for the upgrade.
			 */
			return;
		}

		$this->set_table_ready();
	}

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.5
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
	 * Init:
	 * - Launch hooks.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_upgrade_table' ) );
	}

	/**
	 * Tell if we can work with the tables.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function can_operate() {
		return $this->table_created;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TABLE SPECIFICS ========================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Whitelist of columns.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @return  array
	 */
	abstract public function get_columns();

	/**
	 * Default column values.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @return  array
	 */
	abstract public function get_column_defaults();

	/**
	 * Get the query to create the table fields.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	abstract protected function get_table_schema();


	/** ----------------------------------------------------------------------------------------- */
	/** QUERIES ================================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the table is empty or not.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool True if the table contains at least one row.
	 */
	public function has_items() {
		global $wpdb;

		$column = esc_sql( $this->primary_key );

		return (bool) $wpdb->get_var( "SELECT $column FROM $this->table_name LIMIT 1;" ); // WPCS: unprepared SQL ok.
	}

	/**
	 * Retrieve a row by the primary key.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param  string $row_id A primary key.
	 * @return array
	 */
	public function get( $row_id ) {
		if ( $row_id <= 0 ) {
			return array();
		}

		return $this->get_by( $this->primary_key, $row_id );
	}

	/**
	 * Retrieve a row by a specific column / value.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param  string $column_where A column name.
	 * @param  mixed  $column_value A value.
	 * @return array
	 */
	public function get_by( $column_where, $column_value ) {
		global $wpdb;

		$placeholder   = $this->get_placeholder( $column_where );
		$column_where  = esc_sql( $column_where );

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column_where = $placeholder LIMIT 1;", $column_value ), ARRAY_A ); // WPCS: unprepared SQL ok, PreparedSQLPlaceholders replacement count ok.

		return (array) $this->cast_row( $result );
	}

	/**
	 * Retrieve a row by the specified column / values.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_where  A column name.
	 * @param  array  $column_values An array of values.
	 * @return array
	 */
	public function get_in( $column_where, $column_values ) {
		global $wpdb;

		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_row( "SELECT * FROM $this->table_name WHERE $column_where IN ( $column_values ) LIMIT 1;", ARRAY_A ); // WPCS: unprepared SQL ok.

		return (array) $this->cast_row( $result );
	}

	/**
	 * Retrieve a var by the primary key.
	 * Previously named get_column().
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @param  string $row_id        A primary key.
	 * @return mixed
	 */
	public function get_var( $column_select, $row_id ) {
		if ( $row_id <= 0 ) {
			return false;
		}

		return $this->get_var_by( $column_select, $this->primary_key, $row_id );
	}

	/**
	 * Retrieve a var by the specified column / value.
	 * Previously named get_column_by().
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @param  string $column_where  A column name.
	 * @param  string $column_value  A value.
	 * @return mixed
	 */
	public function get_var_by( $column_select, $column_where, $column_value ) {
		global $wpdb;

		$placeholder  = $this->get_placeholder( $column_where );
		$column       = esc_sql( $column_select );
		$column_where = esc_sql( $column_where );

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = $placeholder LIMIT 1;", $column_value ) ); // WPCS: unprepared SQL ok, PreparedSQLPlaceholders replacement count ok.

		return $this->cast( $result, $column_select );
	}

	/**
	 * Retrieve a var by the specified column / values.
	 * Previously named get_column_in().
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $column_select A column name.
	 * @param  string $column_where  A column name.
	 * @param  array  $column_values An array of values.
	 * @return mixed
	 */
	public function get_var_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_var( "SELECT $column FROM $this->table_name WHERE $column_where IN ( $column_values ) LIMIT 1;" ); // WPCS: unprepared SQL ok.

		return $this->cast( $result, $column_select );
	}

	/**
	 * Retrieve values by the specified column / values.
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
	public function get_column_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where IN ( $column_values );" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}

	/**
	 * Retrieve values by the specified column / values.
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
	public function get_column_not_in( $column_select, $column_where, $column_values ) {
		global $wpdb;

		$column        = esc_sql( $column_select );
		$column_where  = esc_sql( $column_where );
		$column_values = Imagify_DB::prepare_values_list( $column_values );

		$result = $wpdb->get_col( "SELECT $column FROM $this->table_name WHERE $column_where NOT IN ( $column_values );" ); // WPCS: unprepared SQL ok.

		return $this->cast_col( $result, $column_select );
	}

	/**
	 * Insert a new row.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param  string $data New data.
	 * @return int          The ID.
	 */
	public function insert( $data ) {
		global $wpdb;

		// Initialise column format array.
		$column_formats = $this->get_columns();

		// Set default values.
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Force fields to lower case.
		$data = array_change_key_case( $data );

		// White list columns.
		$data = array_intersect_key( $data, $column_formats );

		// Maybe serialize some values.
		$data = $this->serialize_columns( $data );

		// Reorder $column_formats to match the order of columns given in $data.
		$column_formats = array_merge( $data, $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a row.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param  int    $row_id A primary key.
	 * @param  array  $data   New data.
	 * @param  string $where  A column name.
	 * @return bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {
		global $wpdb;

		if ( $row_id <= 0 ) {
			return false;
		}

		if ( ! $this->get( $row_id ) ) {
			$this->insert( $data );
			return true;
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

		// Maybe serialize some values.
		$data = $this->serialize_columns( $data );

		// Reorder $column_formats to match the order of columns given in $data.
		$column_formats = array_merge( $data, $column_formats );

		return (bool) $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats, $this->get_placeholder( $where ) );
	}

	/**
	 * Delete a row identified by the primary key.
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param  string $row_id A primary key.
	 * @return bool
	 */
	public function delete( $row_id = 0 ) {
		global $wpdb;

		if ( $row_id <= 0 ) {
			return false;
		}

		$placeholder = $this->get_placeholder( $this->primary_key );

		return (bool) $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = $placeholder", $row_id ) ); // WPCS: unprepared SQL ok, PreparedSQLPlaceholders replacement count ok.
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TABLE CREATION ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Maybe create/upgrade the table in the database.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function maybe_upgrade_table() {
		global $wpdb;

		if ( $this->table_is_up_to_date() ) {
			// The table has the right version.
			$this->set_table_ready();
			return;
		}

		// Create the table.
		$this->create_table();
	}

	/**
	 * Create/Upgrade the table in the database.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function create_table() {
		if ( ! Imagify_DB::create_table( $this->get_table_name(), $this->get_table_schema() ) ) {
			// Failure.
			$this->set_table_not_ready();
			$this->delete_db_version();
			return;
		}

		// Table successfully created/upgraded.
		$this->set_table_ready();
		$this->update_db_version();
	}

	/**
	 * Set various properties to tell the table is ready to be used.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	protected function set_table_ready() {
		global $wpdb;

		$this->table_created  = true;
		$wpdb->{$this->table} = $this->table_name;

		if ( $this->table_is_global ) {
			$wpdb->global_tables[] = $this->table;
		} else {
			$wpdb->tables[] = $this->table;
		}
	}

	/**
	 * Unset various properties to tell the table is NOT ready to be used.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	protected function set_table_not_ready() {
		global $wpdb;

		$this->table_created  = false;
		unset( $wpdb->{$this->table} );

		if ( $this->table_is_global ) {
			$wpdb->global_tables = array_diff( $wpdb->global_tables, array( $this->table ) );
		} else {
			$wpdb->tables = array_diff( $wpdb->tables, array( $this->table ) );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TABLE VERSION =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the table version.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int
	 */
	public function get_table_version() {
		return $this->table_version;
	}

	/**
	 * Tell if the table is up-to-date (we don't "downgrade" the tables).
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function table_is_up_to_date() {
		return $this->get_db_version() >= $this->get_table_version();
	}

	/**
	 * Get the table version stored in DB.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int|bool The version. False if not set yet.
	 */
	public function get_db_version() {
		$option_name = $this->table . self::TABLE_VERSION_OPTION_SUFFIX;

		if ( $this->table_is_global && is_multisite() ) {
			return get_site_option( $option_name );
		}

		return get_option( $option_name );
	}

	/**
	 * Update the table version stored in DB.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function update_db_version() {
		$option_name = $this->table . self::TABLE_VERSION_OPTION_SUFFIX;

		if ( $this->table_is_global && is_multisite() ) {
			update_site_option( $option_name, $this->get_table_version() );
		} else {
			update_option( $option_name, $this->get_table_version() );
		}
	}

	/**
	 * Delete the table version stored in DB.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function delete_db_version() {
		$option_name = $this->table . self::TABLE_VERSION_OPTION_SUFFIX;

		if ( $this->table_is_global && is_multisite() ) {
			delete_site_option( $option_name );
		} else {
			delete_option( $option_name );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the table name.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Tell if the table is the same for each site of a Multisite.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function is_table_global() {
		return $this->table_is_global;
	}

	/**
	 * Get the primary column name.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_primary_key() {
		return $this->primary_key;
	}

	/**
	 * Get the formats related to the given columns.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $columns An array of column names (as keys).
	 * @return array
	 */
	public function get_column_formats( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array_flip( (array) $columns );
		}

		// White list columns.
		return array_intersect_key( $this->get_columns(), $columns );
	}

	/**
	 * Get the placeholder corresponding to the given key.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $key The key.
	 * @return string
	 */
	public function get_placeholder( $key ) {
		$columns = $this->get_columns();
		return isset( $columns[ $key ] ) ? $columns[ $key ] : '%s';
	}

	/**
	 * Tell if the column value must be (un)serialized.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $key The key.
	 * @return bool
	 */
	public function is_column_serialized( $key ) {
		$columns = $this->get_column_defaults();
		return isset( $columns[ $key ] ) && is_array( $columns[ $key ] );
	}

	/**
	 * Cast a value.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  mixed  $value The value to cast.
	 * @param  string $key   The corresponding key.
	 * @return mixed
	 */
	public function cast( $value, $key ) {
		if ( null === $value || is_bool( $value ) ) {
			return $value;
		}

		$placeholder = $this->get_placeholder( $key );

		if ( '%d' === $placeholder ) {
			return (int) $value;
		}

		if ( '%f' === $placeholder ) {
			return (float) $value;
		}

		if ( $value && $this->is_column_serialized( $key ) ) {
			return maybe_unserialize( $value );
		}

		return $value;
	}

	/**
	 * Cast a column.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $values The values to cast.
	 * @param  string $column The corresponding column name.
	 * @return array
	 */
	public function cast_col( $values, $column ) {
		if ( ! $values ) {
			return $values;
		}

		foreach ( $values as $i => $value ) {
			$values[ $i ] = $this->cast( $value, $column );
		}

		return $values;
	}

	/**
	 * Cast a row.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array|object $row_fields A row from the DB.
	 * @return array|object
	 */
	public function cast_row( $row_fields ) {
		if ( ! $row_fields ) {
			return $row_fields;
		}

		if ( is_array( $row_fields ) ) {
			foreach ( $row_fields as $field => $value ) {
				$row_fields[ $field ] = $this->cast( $value, $field );
			}
		} elseif ( is_object( $row_fields ) ) {
			foreach ( $row_fields as $field => $value ) {
				$row_fields->$field = $this->cast( $value, $field );
			}
		}

		return $row_fields;
	}

	/**
	 * Serialize columns that need to be.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $data An array of values.
	 * @return array
	 */
	public function serialize_columns( $data ) {
		if ( ! isset( $this->to_serialize ) ) {
			$this->to_serialize = array_filter( $this->get_column_defaults(), 'is_array' );
		}

		if ( ! $this->to_serialize ) {
			return $data;
		}

		$serialized_data = array_intersect_key( $data, $this->to_serialize );
		$serialized_data = array_map( 'maybe_serialize', $serialized_data );

		return array_merge( $data, $serialized_data );
	}
}
