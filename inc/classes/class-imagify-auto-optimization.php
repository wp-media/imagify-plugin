<?php

use Imagify\Traits\InstanceGetterTrait;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the auto-optimization process.
 * This occurs when a new image is uploaded, and when an optimized image is worked with (resized, etc).
 * The process will work only if wp_generate_attachment_metadata() and wp_update_attachment_metadata() are used.
 *
 * @since 1.8.4
 */
class Imagify_Auto_Optimization extends Imagify_Auto_Optimization_Deprecated {
	use InstanceGetterTrait;

	/**
	 * An array containing all the "steps" an attachment is going through.
	 * This is used to decide the behavior of the automatic optimization.
	 *
	 * @var    array {
	 *     An array of arrays with attachment ID as keys.
	 *     Each array can contain the following:
	 *
	 *     @type $upload   int Set to 1 if the attachment is a new upload.
	 *     @type $generate int Set to 1 when going though wp_generate_attachment_metadata().
	 *     @type $update   int Set to 1 when going though wp_update_attachment_metadata().
	 * }
	 * @since 1.8.4
	 * @since 1.9.10 Private.
	 * @since 1.9.10 Items are arrays instead of 1s.
	 */
	private $attachments = [];

	/**
	 * Tell if we’re using WP 5.3+.
	 *
	 * @var   bool
	 * @since 1.9.10
	 */
	private $is_wp_53;

	/**
	 * The ID of the attachment that failed to be uploaded.
	 *
	 * @var   int
	 * @since 1.9.8
	 */
	protected $upload_failure_id = 0;

	/**
	 * Used to prevent an auto-optimization locally.
	 *
	 * @var   array
	 * @since 1.8.4
	 */
	private static $prevented = [];

	/**
	 * Used to prevent an auto-optimization internally.
	 *
	 * @var   array
	 * @since 1.9.8
	 */
	private static $prevented_internally = [];

