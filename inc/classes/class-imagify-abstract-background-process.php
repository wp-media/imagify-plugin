<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class handling background processes.
 *
 * @since  1.8.1
 * @author Grégory Viguier
 */
abstract class Imagify_Abstract_Background_Process extends WP_Background_Process {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8.1
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Prefix used to build the global process identifier.
	 *
	 * @var    string
	 * @since  1.8.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $prefix = 'imagify';

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.8.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;


	/**
	 * Get the main Instance.
	 *
	 * @since  1.8.1
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
	 * Init: launch a hook that will clear the scheduled events and empty the queue when the plugin is disabled.
	 * This is only a precaution in case something went wrong.
	 *
	 * @since  1.8.1
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( did_action( self::get_deactivation_hook_name() ) ) {
			$this->cancel_process();
		} else {
			add_action( self::get_deactivation_hook_name(), array( $this, 'cancel_process' ) );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** COMPAT ================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Cancel Process.
	 * Stop processing queue items, clear cronjob and delete batch.
	 * This is a copy of the parent's method, in case an older version of WP_Background_Process is loaded instead of this one (an old version without this method).
	 *
	 * @since  1.8.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function cancel_process() {
		if ( method_exists( $this, 'cancel_process' ) ) {
			parent::cancel_process();
			return;
		}

		if ( ! $this->is_queue_empty() ) {
			$batch = $this->get_batch();

			$this->delete( $batch->key );

			wp_clear_scheduled_hook( $this->get_event_name() );
		}
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the cron name.
	 *
	 * @since  1.8.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public function get_event_name() {
		return $this->cron_hook_identifier;
	}

	/**
	 * Get the cron name.
	 *
	 * @since  1.8.1
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
