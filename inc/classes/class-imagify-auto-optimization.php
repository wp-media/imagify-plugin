<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the auto-optimization process.
 * This occurs when a new image is uploaded, and when an optimized image is worked with (resized, etc).
 * The process will work only if wp_update_attachment_metadata() is used.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 */
class Imagify_Auto_Optimization {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8.4
	 * @author Grégory Viguier
	 */
	const VERSION = '1.1';

	/**
	 * An array containing the IDs (as keys) of attachments just being uploaded.
	 *
	 * @var    array
	 * @since  1.8.4
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $uploads = [];

	/**
	 * An array containing the IDs (as keys) of attachments that must be optimized automatically.
	 * The values tell if the attachment is a new upload.
	 *
	 * @var    array
	 * @since  1.8.4
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachments = [];

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.8.4
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $instance;

	/**
	 * Used to prevent an auto-optimization locally.
	 *
	 * @var    array
	 * @since  1.8.4
	 * @access private
	 * @author Grégory Viguier
	 */
	private static $prevented = [];

	/**
	 * Get the main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return object Main instance.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * The class constructor.
	 *
	 * @since  1.8.4
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Init.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		$prio = IMAGIFY_INT_MAX - 30;

		// Automatic optimization tunel.
		add_action( 'add_attachment',                [ $this, 'store_upload_ids' ], $prio );
		add_filter( 'wp_update_attachment_metadata', [ $this, 'store_ids_to_optimize' ], $prio, 2 );
		add_action( 'updated_post_meta',             [ $this, 'do_auto_optimization' ], $prio, 4 );
		add_action( 'added_post_meta',               [ $this, 'do_auto_optimization' ], $prio, 4 );
		add_action( 'deleted_post_meta',             [ $this, 'unset_optimization' ], $prio, 3 );

		// Prevent to re-optimize when updating the image width and height (when resizing the full image).
		add_action( 'imagify_before_update_wp_media_data_dimensions', [ __CLASS__, 'prevent_optimization' ], 5 );
		add_action( 'imagify_after_update_wp_media_data_dimensions',  [ __CLASS__, 'allow_optimization' ], 5 );
	}

	/**
	 * Remove the hooks.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 */
	public function remove_hooks() {
		$prio = IMAGIFY_INT_MAX - 30;

		remove_action( 'add_attachment',                                 [ $this, 'store_upload_ids' ], $prio );
		remove_filter( 'wp_update_attachment_metadata',                  [ $this, 'store_ids_to_optimize' ], $prio );
		remove_action( 'updated_post_meta',                              [ $this, 'do_auto_optimization' ], $prio );
		remove_action( 'added_post_meta',                                [ $this, 'do_auto_optimization' ], $prio );
		remove_action( 'deleted_post_meta',                              [ $this, 'unset_optimization' ], $prio );
		remove_action( 'imagify_before_update_wp_media_data_dimensions', [ __CLASS__, 'prevent_optimization' ], 5 );
		remove_action( 'imagify_after_update_wp_media_data_dimensions',  [ __CLASS__, 'allow_optimization' ], 5 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Store the ID of attachments that just have been uploaded.
	 * We use those IDs to tell the difference later in `wp_update_attachment_metadata()`.
	 *
	 * @since  1.8.4
	 * @access public
	 * @see    $this->store_ids_to_optimize()
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function store_upload_ids( $attachment_id ) {
		if ( ! self::is_optimization_prevented( $attachment_id ) && imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}
	}

	/**
	 * After the attachment meta data has been generated, launch an async optimization.
	 * Two cases are possible to trigger the optimization:
	 * - It's a new upload and auto-optimization is enabled.
	 * - It's not a new upload (it is regenerated) and the attachment is already optimized.
	 *
	 * @since  1.8.4
	 * @access public
	 * @see    $this->store_upload_ids()
	 * @author Grégory Viguier
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_ids_to_optimize( $metadata, $attachment_id ) {
		static $auto_optimize;

		if ( self::is_optimization_prevented( $attachment_id ) ) {
			return $metadata;
		}

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! isset( $auto_optimize ) ) {
				$auto_optimize = get_imagify_option( 'auto_optimize' );
			}

			if ( ! $auto_optimize ) {
				/**
				 * Fires when a new attachment is uploaded but auto-optimization is disabled.
				 *
				 * @since  1.8.4
				 * @author Grégory Viguier
				 *
				 * @param int   $attachment_id Attachment ID.
				 * @param array $metadata      An array of attachment meta data.
				 */
				do_action( 'imagify_new_attachment_auto_optimization_disabled', $attachment_id, $metadata );

				return $metadata;
			}

			/**
			 * Allow to prevent automatic optimization for a specific attachment.
			 *
			 * @since  1.6.12
			 * @author Grégory Viguier
			 *
			 * @param bool  $optimize      True to optimize, false otherwise.
			 * @param int   $attachment_id Attachment ID.
			 * @param array $metadata      An array of attachment meta data.
			 */
			$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

			if ( ! $optimize ) {
				return $metadata;
			}

			/**
			 * It's a new upload and auto-optimization is enabled.
			 */
		}

