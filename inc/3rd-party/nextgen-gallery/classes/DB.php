<?php
namespace Imagify\ThirdParty\NGG;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Imagify NextGen Gallery DB class.
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 */
class DB extends \Imagify_Abstract_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.1';

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
	protected $table = 'ngg_imagify_data';

	/**
	 * The version of our database table.
	 *
	 * @var    int
	 * @since  1.5
	 * @since  1.7 Not public anymore, now an integer.
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
	protected $table_is_global = false;

	/**
	 * The name of the primary column.
	 *
	 * @var    string
	 * @since  1.5
	 * @since  1.7 Not public anymore.
	 * @access protected
	 */
	protected $primary_key = 'pid';

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
			'data_id'            => 0,
			'pid'                => 0,
			'optimization_level' => '',
			'status'             => '',
			'data'               => array(),
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
			data_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			pid int(11) unsigned NOT NULL default 0,
			optimization_level varchar(1) NOT NULL default '',
			status varchar(30) NOT NULL default '',
			data longtext NOT NULL default '',
			PRIMARY KEY  (data_id),
			KEY pid (pid)";
	}
}
