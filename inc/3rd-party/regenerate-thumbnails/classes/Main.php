<?php
namespace Imagify\ThirdParty\RegenerateThumbnails;

use Imagify_Requirements;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles compatibility with Regenerate Thumbnails plugin.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 */
class Main extends \Imagify_Regenerate_Thumbnails_Deprecated {
	use \Imagify\Traits\InstanceGetterTrait;

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @author Grégory Viguier
	 */
	const VERSION = '1.2';

	/**
	 * Action used for the ajax callback.
	 *
	 * @var    string
	 * @since  1.9
	 * @author Grégory Viguier
	 */
	const HOOK_SUFFIX = 'regenerate_thumbnails';

	/**
	 * List of optimization processes.
	 *
	 * @var    array An array of ProcessInterface objects. The array keys are the media IDs.
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $processes = [];

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @var    bool
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $is_wp53;


	/** ----------------------------------------------------------------------------------------- */
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Launch the hooks.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		if ( ! class_exists( '\RegenerateThumbnails_Regenerator' ) ) {
			return;
		}

		add_filter( 'rest_dispatch_request',                [ $this, 'maybe_init_attachment' ], 4, 4 );
		add_action( 'imagify_after_' . static::HOOK_SUFFIX, [ $this, 'after_regenerate_thumbnails' ], 8, 2 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the REST dispatch request result, to hook before Regenerate Thumbnails starts its magic.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  bool            $dispatch_result Dispatch result, will be used if not empty.
	 * @param  WP_REST_Request $request         Request used to generate the response.
	 * @param  string          $route           Route matched for the request.
	 * @param  array           $handler         Route handler used for the request.
	 * @return bool
	 */
	public function maybe_init_attachment( $dispatch_result, $request, $route = null, $handler = null ) {
		if ( strpos( $route, static::get_route_prefix() ) === false ) {
			return $dispatch_result;
		}

		$media_id = $request->get_param( 'id' );

		if ( ! $this->set_process( $media_id ) ) {
			return $dispatch_result;
		}

		$media_id = $this->get_process( $media_id )->get_media()->get_id();

		// The attachment can be regenerated: keep the optimized full-sized file safe, and replace it by the backup file.
		$this->backup_optimized_file( $media_id );
		// Prevent automatic optimization.
		\Imagify_Auto_Optimization::prevent_optimization( $media_id );
		// Launch the needed hook.
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'launch_async_optimization' ], IMAGIFY_INT_MAX - 30, 2 );