		if ( ! $is_new_upload ) {
			// An existing attachment being regenerated (or something).
			$process = imagify_get_optimization_process( $attachment_id, 'wp' );

			if ( ! $process->is_valid() ) {
				// Uh?
				return $metadata;
			}

			if ( ! $process->get_data()->get_optimization_status() ) {
				/**
				 * Fires when an attachment is updated but not optimized yet.
				 *
				 * @since  1.8.4
				 * @author Grégory Viguier
				 *
				 * @param int   $attachment_id Attachment ID.
				 * @param array $metadata      An array of attachment meta data.
				 */
				do_action( 'imagify_not_optimized_attachment_updated', $attachment_id, $metadata );

				return $metadata;
			}

			/**
			 * Allow to prevent automatic reoptimization for a specific attachment.
			 *
			 * @since  1.8.4
			 * @author Grégory Viguier
			 *
			 * @param bool  $optimize      True to optimize, false otherwise.
			 * @param int   $attachment_id Attachment ID.
			 * @param array $metadata      An array of attachment meta data.
			 */
			$optimize = apply_filters( 'imagify_auto_optimize_optimized_attachment', true, $attachment_id, $metadata );

			if ( ! $optimize ) {
				return $metadata;
			}

			/**
			 * The attachment already exists and was already optimized.
			 */
		}

		// Ready for the next step.
		$this->attachments[ $attachment_id ] = $is_new_upload;

		return $metadata;
	}

	/**
	 * Launch auto optimization immediately after the post meta '_wp_attachment_metadata' is added or updated.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int    $meta_id       ID of the metadata entry.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 * @param mixed  $metadata      Meta value.
	 */
	public function do_auto_optimization( $meta_id, $attachment_id, $meta_key, $metadata ) {
		if ( '_wp_attachment_metadata' !== $meta_key || ! isset( $this->attachments[ $attachment_id ] ) ) {
			return;
		}

		if ( self::is_optimization_prevented( $attachment_id ) ) {
			return;
		}

		$is_new_upload = $this->attachments[ $attachment_id ];
		unset( $this->attachments[ $attachment_id ] );

		$process = imagify_get_optimization_process( $attachment_id, 'wp' );

		/**
		 * Fires before an attachment auto-optimization is triggered.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $attachment_id The attachment ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action_deprecated( 'imagify_before_auto_optimization_launch', [ $attachment_id, $is_new_upload ], '1.9', 'imagify_before_auto_optimization' );

		/**
		 * Triggered before a media is auto-optimized.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $attachment_id The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_before_auto_optimization', $attachment_id, $is_new_upload );

		if ( $is_new_upload ) {
			/**
			 * It's a new upload.
			 */
			// Optimize.
			$process->optimize( null, [ 'is_new_upload' => 1 ] );
		} else {
			/**
			 * The media has already been optimized (or at least it has been tried).
			 */
			$process_data = $process->get_data();

			// Get the optimization level before deleting the optimization data.
			$optimization_level = $process_data->get_optimization_level();

			// Some specifics for the image editor.
			if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
				check_ajax_referer( 'image_editor-' . $attachment_id );

				if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
					imagify_die();
				}

				// Restore the backup file.
				$result = $process->restore();

				if ( is_wp_error( $result ) ) {
					// Restoration failed, there is no good way to handle this case.
					$process_data->delete_optimization_data();
				}
			} else {
				// Remove old optimization data.
				$process_data->delete_optimization_data();
			}

			// Optimize.
			$process->optimize( $optimization_level );
		}

		/**
		 * Triggered after a media auto-optimization is launched.
		 *
		 * @since  1.8.4
		 * @author Grégory Viguier
		 *
		 * @param int  $attachment_id The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_after_auto_optimization', $attachment_id, $is_new_upload );
	}

	/**
	 * Remove the attachment ID from the $attachments property if the post meta '_wp_attachment_metadata' is deleted.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int    $meta_ids      An array of deleted metadata entry IDs.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 */
	public function unset_optimization( $meta_ids, $attachment_id, $meta_key ) {
		if ( '_wp_attachment_metadata' !== $meta_key || ! isset( $this->attachments[ $attachment_id ] ) ) {
			return;
		}

		unset( $this->attachments[ $attachment_id ] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Prevent an auto-optimization locally.
	 * How to use it:
	 *     Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
	 *     wp_update_attachment_metadata( $attachment_id );
	 *     Imagify_Auto_Optimization::allow_optimization( $attachment_id );
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public static function prevent_optimization( $attachment_id ) {
		self::$prevented[ $attachment_id ] = 1;
	}

	/**
	 * Allow an auto-optimization locally.
	 * How to use it:
	 *     Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
	 *     wp_update_attachment_metadata( $attachment_id );
	 *     Imagify_Auto_Optimization::allow_optimization( $attachment_id );
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public static function allow_optimization( $attachment_id ) {
		unset( self::$prevented[ $attachment_id ] );
	}

	/**
	 * Tell if an auto-optimization is prevented locally.
	 *
	 * @since  1.8.4
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int $attachment_id Current attachment ID.
	 * @return bool
	 */
	public static function is_optimization_prevented( $attachment_id ) {
		return ! empty( self::$prevented[ $attachment_id ] );
	}
}
