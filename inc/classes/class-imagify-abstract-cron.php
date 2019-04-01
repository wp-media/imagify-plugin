<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Basis class that handles events.
 *
 * @since  1.7
 * @author Grégory Viguier
 */
abstract class Imagify_Abstract_Cron {

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
	protected $event_name = '';

	/**
	 * Cron recurrence.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_recurrence = '';

	/**
	 * Cron time.
	 *
	 * @var   string
	 * @since 1.7
	 * @access protected
	 */
	protected $event_time = '';

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
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Launch hooks.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		add_action( 'init',                       array( $this, 'schedule_event' ) );
		add_action( $this->get_event_name(),      array( $this, 'do_event' ) );
		add_filter( 'cron_schedules',             array( $this, 'maybe_add_recurrence' ) );

		if ( did_action( static::get_deactivation_hook_name() ) ) {
			$this->unschedule_event();
		} else {
			add_action( static::get_deactivation_hook_name(), array( $this, 'unschedule_event' ) );
		}
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
		if ( ! wp_next_scheduled( $this->get_event_name() ) ) {
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
	abstract public function do_event();

	/**
	 * Unschedule the event at plugin or submodule deactivation.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 */
	public function unschedule_event() {
		wp_clear_scheduled_hook( $this->get_event_name() );
	}

	/**
	 * Add the event recurrence schedule.
	 *
	 * @since  1.7
	 * @access public
	 * @see    wp_get_schedules()
	 * @author Grégory Viguier
	 *
	 * @param array $schedules An array of non-default cron schedules. Default empty.
	 *
	 * @return array
	 */
	public function maybe_add_recurrence( $schedules ) {
		$default_schedules = array(
			'hourly'     => 1,
			'twicedaily' => 1,
			'daily'      => 1,
		);

		$event_recurrence = $this->get_event_recurrence();

		if ( ! empty( $schedules[ $event_recurrence ] ) || ! empty( $default_schedules[ $event_recurrence ] ) ) {
			return $schedules;
		}

		$recurrences = array(
			'weekly' => array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'imagify' ),
			),
		);

		if ( method_exists( $this, 'get_event_recurrence_attributes' ) ) {
			$recurrences[ $event_recurrence ] = $this->get_event_recurrence_attributes();
		}

		if ( ! empty( $recurrences[ $event_recurrence ] ) ) {
			$schedules[ $event_recurrence ] = $recurrences[ $event_recurrence ];
		}

		return $schedules;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the cron name.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_event_name() {
		return $this->event_name;
	}

	/**
	 * Get the cron recurrence.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_event_recurrence() {
		/**
		 * Filter the recurrence of the event.
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param string $event_recurrence The recurrence.
		 * @param string $event_name       Name of the event this recurrence is used for.
		 */
		return apply_filters( 'imagify_event_recurrence', $this->event_recurrence, $this->get_event_name() );
	}

	/**
	 * Get the time to schedule the event.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_event_time() {
		/**
		 * Filter the time at which the event is triggered (WordPress time).
		 *
		 * @since  1.7
		 * @author Grégory Viguier
		 *
		 * @param string $event_time A 24H formated time: `hour:minute`.
		 * @param string $event_name Name of the event this time is used for.
		 */
		return apply_filters( 'imagify_event_time', $this->event_time, $this->get_event_name() );
	}

	/**
	 * Get the timestamp to schedule the event.
	 *
	 * @since  1.7
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return int Timestamp.
	 */
	public function get_event_timestamp() {
		return self::get_next_timestamp( $this->get_event_time() );
	}

	/**
	 * Get the timestamp of the next (event) date for the given hour.
	 *
	 * @since  1.7
	 * @author Grégory Viguier
	 * @source secupress_get_next_cron_timestamp()
	 *
	 * @param string $event_time Time when the event callback should be triggered (WordPress time), formated like `hh:mn` (hour:minute).
	 *
	 * @return int Timestamp.
	 */
	public static function get_next_timestamp( $event_time = '00:00' ) {
		$current_time_int = (int) date( 'Gis' );
		$event_time_int   = (int) str_replace( ':', '', $event_time . '00' );
		$event_time       = explode( ':', $event_time );
		$event_hour       = (int) $event_time[0];
		$event_minute     = (int) $event_time[1];
		$offset           = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

		if ( $event_time_int <= $current_time_int ) {
			// The event time is passed, we need to schedule the event tomorrow.
			return mktime( $event_hour, $event_minute, 0, (int) date( 'n' ), (int) date( 'j' ) + 1 ) - $offset;
		}

		// We haven't passed the event time yet, schedule the event today.
		return mktime( $event_hour, $event_minute, 0 ) - $offset;
	}

	/**
	 * Get the deactivation hook name.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_deactivation_hook_name() {
		static $deactivation_hook;

		if ( ! isset( $deactivation_hook ) ) {
			$deactivation_hook = 'deactivate_' . plugin_basename( IMAGIFY_FILE );
		}

		return $deactivation_hook;
	}
}
