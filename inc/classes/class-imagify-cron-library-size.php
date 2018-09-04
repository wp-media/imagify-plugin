<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the cron that calculate and cache the library size.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Cron_Library_Size extends Imagify_Abstract_Cron {

	/**
	 * Class version.
	 *
	 * @var   string
	 * @since 1.7
	 */
	const VERSION = '1.0';

	/**
	 * Cron name.
	 *
	 * @var    string
	 * @since  1.7
	 * @access protected
	 */
	protected $event_name = 'imagify_update_library_size_calculations_event';

	/**
	 * Cron recurrence.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_recurrence = 'weekly';

	/**
	 * Cron time.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_time = '04:00';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.7
	 * @access protected
	 */
	protected static $_instance;

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


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * The event action.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function do_event() {
		imagify_do_async_job( array(
			'action'      => 'imagify_update_estimate_sizes',
			'_ajax_nonce' => wp_create_nonce( 'update_estimate_sizes' ),
		) );
	}
}