		return $dispatch_result;
	}

	/**
	 * Auto-optimize after an attachment is regenerated.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $metadata An array of attachment meta data, containing the sizes that have been regenerated.
	 * @param  int   $media_id Current media ID.
	 * @return array
	 */
	public function launch_async_optimization( $metadata, $media_id ) {
		$process = $this->get_process( $media_id );

		if ( ! $process ) {
			return $metadata;
		}

		$sizes         = isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? $metadata['sizes'] : [];
		$media         = $process->get_media();
		$fullsize_path = $media->get_raw_fullsize_path();

		if ( $fullsize_path ) {
			$original_path = $media->get_original_path();

			if ( $original_path && $this->is_wp_53() && $original_path !== $fullsize_path ) {
				/**
				 * The original file and the full-sized file are not the same:
				 * That means wp_generate_attachment_metadata() recreated the full-sized file, based on the original one.
				 * So it must be optimized again now.
				 */
				$sizes['full'] = [];
			}
		}

		if ( ! $sizes ) {
			// Put the optimized full-sized file back.
			$this->put_optimized_file_back( $media_id );
			return $metadata;
		}

		/**
		 * Optimize the sizes that have been regenerated.
		 */
		// If the media has webp versions, recreate them for the sizes that have been regenerated.
		$data              = $process->get_data();
		$optimization_data = $data->get_optimization_data();

		if ( ! empty( $optimization_data['sizes'] ) ) {
			foreach ( $optimization_data['sizes'] as $size_name => $size_data ) {
				$non_webp_size_name = $process->is_size_webp( $size_name );

				if ( ! $non_webp_size_name || ! isset( $sizes[ $non_webp_size_name ] ) ) {
					continue;
				}

				// Add the webp size.
				$sizes[ $size_name ] = [];
			}
		}

		$sizes              = array_keys( $sizes );
		$optimization_level = $data->get_optimization_level();
		$optimization_args  = [ 'hook_suffix' => static::HOOK_SUFFIX ];

		// Delete related optimization data or nothing will be optimized.
		$data->delete_sizes_optimization_data( $sizes );
		$process->optimize_sizes( $sizes, $optimization_level, $optimization_args );

		$this->unset_process( $media_id );

		return $metadata;
	}

	/**
	 * Fires after regenerating the thumbnails.
	 * This puts the full-sized optimized file back.
	 *
	 * @since  1.9
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param ProcessInterface $process The optimization process.
	 * @param array            $item    The item being processed. See $this->task().
	 */
	public function after_regenerate_thumbnails( $process, $item ) {
		$media_id = $process->get_media()->get_id();

		$this->processes[ $media_id ] = $process;

		$this->put_optimized_file_back( $media_id );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Set an optimization process.
	 *
	 * @since  1.9
	 * @author Grégory Viguier
	 * @access protected
	 *
	 * @param  int $media_id The media ID.
	 * @return bool
	 */
	protected function set_process( $media_id ) {
		if ( ! $media_id || ! Imagify_Requirements::is_api_key_valid() ) {
			return false;
		}

		$process = imagify_get_optimization_process( $media_id, 'wp' );

		if ( ! $process->is_valid() || ! $process->get_media()->is_image() || ! $process->get_data()->is_optimized() ) {
			// Invalid, not animage, or no optimization have been attempted yet.
			return false;
		}

		$this->processes[ $media_id ] = $process;

		return true;
	}

	/**
	 * Unset an optimization process.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param int $media_id The media ID.
	 */
	protected function unset_process( $media_id ) {
		unset( $this->processes[ $media_id ] );
	}

	/**
	 * Unset an optimization process.
	 *
	 * @since  1.9
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  int $media_id The media ID.
	 * @return ProcessInterface|bool An optimization process object. False on failure.
	 */
	protected function get_process( $media_id ) {
		return ! empty( $this->processes[ $media_id ] ) ? $this->processes[ $media_id ] : false;
	}

	/**
	 * Backup the optimized full-sized file and replace it by the original backup file.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param int $media_id Media ID.
	 */
	protected function backup_optimized_file( $media_id ) {
		$media         = $this->get_process( $media_id )->get_media();
		$fullsize_path = $media->get_raw_fullsize_path();

		if ( ! $fullsize_path ) {
			// Uh?
			return;
		}

		$original_path = $media->get_original_path();

		if ( $original_path && $this->is_wp_53() && $original_path !== $fullsize_path ) {
			/**
			 * The original file and the full-sized file are not the same:
			 * That means wp_generate_attachment_metadata() will recreate the full-sized file, based on the original one.
			 * Then, the thumbnails will be created from a newly created (unoptimized) file.
			 */
			return;
		}

		$backup_path = $media->get_backup_path();

		if ( ! $backup_path ) {
			// No backup file, too bad.
			return;
		}

		/**
		 * Replace the optimized full-sized file by the backup, so any optimization will not use an optimized file, but the original one.
		 * The optimized full-sized file is kept and renamed, and will be put back in place at the end of the optimization process.
		 */
		$filesystem = \Imagify_Filesystem::get_instance();

		if ( $filesystem->exists( $fullsize_path ) ) {
			$tmp_file_path = static::get_temporary_file_path( $fullsize_path );
			$moved         = $filesystem->move( $fullsize_path, $tmp_file_path, true );
		}

		$filesystem->copy( $backup_path, $fullsize_path );
	}

	/**
	 * Put the optimized full-sized file back.
	 *
	 * @since  1.7.1
	 * @since  1.9 Replaced $attachment parameter by $media_id.
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param int $media_id Media ID.
	 */
	protected function put_optimized_file_back( $media_id ) {
		$file_path     = $this->get_process( $media_id )->get_media()->get_raw_fullsize_path();
		$tmp_file_path = static::get_temporary_file_path( $file_path );
		$filesystem    = \Imagify_Filesystem::get_instance();

		if ( $filesystem->exists( $tmp_file_path ) ) {
			$moved = $filesystem->move( $tmp_file_path, $file_path, true );
		}
	}

	/**
	 * Tell if we’re playing in WP 5.3’s garden.
	 *
	 * @since  1.9.8
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	protected function is_wp_53() {
		if ( isset( $this->is_wp53 ) ) {
			return $this->is_wp53;
		}

		$this->is_wp53 = function_exists( 'wp_get_original_image_path' );

		return $this->is_wp53;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** PUBLIC TOOLS ============================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the beginning of the route used to regenerate thumbnails.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string
	 */
	public static function get_route_prefix() {
		static $route;

		if ( ! isset( $route ) ) {
			$regen = \RegenerateThumbnails();

			if ( ( empty( $regen->rest_api ) || ! is_object( $regen->rest_api ) ) && method_exists( $regen, 'rest_api_init' ) ) {
				$regen->rest_api_init();
			}

			$route = '/' . trim( $regen->rest_api->namespace, '/' ) . '/regenerate/';
		}

		return $route;
	}

	/**
	 * Get the path to the temporary file.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $file_path The optimized full-sized file path.
	 * @return string
	 */
	public static function get_temporary_file_path( $file_path ) {
		return $file_path . '_backup';
	}
}
