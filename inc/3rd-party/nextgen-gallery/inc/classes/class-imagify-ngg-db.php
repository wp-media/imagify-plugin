<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify NextGen Gallery DB class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class Imagify_NGG_DB extends Imagify_Abstract_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * The single instance of the class.
	 *
	 * @since  1.5
	 * @access protected
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * Get things started.
	 *
	 * @since  1.5
	 * @access protected
	 * @author Jonathan Buttigieg
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
	 * Whitelist of columns.
	 *
	 * @since  1.5
	 * @access public
	 * @author Jonathan Buttigieg
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'data_id'            => '%d',
			'pid'                => '%d',
			'optimization_level' => '%s',
			'status'             => '%s',
			'data'               => '%s',
		);
	}

	/**
	 * Default column values.
	 *
	 * @since  1.5
	 * @access public
	 * @author Jonathan Buttigieg
	 *
	 * @return array
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
	 * @since  1.5
	 * @access public
	 * @author Jonathan Buttigieg
	 */
	public function create_table() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();

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
}
