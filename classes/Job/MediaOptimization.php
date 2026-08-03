<?php
namespace Imagify\Job;

use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Traits\InstanceGetterTrait;
use WP_Error;

/**
 * Job class for media optimization.
 *
 * @since 1.9
 */
final class MediaOptimization extends \Imagify_Abstract_Background_Process {
	use InstanceGetterTrait;

	/**
	 * Batch-key segment used to mark a batch as "urgent" (high priority).
	 * Shared by generate_key() (write path) and get_batches() (read path) so both
	 * always agree on the exact same string shape.
	 *
	 * @var   string
	 * @since 2.3.2
	 */
	private const URGENT_KEY_SEGMENT = 'batch_urgent';

	/**
	 * Background process: the action to perform.
	 *
	 * @var   string
	 * @since 1.9
	 */
	protected $action = 'optimize_media';

	/**
	 * The optimization process instance.
	 *
	 * @var   ?ProcessInterface
	 * @since 1.9
	 */
	protected $optimization_process;

	/**
	 * Handle job logic.
	 *
	 * @since 1.9
	 *
	 * @param array $item {
	 *     The data to use for this job.
	 *
	 *     @type string $task               The task to perform. Optional: set it only if you know what you’re doing.
	 *     @type int    $id                 The media ID.
	 *     @type array  $sizes              An array of media sizes (strings). Use "full" for the size of the main file.
	 *     @type array  $sizes_done         Used internally to store the media sizes that have been processed.
	 *     @type int    $optimization_level The optimization level. Null for the level set in the settings.
	 *     @type string $process_class      The name of the process class. The class must implement ProcessInterface.
	 *     @type array  $data               {
	 *         Can be used to pass any data. Keep it short, don’t forget it will be stored in the database.
	 *         It should contain the following though:
	 *
	 *         @type string $hook_suffix   Suffix used to trigger hooks before and after optimization. Should be always provided.
	 *         @type bool   $delete_backup True to delete the backup file after the optimization process. This is used when a temporary backup of the original file has been created, but backup option is disabled. Default is false.
	 *         @type bool   $bulk          True when this optimization was triggered by a bulk run (see Bulk::force_optimize()). Forwarded verbatim to the 'imagify_before_*'/'imagify_after_*' hook callbacks below. Default is false.
	 *     }
	 * }
	 * @return array|bool The modified item to put back in the queue. False to remove the item from the queue.
	 */
	protected function task( $item ) {
		$item = $this->validate_item( $item );

		if ( ! $item ) {
			// Not valid.
			return false;
		}

		// Launch the task.
		$method = 'task_' . $item['task'];
		$item   = $this->$method( $item );

		if ( $item['task'] ) {
			// Next task.
			return $item;
		}

		// End of the queue.
		$this->optimization_process->unlock();
		return false;
	}

	/**
	 * Generate the batch key.
	 * Overrides the vendored method to give priority ("urgent") batches a distinct
	 * key shape, so get_batches() can order them first.
	 *
	 * @since 2.3.2
	 *
	 * @param int    $length Optional, length of the string to return. Default 64.
	 * @param string $key    Optional, string with a prefix key. Default 'batch'.
	 * @return string
	 */
	protected function generate_key( $length = 64, $key = 'batch' ) {
		if ( 'batch' === $key && $this->has_priority_item_queued() ) {
			return parent::generate_key( $length, self::URGENT_KEY_SEGMENT );
		}

		return parent::generate_key( $length, $key );
	}

