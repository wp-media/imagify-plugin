<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery DB class.
 *
 * @since 1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG_DB extends Imagify_Abstract_DB {

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
	 * Get things started.
	 *
	 * @access  public
	 * @since   1.5
	 * @author  Jonathan Buttigieg
	 */
	protected function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'ngg_imagify_data';
		$this->primary_key = 'pid'; // Instead of data_id.
		$this->version     = '1.0';

		// Database declaration.
		$wpdb->ngg_imagify_data = $this->table_name;

		// Add table to the index of WordPress tables.
		$wpdb->tables[] = 'ngg_imagify_data';
	}

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
	 * @author  Jonathan Buttigieg
	 *
	 * @return  array
	 */
	public function get_columns() {
		return array(
			'data_id'            => '%d',
			'pid'                => '%d',
			'optimization_level' => '%s',
			'status'             => '%s',
			'data'         		 => '%s',
		);
	}

	/**
	 * Default column values.
	 *
	 * @access  public
	 * @since   1.5
	 * @author  Jonathan Buttigieg
	 *
	 * @return  array
	 */
	public function get_column_defaults() {
		return array(
			'pid'                => 0,
			'optimization_level' => '',
			'status'             => '',
			'data'               => '',
		);
	}

	/**
	 * Create the table.
	 *
	 * @access  public
	 * @since   1.5
	 * @author Jonathan Buttigieg
	 */
	public function create_table() {
		global $wpdb;

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			data_id int(11) NOT NULL AUTO_INCREMENT,
			pid int(11) NOT NULL,
			optimization_level varchar(1) NOT NULL,
			status varchar(30) NOT NULL,
			data longtext NOT NULL,
			PRIMARY KEY (data_id)
		) $charset_collate;";

		maybe_create_table( $this->table_name, $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 * Well, actually it ensures nothing since it's not a full singleton pattern.
	 *
	 * @access  public
	 * @since   1.5
	 * @author  Jonathan Buttigieg
	 *
	 * @return object Main instance.
	 */
	public static function instance() {
		$class_name = get_class( $this );
		_deprecated_function( $class_name . '::' . __FUNCTION__ . '()', '1.6.5', 'imagify_ngg_db()' );
		return self::get_instance();
	}
}

/**
 * Returns the main instance of the Imagify_NGG_DB class.
 *
 * @since 1.6.5
 * @author Jonathan Buttigieg
 *
 * @return object The Imagify_NGG_DB instance.
 */
function imagify_ngg_db() {
	return Imagify_NGG_DB::get_instance();
}
