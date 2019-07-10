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
	const VERSION = '1.1';

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
	 * Set to true to automatically displatch at the end of the page.
	 *
	 * @var    bool
	 * @since  1.9
	 * @access protected
	 * @see    $this->save()
	 * @see    $this->maybe_save_and_dispatch()
	 * @author Grégory Viguier
	 */
	protected $auto_dispatch = false;

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
		if ( ! isset( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Init: launch a hook that will clear the scheduled events and empty the queue when the plugin is disabled.
	 * This is only a precaution in case something went wrong.
	 *
	 * @since  1.8.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		$this->query_url = admin_url( 'admin-ajax.php' );

		/**
		 * Filter the URL to use for background processes.
		 *
		 * @since  1.9.5
		 * @author Grégory Viguier
		 *
		 * @param string $query_url An URL.
		 * @param object $this      This class instance.
		 */
		$this->query_url = apply_filters( 'imagify_background_process_url', $this->query_url, $this );

		if ( ! $this->query_url || ! is_string( $this->query_url ) || ! preg_match( '@^https?://@', $this->query_url ) ) {
			$this->query_url = admin_url( 'admin-ajax.php' );
		}

		// Deactivation hook.
		if ( did_action( static::get_deactivation_hook_name() ) ) {
			$this->cancel_process();
		} else {
			add_action( static::get_deactivation_hook_name(), [ $this, 'cancel_process' ] );
		}

		// Automatically save and dispatch at the end of the page if the queue is not empty.
		add_action( 'shutdown', [ $this, 'maybe_save_and_dispatch' ], 666 ); // Evil magic number.
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OVERRIDES =============================================================================== */
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

	/**
	 * Save the queen. No, I meant the queue.
	 * Also empty the queue to avoid to create several batches with the same items.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return $this
	 */
	public function save() {
		if ( empty( $this->data ) ) {
			return $this;
		}

		parent::save();

		$this->auto_dispatch = true;
		$this->data          = [];

		return $this;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Save and dispatch if the queue is not empty.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 */
	public function maybe_save_and_dispatch() {
		$this->save();

		if ( $this->auto_dispatch ) {
			$this->dispatch();
		}
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
	public function get_event_name() {
		return $this->cron_hook_identifier;
	}

	/**
	 * Get the deactivation hook name.
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