	/**
	 * Init.
	 *
	 * @since 1.8.4
	 */
	public function init() {
		global $wp_version;

		$priority       = IMAGIFY_INT_MAX - 30;
		$this->is_wp_53 = version_compare( $wp_version, '5.3-alpha1' ) >= 0;

		// Automatic optimization tunel.
		add_action( 'add_attachment',                  [ $this, 'store_upload_ids' ], $priority );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'maybe_store_generate_step' ], $priority, 2 );
		add_filter( 'wp_update_attachment_metadata',   [ $this, 'store_ids_to_optimize' ], $priority, 2 );

		if ( $this->is_wp_53 ) {
			// WP 5.3+.
			add_action( 'imagify_after_auto_optimization_init', [ $this, 'do_auto_optimization' ], $priority, 2 );
			// Upload failure recovering.
			add_action( 'wp_ajax_media-create-image-subsizes', [ $this, 'prevent_auto_optimization_when_recovering_from_upload_failure' ], -5 ); // Before WP’s hook (priority 1).
		} else {
			add_action( 'updated_post_meta',             [ $this, 'do_auto_optimization_after_meta_update' ], $priority, 4 );
			add_action( 'added_post_meta',               [ $this, 'do_auto_optimization_after_meta_update' ], $priority, 4 );
		}

		add_action( 'deleted_post_meta', [ $this, 'unset_optimization' ], $priority, 3 );

		// Prevent to re-optimize when updating the image width and height (when resizing the full image).
		add_action( 'imagify_before_update_wp_media_data_dimensions', [ __CLASS__, 'prevent_optimization' ], 5 );
		add_action( 'imagify_after_update_wp_media_data_dimensions',  [ __CLASS__, 'allow_optimization' ], 5 );
	}

	/**
	 * Remove the hooks.
	 *
	 * @since 1.8.4
	 */
	public function remove_hooks() {
		$priority = IMAGIFY_INT_MAX - 30;

		// Automatic optimization tunel.
		remove_action( 'add_attachment',                  [ $this, 'store_upload_ids' ], $priority );
		remove_filter( 'wp_generate_attachment_metadata', [ $this, 'maybe_store_generate_step' ], $priority );
		remove_filter( 'wp_update_attachment_metadata',   [ $this, 'store_ids_to_optimize' ], $priority );

		if ( $this->is_wp_53 ) {
			// WP 5.3+.
			remove_action( 'imagify_after_auto_optimization_init', [ $this, 'do_auto_optimization' ], $priority );
			// Upload failure recovering.
			remove_action( 'wp_ajax_media-create-image-subsizes', [ $this, 'prevent_auto_optimization_when_recovering_from_upload_failure' ], -5 );
		} else {
			remove_action( 'updated_post_meta',             [ $this, 'do_auto_optimization_after_meta_update' ], $priority );
			remove_action( 'added_post_meta',               [ $this, 'do_auto_optimization_after_meta_update' ], $priority );
		}

		remove_action( 'deleted_post_meta', [ $this, 'unset_optimization' ], $priority );

		// Prevent to re-optimize when updating the image width and height (when resizing the full image).
		remove_action( 'imagify_before_update_wp_media_data_dimensions', [ __CLASS__, 'prevent_optimization' ], 5 );
		remove_action( 'imagify_after_update_wp_media_data_dimensions',  [ __CLASS__, 'allow_optimization' ], 5 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Store the "upload step" when an attachment has just been uploaded.
	 *
	 * @since 1.8.4
	 * @see   $this->store_ids_to_optimize()
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function store_upload_ids( $attachment_id ) {
		if ( ! self::is_optimization_prevented( $attachment_id ) && imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->set_step( $attachment_id, 'upload' );
		}
	}

	/**
	 * Store the "generate step" when wp_generate_attachment_metadata() is used.
	 *
	 * @since 1.9.10
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function maybe_store_generate_step( $metadata, $attachment_id ) {
		if ( self::is_optimization_prevented( $attachment_id ) ) {
			return $metadata;
		}

		if ( empty( $metadata ) || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->unset_steps( $attachment_id );
			return $metadata;
		}

		$this->set_step( $attachment_id, 'generate' );

		return $metadata;
	}

	/**
	 * After the attachment meta data has been generated (partially, since WP 5.3), init the auto-optimization.
	 * Two cases are possible to trigger the optimization:
	 * - It's a new upload and auto-optimization is enabled.
	 * - It's not a new upload (it is regenerated) and the attachment is already optimized.
	 *
	 * @since 1.8.4
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

		if ( empty( $metadata ) || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->unset_steps( $attachment_id );
			return $metadata;
		}

		if ( ! $this->has_step( $attachment_id, 'generate' ) ) {
			return $metadata;
		}

		$is_new_upload = $this->has_step( $attachment_id, 'upload' );

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! isset( $auto_optimize ) ) {
				$auto_optimize = get_imagify_option( 'auto_optimize' );
			}

			if ( ! $auto_optimize ) {
				/**
				 * Fires when a new attachment is uploaded but auto-optimization is disabled.
				 *
				 * @since 1.8.4
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
			 * @since 1.6.12
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
				 * @since 1.8.4
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
			 * @since 1.8.4
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
		$this->set_step( $attachment_id, 'update' );

		/**
		 * Triggered after a media auto-optimization init.
		 *
		 * @since 1.9.8
		 *
		 * @param int  $attachment_id The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_after_auto_optimization_init', $attachment_id, $is_new_upload );

		return $metadata;
	}

	/**
	 * Launch auto optimization immediately after the post meta '_wp_attachment_metadata' is added or updated.
	 *
	 * @since 1.9
	 * @since 1.9 Previously named do_auto_optimization().
	 *
	 * @param int    $meta_id       ID of the metadata entry.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 * @param mixed  $metadata      Meta value.
	 */
	public function do_auto_optimization_after_meta_update( $meta_id, $attachment_id, $meta_key, $metadata ) {
		if ( '_wp_attachment_metadata' !== $meta_key ) {
			return;
		}

		if ( self::is_optimization_prevented( $attachment_id ) ) {
			return;
		}

		if ( ! $this->has_step( $attachment_id, 'update' ) ) {
			return;
		}

		$this->do_auto_optimization( $attachment_id, $this->has_step( $attachment_id, 'upload' ) );
	}

	/**
	 * Launch auto optimization immediately after the post meta '_wp_attachment_metadata' is added or updated.
	 *
	 * @since 1.8.4
	 * @since 1.9.8 Changed signature.
	 *
	 * @param int  $attachment_id The media ID.
	 * @param bool $is_new_upload True if it's a new upload. False otherwize.
	 */
	public function do_auto_optimization( $attachment_id, $is_new_upload ) {
		$this->unset_steps( $attachment_id );

		$process = imagify_get_optimization_process( $attachment_id, 'wp' );

		/**
		 * Fires before an attachment auto-optimization is triggered.
		 *
		 * @since 1.8.4
		 *
		 * @param int  $attachment_id The attachment ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action_deprecated( 'imagify_before_auto_optimization_launch', [ $attachment_id, $is_new_upload ], '1.9', 'imagify_before_auto_optimization' );

		/**
		 * Triggered before a media is auto-optimized.
		 *
		 * @since 1.8.4
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
		 * @since 1.8.4
		 *
		 * @param int  $attachment_id The media ID.
		 * @param bool $is_new_upload True if it's a new upload. False otherwize.
		 */
		do_action( 'imagify_after_auto_optimization', $attachment_id, $is_new_upload );
	}

	/**
	 * Remove the attachment ID from the $attachments property if the post meta '_wp_attachment_metadata' is deleted.
	 *
	 * @since 1.8.4
	 *
	 * @param int    $meta_ids      An array of deleted metadata entry IDs.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 */
	public function unset_optimization( $meta_ids, $attachment_id, $meta_key ) {
		if ( '_wp_attachment_metadata' !== $meta_key ) {
			return;
		}

		$this->unset_steps( $attachment_id );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS FOR WP 5.3+’S UPLOAD FAILURE RECOVERING =========================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * With WP 5.3+, prevent auto-optimization when WP tries to create thumbnails after an upload error, because it triggers wp_update_attachment_metadata() for each thumbnail size.
	 *
	 * @since 1.9.8
	 * @see   wp_ajax_media_create_image_subsizes()
	 * @see   wp_update_image_subsizes()
	 */
	public function prevent_auto_optimization_when_recovering_from_upload_failure() {
		if ( ! check_ajax_referer( 'media-form', false, false ) ) {
			return;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		if ( ! imagify_get_context( 'wp' )->current_user_can( 'auto-optimize' ) ) {
			return;
		}

		$attachment_id = ! empty( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;

		if ( empty( $attachment_id ) ) {
			return;
		}

		if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return;
		}

		$this->upload_failure_id = $attachment_id;

		// Auto-optimization will be done on shutdown.
		ob_start( [ $this, 'maybe_do_auto_optimization_after_recovering_from_upload_failure' ] );
	}

	/**
	 * Maybe launch auto-optimization after recovering from an upload failure, when all thumbnails are created.
	 *
	 * @since 1.9.8
	 * @see   wp_ajax_media_create_image_subsizes()
	 *
	 * @param  string $content Buffer’s content.
	 * @return string          Buffer’s content.
	 */
	public function maybe_do_auto_optimization_after_recovering_from_upload_failure( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		if ( empty( $this->upload_failure_id ) ) {
			// Uh?
			return $content;
		}

		if ( ! get_post( $this->upload_failure_id ) ) {
			return $content;
		}

		$json = @json_decode( $content );

		if ( empty( $json->success ) ) {
			return $content;
		}

		$attachment_id = $this->upload_failure_id;
		$metadata      = wp_get_attachment_metadata( $attachment_id );

		// Launch the process.
		$this->upload_failure_id = 0;
		$this->set_step( $attachment_id, 'generate' );
		$this->store_ids_to_optimize( $metadata, $attachment_id );

		return $content;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Set a "step" for an attachment.
	 *
	 * @since 1.9.10
	 * @see   $this->attachments
	 *
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $step          The step to add.
	 */
	public function set_step( $attachment_id, $step ) {
		if ( empty( $this->attachments[ $attachment_id ] ) ) {
			$this->attachments[ $attachment_id ] = [];
		}

		$this->attachments[ $attachment_id ][ $step ] = 1;
	}

	/**
	 * Unset a "step" for an attachment.
	 *
	 * @since 1.9.10
	 * @see   $this->attachments
	 *
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $step          The step to add.
	 */
	public function unset_step( $attachment_id, $step ) {
		unset( $this->attachments[ $attachment_id ][ $step ] );

		if ( empty( $this->attachments[ $attachment_id ] ) ) {
			$this->unset_steps( $attachment_id );
		}
	}

	/**
	 * Unset all "steps" for an attachment.
	 *
	 * @since 1.9.10
	 * @see   $this->attachments
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function unset_steps( $attachment_id ) {
		unset( $this->attachments[ $attachment_id ] );
	}

	/**
	 * Tell if a "step" for an attachment exists.
	 *
	 * @since 1.9.10
	 * @see   $this->attachments
	 *
	 * @param  int    $attachment_id Current attachment ID.
	 * @param  string $step          The step to add.
	 * @return bool
	 */
	public function has_step( $attachment_id, $step ) {
		return ! empty( $this->attachments[ $attachment_id ][ $step ] );
	}

	/**
	 * Prevent an auto-optimization locally.
	 * How to use it:
	 *     Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
	 *     wp_update_attachment_metadata( $attachment_id );
	 *     Imagify_Auto_Optimization::allow_optimization( $attachment_id );
	 *
	 * @since 1.8.4
	 * @since 1.9.8 Prevents/Allows can stack.
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public static function prevent_optimization( $attachment_id ) {
		if ( ! isset( self::$prevented[ $attachment_id ] ) ) {
			self::$prevented[ $attachment_id ] = 1;
		} else {
			++self::$prevented[ $attachment_id ];
		}
	}

	/**
	 * Allow an auto-optimization locally.
	 * How to use it:
	 *     Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
	 *     wp_update_attachment_metadata( $attachment_id );
	 *     Imagify_Auto_Optimization::allow_optimization( $attachment_id );
	 *
	 * @since 1.8.4
	 * @since 1.9.8 Prevents/Allows can stack.
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public static function allow_optimization( $attachment_id ) {
		if ( ! isset( self::$prevented[ $attachment_id ] ) ) {
			return;
		}
		--self::$prevented[ $attachment_id ];

		if ( self::$prevented[ $attachment_id ] <= 0 ) {
			unset( self::$prevented[ $attachment_id ] );
		}
	}

	/**
	 * Tell if an auto-optimization is prevented locally.
	 *
	 * @since 1.8.4
	 *
	 * @param  int $attachment_id Current attachment ID.
	 * @return bool
	 */
	public static function is_optimization_prevented( $attachment_id ) {
		return ! empty( self::$prevented[ $attachment_id ] ) || ! empty( self::$prevented_internally[ $attachment_id ] );
	}

	/**
	 * Prevent an auto-optimization internally.
	 *
	 * @since 1.9.8
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	protected static function prevent_optimization_internally( $attachment_id ) {
		self::$prevented_internally[ $attachment_id ] = 1;
	}

	/**
	 * Allow an auto-optimization internally.
	 *
	 * @since 1.9.8
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	protected static function allow_optimization_internally( $attachment_id ) {
		unset( self::$prevented_internally[ $attachment_id ] );
	}
}
