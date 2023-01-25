<?php

use Imagify\Traits\InstanceGetterTrait;

/**
 * Class handling background processes.
 *
 * @since  1.8.1
 */
abstract class Imagify_Abstract_Background_Process extends Imagify_WP_Background_Process {
	use InstanceGetterTrait;

	/**
	 * Prefix used to build the global process identifier.
	 *
	 * @var string
	 * @since 1.8.1
	 */
	protected $prefix = 'imagify';

	/**
	 * Set to true to automatically displatch at the end of the page.
	 *
	 * @var bool
	 * @since 1.9
	 * @see $this->save()
	 * @see $this->maybe_save_and_dispatch()
	 */
	protected $auto_dispatch = false;

	/**
	 * Init: launch a hook that will clear the scheduled events and empty the queue when the plugin is disabled.
	 * This is only a precaution in case something went wrong.
	 *
	 * @since 1.8.1
	 */
	public function init() {
		$this->query_url = admin_url( 'admin-ajax.php' );

		/**
		 * Filter the URL to use for background processes.
		 *
		 * @since 1.9.5
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
	 * @since 1.8.1
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
	 * @since 1.9
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
	 * @since 1.9
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
	 * @since 1.8.1
	 *
	 * @return string
	 */
	public function get_event_name() {
		return $this->cron_hook_identifier;
	}

	/**
	 * Get the deactivation hook name.
	 *
	 * @since 1.8.1
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