	/**
	 * Tell if any of the not-yet-persisted queued items carries a truthy 'priority' key.
	 *
	 * A single truthy item marks the whole batch urgent, which only makes sense because
	 * a batch is always a singleton at this point: generate_key() is called from
	 * push_to_queue(), immediately followed by save(), one item at a time. If several
	 * items were ever pushed to $this->data before save() is called, they would all end
	 * up sharing the same "urgent" key as soon as one of them requests priority.
	 *
	 * @since 2.3.2
	 *
	 * @return bool
	 */
	private function has_priority_item_queued(): bool {
		if ( empty( $this->data ) || ! is_array( $this->data ) ) {
			return false;
		}

		foreach ( $this->data as $item ) {
			if ( is_array( $item ) && ! empty( $item['priority'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get batches.
	 * Overrides the vendored method to return "urgent" (priority) batches before
	 * normal ones, each tier still ordered FIFO (by option/meta ID) internally.
	 *
	 * @since 2.3.2
	 *
	 * @param int $limit Number of batches to return, defaults to all.
	 * @return array of stdClass
	 */
	public function get_batches( $limit = 0 ) {
		global $wpdb;

		if ( empty( $limit ) || ! is_int( $limit ) ) {
			$limit = 0;
		}

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key        = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
		$urgent_key = $wpdb->esc_like( $this->identifier . '_' . self::URGENT_KEY_SEGMENT . '_' ) . '%';

		$sql = '
			SELECT *
			FROM ' . $table . '
			WHERE ' . $column . ' LIKE %s
			ORDER BY ( ' . $column . ' LIKE %s ) DESC, ' . $key_column . ' ASC
			';

		$args = [ $key, $urgent_key ];

		if ( ! empty( $limit ) ) {
			$sql   .= ' LIMIT %d';
			$args[] = $limit;
		}

		$items = $wpdb->get_results(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$args
			)
		);

		$batches = [];

		if ( ! empty( $items ) ) {
			$allowed_classes = $this->allowed_batch_data_classes;

			$batches = array_map(
				static function ( $item ) use ( $column, $value_column, $allowed_classes ) {
					$batch       = new \stdClass();
					$batch->key  = $item->{$column};
					$batch->data = static::maybe_unserialize( $item->{$value_column}, $allowed_classes );

					return $batch;
				},
				$items
			);
		}

		return $batches;
	}

	/**
	 * Trigger hooks before the optimization job.
	 *
	 * @since 1.9
	 *
	 * @param  array $item See $this->task().
	 * @return array       The item.
	 */
	private function task_before( $item ) {
		if ( ! empty( $item['error'] ) && is_wp_error( $item['error'] ) ) {
			$wp_error = $item['error'];
		} else {
			$wp_error = new WP_Error();
		}

		/**
		 * Fires before optimizing a media.
		 * Any number of files can be optimized, not necessarily all of the media files.
		 * If you want to return a WP_Error, use the existing $wp_error object.
		 *
		 * @since 1.9
		 *
		 * @param array|WP_Error  $data     New data to pass along the item. A WP_Error object to stop the process.
		 * @param WP_Error        $wp_error Add errors to this object and return it to stop the process.
		 * @param ProcessInterface $process  The optimization process.
		 * @param array            $item     The item being processed. See $this->task().
		 */
		$data = apply_filters( 'imagify_before_optimize', [], $wp_error, $this->optimization_process, $item ); // @phpstan-ignore-line

		if ( is_wp_error( $data ) ) {
			$wp_error = $data;
		} elseif ( $data && is_array( $data ) ) {
			$item['data'] = array_merge( $data, $item['data'] );
		}

		if ( $wp_error->get_error_codes() ) {
			// Don't optimize if there is an error.
			$item['task']  = 'after';
			$item['error'] = $wp_error;
			return $item;
		}

		if ( empty( $item['data']['hook_suffix'] ) ) {
			// Next task.
			$item['task'] = 'optimize';
			return $item;
		}

		$hook_suffix = $item['data']['hook_suffix'];

		/**
		 * Fires before optimizing a media.
		 * Any number of files can be optimized, not necessarily all of the media files.
		 * If you want to return a WP_Error, use the existing $wp_error object.
		 *
		 * @since 1.9
		 *
		 * @param array|WP_Error  $data     New data to pass along the item. A WP_Error object to stop the process.
		 * @param WP_Error        $wp_error Add errors to this object and return it to stop the process.
		 * @param ProcessInterface $process  The optimization process.
		 * @param array            $item     The item being processed. See $this->task().
		 */
		$data = apply_filters( "imagify_before_{$hook_suffix}", [], $wp_error, $this->optimization_process, $item ); // @phpstan-ignore-line

		if ( is_wp_error( $data ) ) {
			$wp_error = $data;
		} elseif ( $data && is_array( $data ) ) {
			$item['data'] = array_merge( $data, $item['data'] );
		}

		if ( $wp_error->get_error_codes() ) {
			// Don't optimize if there is an error.
			$item['task']  = 'after';
			$item['error'] = $wp_error;
			return $item;
		}

		// Next task.
		$item['task'] = 'optimize';

		return $item;
	}

	/**
	 * Start the optimization job.
	 *
	 * @since 1.9
	 *
	 * @param  array $item See $this->task().
	 * @return array       The item.
	 */
	private function task_optimize( $item ) {
		// Determine which size we're going to optimize. The 'full' size must be optimized before any other.
		if ( in_array( 'full', $item['sizes'], true ) ) {
			$current_size  = 'full';
			$item['sizes'] = array_diff( $item['sizes'], [ 'full' ] );
		} else {
			$current_size = array_shift( $item['sizes'] );
		}

		$item['sizes_done'][] = $current_size;

		// Optimize the file.
		$data = $this->optimization_process->optimize_size( $current_size, $item['optimization_level'] );

		if ( 'full' === $current_size ) {
			if ( is_wp_error( $data ) ) {
				// Don't go further if there is an error.
				$item['sizes'] = [];
				$item['error'] = $data;

			} elseif ( 'already_optimized' === $data['status'] ) {
				// Status is "already_optimized", try to create next-gen versions only.
				$item['sizes'] = array_filter( $item['sizes'], [ $this->optimization_process, 'is_size_next_gen' ] );

			} elseif ( 'success' !== $data['status'] ) {
				// Don't go further if the full size has not the "success" status.
				$item['sizes'] = [];
			}
		}

		if ( ! $item['sizes'] ) {
			// No more files to optimize.
			$item['task'] = 'after';
		}

		// Optimize the next file or go to the next task.
		return $item;
	}

	/**
	 * Trigger hooks after the optimization job.
	 *
	 * @since 1.9
	 *
	 * @param  array $item See $this->task().
	 * @return array       The item.
	 */
	private function task_after( $item ) {
		if ( ! empty( $item['data']['delete_backup'] ) ) {
			$this->optimization_process->delete_backup();
		}

		/**
		 * Fires after optimizing a media.
		 * Any number of files can be optimized, not necessarily all of the media files.
		 *
		 * @since 1.9
		 *
		 * @param ProcessInterface $process The optimization process.
		 * @param array            $item    The item being processed. See $this->task().
		 */
		do_action( 'imagify_after_optimize', $this->optimization_process, $item );

		if ( empty( $item['data']['hook_suffix'] ) ) {
			$item['task'] = false;
			return $item;
		}

		$hook_suffix = $item['data']['hook_suffix'];

		/**
		 * Fires after optimizing a media.
		 * Any number of files can be optimized, not necessarily all of the media files.
		 *
		 * @since 1.9
		 *
		 * @param ProcessInterface $process The optimization process.
		 * @param array            $item    The item being processed. See $this->task().
		 */
		do_action( "imagify_after_{$hook_suffix}", $this->optimization_process, $item );

		$item['task'] = false;
		return $item;
	}

	/**
	 * Validate an item.
	 * On success, the property $this->optimization_process is set.
	 *
	 * @since 1.9
	 *
	 * @param array $item See $this->task().
	 * @return array|bool The item. False if invalid.
	 */
	protected function validate_item( $item ) {
		$this->optimization_process = null;

		$default = [
			'task'               => '',
			'id'                 => 0,
			'sizes'              => [],
			'sizes_done'         => [],
			'optimization_level' => null,
			'process_class'      => '',
			'data'               => [],
		];

		$item = imagify_merge_intersect( $item, $default );

		// Validate some types first.
		if ( ! is_array( $item['sizes'] ) ) {
			return false;
		}

		if ( isset( $item['error'] ) && ! is_wp_error( $item['error'] ) ) {
			unset( $item['error'] );
		}

		if ( isset( $item['data']['hook_suffix'] ) && ! is_string( $item['data']['hook_suffix'] ) ) {
			unset( $item['data']['hook_suffix'] );
		}

		$item['id']                 = (int) $item['id'];
		$item['optimization_level'] = $this->sanitize_optimization_level( $item['optimization_level'] );

		if ( ! $item['id'] || ! $item['process_class'] ) {
			return false;
		}

		// Process.
		$item['process_class'] = '\\' . ltrim( $item['process_class'], '\\' );

		if ( ! class_exists( $item['process_class'] ) ) {
			return false;
		}

		$process = $this->get_process( $item );

		if ( ! $process ) {
			return false;
		}

		$this->optimization_process = $process;

		// Validate the current task.
		if ( empty( $item['task'] ) ) {
			$item['task'] = 'before';
		}

		if ( ! method_exists( $this, 'task_' . $item['task'] ) ) {
			return false;
		}

		if ( ! $item['sizes'] && 'after' !== $item['task'] ) {
			// Allow to have no sizes, but only after the optimize task is complete.
			return false;
		}

		if ( ! isset( $item['sizes_done'] ) || ! is_array( $item['sizes_done'] ) ) {
			$item['sizes_done'] = [];
		}

		return $item;
	}

	/**
	 * Get the process instance.
	 *
	 * @since 1.9
	 *
	 * @param array $item             See $this->task().
	 * @return ProcessInterface|bool The instance object on success. False on failure.
	 */
	protected function get_process( $item ) {
		$process_class = $item['process_class'];
		$process       = new $process_class( $item['id'] );

		if ( ! $process instanceof ProcessInterface || ! $process->is_valid() ) {
			return false;
		}

		return $process;
	}

	/**
	 * Sanitize and validate an optimization level.
	 * If not provided (false, null), fallback to the level set in the plugin's settings.
	 *
	 * @since 1.9
	 *
	 * @param  mixed $optimization_level The optimization level.
	 * @return int
	 */
	protected function sanitize_optimization_level( $optimization_level ) {
		if ( ! is_numeric( $optimization_level ) ) {
			if ( get_imagify_option( 'lossless' ) ) {
				return 0;
			}

			return get_imagify_option( 'optimization_level' );
		}

		return \Imagify_Options::get_instance()->sanitize_and_validate( 'optimization_level', $optimization_level );
	}
}
