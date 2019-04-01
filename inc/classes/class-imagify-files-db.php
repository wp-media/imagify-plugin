<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * DB class that handles files in "custom folders".
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Files_DB extends Imagify_Abstract_DB {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

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
	protected $table = 'imagify_files';

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
	protected $primary_key = 'file_id';

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
			'file_id'            => '%d',
			'folder_id'          => '%d',
			'file_date'          => '%s',
			'path'               => '%s',
			'hash'               => '%s',
			'mime_type'          => '%s',
			'modified'           => '%d',
			'width'              => '%d',
			'height'             => '%d',
			'original_size'      => '%d',
			'optimized_size'     => '%d',
			'percent'            => '%d',
			'optimization_level' => '%d',
			'status'             => '%s',
			'error'              => '%s',
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
			'file_id'            => 0,
			'folder_id'          => 0,
			'file_date'          => '0000-00-00 00:00:00',
			'path'               => '',
			'hash'               => '',
			'mime_type'          => '',
			'modified'           => 0,
			'width'              => 0,
			'height'             => 0,
			'original_size'      => 0,
			'optimized_size'     => null,
			'percent'            => null,
			'optimization_level' => null,
			'status'             => null,
			'error'              => null,
		);
	}

	/**
	 * Get the query to create the table fields.
	 *
	 * For with and height: `smallint(2) unsigned` means 65,535px max.
	 *
	 * @since  1.7
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	protected function get_table_schema() {
		return "
			file_id bigint(20) unsigned NOT NULL auto_increment,
			folder_id bigint(20) unsigned NOT NULL default 0,
			file_date datetime NOT NULL default '0000-00-00 00:00:00',
			path varchar(191) NOT NULL default '',
			hash varchar(32) NOT NULL default '',
			mime_type varchar(100) NOT NULL default '',
			modified tinyint(1) unsigned NOT NULL default 0,
			width smallint(2) unsigned NOT NULL default 0,
			height smallint(2) unsigned NOT NULL default 0,
			original_size int(4) unsigned NOT NULL default 0,
			optimized_size int(4) unsigned default NULL,
			percent smallint(2) unsigned default NULL,
			optimization_level tinyint(1) unsigned default NULL,
			status varchar(20) default NULL,
			error varchar(255) default NULL,
			PRIMARY KEY (file_id),
			UNIQUE KEY path (path),
			KEY folder_id (folder_id),
			KEY optimization_level (optimization_level),
			KEY status (status),
			KEY modified (modified)";
	}
}
