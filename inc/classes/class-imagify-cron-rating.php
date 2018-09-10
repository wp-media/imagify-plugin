<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the plugin rating cron.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
class Imagify_Cron_Rating extends Imagify_Abstract_Cron {

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
	protected $event_name = 'imagify_rating_event';

	/**
	 * Cron recurrence.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_recurrence = 'daily';

	/**
	 * Cron time.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_time = '15:00';

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
	 * Initiate the event.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function schedule_event() {
		if ( ! wp_next_scheduled( $this->get_event_name() ) && ! get_site_transient( 'do_imagify_rating_cron' ) ) {
			wp_schedule_event( $this->get_event_timestamp(), $this->get_event_recurrence(), $this->get_event_name() );
		}
	}

	/**
	 * The event action.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function do_event() {
		// Stop the process if the plugin isn't installed for 3 days.
		if ( get_site_transient( 'imagify_seen_rating_notice' ) ) {
			return;
		}

		$user = get_imagify_user();

		if ( ! is_wp_error( $user ) && (int) $user->image_count > 100 ) {
			set_site_transient( 'imagify_user_images_count', $user->image_count );
		}
	}
}
