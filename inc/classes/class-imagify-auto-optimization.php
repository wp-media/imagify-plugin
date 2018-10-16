<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the auto-optimization process.
 * This occurs when a new image is uploaded, and when an optimized image is worked with (resized, etc).
 * The process will work only if wp_update_attachment_metadata() is used.
 *
 * @since  1.8.3
 * @author Grégory Viguier
 */
class Imagify_Auto_Optimization {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8.3
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * An array containing the IDs (as keys) of attachments just being uploaded.
	 *
	 * @var    array
	 * @since  1.8.3
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $uploads = array();

	/**
	 * An array containing the IDs (as keys) of attachments that must be optimized automatically.
	 * The values tell if the attachment is a new upload.
	 *
	 * @var    array
	 * @since  1.8.3
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachments = array();

	/**
	 * The single instance of the class.
	 *
	 * @var    object
	 * @since  1.8.3
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $instance;

	/**
	 * Used to prevent an auto-optimization locally.
	 *
	 * @var    array
	 * @since  1.8.3
	 * @access private
	 * @author Grégory Viguier
	 */
	private static $prevented = array();

	/**
	 * Get the main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.8.3
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
	 * @since  1.8.3
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Init.
	 *
	 * @since  1.8.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		$prio = IMAGIFY_INT_MAX - 30;

		add_filter( 'wp_generate_attachment_metadata', array( $this, 'store_upload_ids' ), $prio, 2 );
		add_filter( 'wp_update_attachment_metadata',   array( $this, 'store_ids_to_optimize' ), $prio, 2 );
		add_action( 'updated_post_meta',               array( $this, 'do_auto_optimization' ), $prio, 4 );
		add_action( 'added_post_meta',                 array( $this, 'do_auto_optimization' ), $prio, 4 );
		add_action( 'deleted_post_meta',               array( $this, 'unset_optimization' ), $prio, 3 );

		// Prevent to re-optimize an attachment after a restore.
		add_action( 'before_imagify_restore_attachment', array( $this, 'allow_restore_before' ), 5 );
		add_action( 'after_imagify_restore_attachment',  array( $this, 'allow_restore_after' ), 5 );
	}

	/**
	 * Remove the hooks.
	 *
	 * @since  1.8.3
	 * @access public
	 * @author Grégory Viguier
	 */
	public function remove_hooks() {
		$prio = IMAGIFY_INT_MAX - 30;

		remove_filter( 'wp_generate_attachment_metadata',   array( $this, 'store_upload_ids' ), $prio, 2 );
		remove_filter( 'wp_update_attachment_metadata',     array( $this, 'store_ids_to_optimize' ), $prio, 2 );
		remove_action( 'updated_post_meta',                 array( $this, 'do_auto_optimization' ), $prio, 4 );
		remove_action( 'added_post_meta',                   array( $this, 'do_auto_optimization' ), $prio, 4 );
		remove_action( 'deleted_post_meta',                 array( $this, 'unset_optimization' ), $prio, 3 );
		remove_action( 'before_imagify_restore_attachment', array( $this, 'allow_restore_before' ), 5 );
		remove_action( 'after_imagify_restore_attachment',  array( $this, 'allow_restore_after' ), 5 );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** HOOKS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the generated attachment meta data.
	 * This is used when a new attachment has just been uploaded (or not, when wp_generate_attachment_metadata() is used).
	 * We use it to tell the difference later in wp_update_attachment_metadata().
	 *
	 * @since  1.8.3
	 * @access public
	 * @see    $this->store_ids_to_optimize()
	 * @author Grégory Viguier
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {

		if ( imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After the attachment meta data has been generated, launch an async optimization.
	 * Two cases are possible to trigger the optimization:
	 * - It's a new upload and auto-optimization is enabled.
	 * - It's not a new upload (it is regenerated) and the attachment is already optimized.
	 *
	 * @since  1.8.3
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

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( self::is_optimization_prevented( $attachment_id ) ) {
			self::allow_optimization( $attachment_id );
			return $metadata;
		}

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! isset( $auto_optimize ) ) {
				$auto_optimize = get_imagify_option( 'auto_optimize' );
			}

			if ( ! $auto_optimize ) {
				// Auto-optimization is disabled.
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
			$attachment = get_imagify_attachment( 'wp', $attachment_id, 'auto_optimization' );

			if ( ! $attachment->get_data() ) {
				// The attachment is not optimized yet.
				return $metadata;
			}

			/**
			 * Allow to prevent automatic reoptimization for a specific attachment.
			 *
			 * @since  1.8.3
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
	 * @since  1.8.3
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

		$is_new_upload = $this->attachments[ $attachment_id ];
		unset( $this->attachments[ $attachment_id ] );

		// Some specifics for the image editor.
		if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
			check_ajax_referer( 'image_editor-' . $attachment_id );

			if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
				imagify_die();
			}

			$data = $_POST;
		} else {
			$data = array();
		}

		// Instead of using user permissions, use a transient.
		set_transient( 'imagify-auto-optimize-' . $attachment_id, 1, 60 );

		imagify_do_async_job( array(
			'action'        => 'imagify_auto_optimize',
			'_ajax_nonce'   => wp_create_nonce( 'imagify_auto_optimize-' . $attachment_id ),
			'attachment_id' => $attachment_id,
			'is_new_upload' => $is_new_upload,
			'context'       => 'wp',
			'data'          => $data,
		) );
	}

	/**
	 * Remove the attachment ID from the $attachments property if the post meta '_wp_attachment_metadata' is deleted.
	 *
	 * @since  1.8.3
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

	/**
	 * Prevent auto-optimization during a restore.
	 *
	 * @since  1.8.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function allow_restore_before( $attachment_id ) {
		self::prevent_optimization( $attachment_id );
	}

	/**
	 * Re-enable auto-optimization during a restore.
	 *
	 * @since  1.8.3
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Current attachment ID.
	 */
	public function allow_restore_after( $attachment_id ) {
		self::allow_optimization( $attachment_id );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Prevent an auto-optimization locally.
	 * How to use it:
	 *     Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
	 *     wp_update_attachment_metadata( $attachment_id );
	 *
	 * @since  1.8.3
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
	 *
	 * @since  1.8.3
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
	 * @since  1.8.3
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
