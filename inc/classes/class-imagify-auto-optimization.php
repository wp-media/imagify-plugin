<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles the auto-optimization process.
 * This occurs when a new image is uploaded, and when an optimized image is worked with (resized, etc).
 *
 * @since  1.8.3
 * @author Grégory Viguier
 */
class Imagify_Auto_Optimization {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * An array containing the IDs (as keys) of attachments just being uploaded.
	 *
	 * @var array
	 */
	protected $uploads = array();

	/**
	 * An array containing the IDs (as keys) of attachments that must be optimized automatically.
	 *
	 * @var array
	 */
	protected $attachments = array();

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.8.3
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $filesystem;

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;

	/**
	 * Get the main Instance.
	 *
	 * Ensures only one instance of class is loaded or can be loaded.
	 *
	 * @since  1.8.3
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
	 * The class constructor.
	 *
	 * @since  1.8.3
	 * @author Grégory Viguier
	 */
	protected function __construct() {
		$this->filesystem = Imagify_Filesystem::get_instance();
	}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.8.3
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}

		$done = true;
		$prio = IMAGIFY_INT_MAX - 30;

		add_filter( 'wp_generate_attachment_metadata', array( $this, 'store_upload_ids' ), $prio, 2 );
		add_filter( 'wp_update_attachment_metadata',   array( $this, 'store_ids_to_optimize' ), $prio, 2 );
		add_action( 'updated_post_meta',               array( $this, 'do_auto_optimization' ), $prio, 4 );
		add_action( 'added_post_meta',                 array( $this, 'do_auto_optimization' ), $prio, 4 );
		add_action( 'deleted_post_meta',               array( $this, 'unset_optimization' ), $prio, 3 );
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
	 * @author Grégory Viguier
	 * @see    $this->store_ids_to_optimize()
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
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
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

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! isset( $auto_optimize ) ) {
				$auto_optimize = Imagify_Requirements::is_api_key_valid() && get_imagify_option( 'auto_optimize' );
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
			$attachment = get_imagify_attachment( 'wp', $attachment_id, 'imagify_auto_optimization' );

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
		$this->attachments[ $attachment_id ] = 1;

		return $metadata;
	}

	/**
	 * Launch auto optimization immediately after the post meta '_wp_attachment_metadata' is added or updated.
	 *
	 * @since  1.8.3
	 * @author Grégory Viguier
	 *
	 * @param int    $meta_id       ID of the metadata entry.
	 * @param int    $attachment_id Current attachment ID.
	 * @param string $meta_key      Meta key.
	 * @param mixed  $metadata      Meta value.
	 */
	public function do_auto_optimization( $meta_id, $attachment_id, $meta_key, $metadata ) {
		if ( '_wp_attachment_metadata' !== $meta_key || empty( $this->attachments[ $attachment_id ] ) ) {
			return;
		}

		unset( $this->attachments[ $attachment_id ] );

		// Some specifics for the image editor.
		if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
			check_ajax_referer( 'image_editor-' . $_POST['postid'] );
			$data = $_POST;
		} else {
			$data = array();
		}

		set_transient( 'imagify-auto-optimize-' . $attachment_id, 1, 60 );

		imagify_do_async_job( array(
			'action'      => 'imagify_async_optimize',
			'_ajax_nonce' => wp_create_nonce( 'imagify_async_optimize' ),
			'post_id'     => $attachment_id,
			'metadata'    => $metadata,
			'data'        => $data,
		) );
	}

	/**
	 * Removes the attachment ID from the $attachments property if the post meta '_wp_attachment_metadata' is deleted.
	 *
	 * @since  1.8.3
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
}
