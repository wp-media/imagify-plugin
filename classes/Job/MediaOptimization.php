<?php
namespace Imagify\Job;

use Imagify\Media\MediaInterface;
use Imagify\Optimization\File;
use Imagify\Optimization\Process\ProcessInterface;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Job class for media optimization.
 *
 * @since  1.9
 * @author Grégory Viguier
 */
class MediaOptimization extends \Imagify_Abstract_Background_Process {

	/**
	 * Background process: the action to perform.
	 *
	 * @var    string
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $action = 'optimize_media';

	/**
	 * The optimization process instance.
	 *
	 * @var    ProcessInterface
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $optimization_process;

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;

	/**
	 * Handle job logic.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
	 * Trigger hooks before the optimization job.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
	 *
	 * @param  array $item See $this->task().
	 * @return array       The item.
	 */
	private function task_before( $item ) {
		if ( ! empty( $item['error'] ) && is_wp_error( $item['error'] ) ) {
			$wp_error = $item['error'];
		} else {
			$wp_error = new \WP_Error();
		}

		/**
		 * Fires before optimizing a media.
		 * Any number of files can be optimized, not necessarily all of the media files.
		 * If you want to return a WP_Error, use the existing $wp_error object.
		 *
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array|\WP_Error  $data     New data to pass along the item. A \WP_Error object to stop the process.
		 * @param \WP_Error        $wp_error Add errors to this object and return it to stop the process.
		 * @param ProcessInterface $process  The optimization process.
		 * @param array            $item     The item being processed. See $this->task().
		 */
		$data = apply_filters( 'imagify_before_optimize', [], $wp_error, $this->optimization_process, $item );

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
		 * @since  1.9
		 * @author Grégory Viguier
		 *
		 * @param array|\WP_Error  $data     New data to pass along the item. A \WP_Error object to stop the process.
		 * @param \WP_Error        $wp_error Add errors to this object and return it to stop the process.
		 * @param ProcessInterface $process  The optimization process.
		 * @param array            $item     The item being processed. See $this->task().
		 */
		$data = apply_filters( "imagify_before_{$hook_suffix}", [], $wp_error, $this->optimization_process, $item );

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
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
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
				$item['task']  = 'after';
				$item['error'] = $data;
				return $item;
			}

			if ( ! $this->optimization_process->get_data()->is_optimized() ) {
				// Don't go thurther if the full size has not the "success" status.
				$item['task'] = 'after';
				return $item;
			}
		}

		if ( ! $item['sizes'] ) {
			// No more files to optimize.
			$item['task'] = 'after';
		}

		// Optimize the next file.
		return $item;
	}

	/**
	 * Trigger hooks after the optimization job.
	 *
	 * @since  1.9
	 * @access private
	 * @author Grégory Viguier
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
		 * @since  1.9
		 * @author Grégory Viguier
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
		 * @since  1.9
		 * @author Grégory Viguier
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
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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

		if ( ! $item['task'] || ! method_exists( $this, 'task_' . $item['task'] ) ) {
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
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
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
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  mixed $optimization_level The optimization level.
	 * @return int
	 */
	protected function sanitize_optimization_level( $optimization_level ) {
		if ( ! is_numeric( $optimization_level ) ) {
			return get_imagify_option( 'optimization_level' );
		}

		return \Imagify_Options::get_instance()->sanitize_and_validate( 'optimization_level', $optimization_level );
	}
}
