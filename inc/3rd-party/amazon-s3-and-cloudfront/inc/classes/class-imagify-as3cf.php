<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Imagify WP Offload S3 class.
 *
 * @since  1.6.6
 * @author Grégory Viguier
 */
class Imagify_AS3CF {

	/**
	 * Class version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * Context used with get_imagify_attachment_class_name().
	 * It matches the class name Imagify_AS3CF_Attachment.
	 *
	 * @var string
	 */
	const CONTEXT = 'AS3CF';

	/**
	 * An array containing the IDs (as keys) of attachments just being uploaded.
	 *
	 * @var array
	 */
	protected $uploads = array();

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
	 * @since  1.6.6
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
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Launch the hooks.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	public function init() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;

		/**
		 * One context to rule 'em all.
		 */
		add_filter( 'imagify_optimize_attachment_context', array( $this, 'optimize_attachment_context' ), 10, 2 );

		/**
		 * Automatic optimisation.
		 */
		// Remove some of our hooks: let S3 work first in these cases.
		remove_filter( 'wp_generate_attachment_metadata',                       '_imagify_optimize_attachment', PHP_INT_MAX );
		remove_action( 'wp_ajax_imagify_async_optimize_as3cf',                  '_do_admin_post_async_optimize_upload_new_media' );
		remove_action( 'shutdown',                                              '_imagify_optimize_save_image_editor_file' );
		remove_action( 'wp_ajax_imagify_async_optimize_save_image_editor_file', '_do_admin_post_async_optimize_save_image_editor_file' );

		// Store the IDs of the attachments being uploaded.
		add_filter( 'wp_generate_attachment_metadata',      array( $this, 'store_upload_ids' ), 10, 2 );
		// Once uploaded to S3, launch the async optimization.
		add_filter( 'wp_update_attachment_metadata',        array( $this, 'do_async_job' ), 210, 2 );
		// Do the optimization in a new thread.
		add_action( 'wp_ajax_imagify_async_optimize_as3cf', array( $this, 'optimize' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** VARIOUS HOOKS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the context used for the optimization (and other stuff).
	 * That way, we'll use the class Imagify_AS3CF_Attachment everywhere (instead of Imagify_Attachment), and make all the manual optimizations fine.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  string $context       The context to determine the class name.
	 * @param  int    $attachment_id The attachment ID.
	 * @return string                The new context.
	 */
	public function optimize_attachment_context( $context, $attachment_id ) {
		if ( self::CONTEXT === $context || $this->is_mime_type_supported( $attachment_id ) ) {
			return self::CONTEXT;
		}
		return $context;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** AUTOMATIC OPTIMIZATION: OPTIMIZE AFTER S3 HAS DONE ITS WORK ============================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Filter the generated attachment meta data.
	 * This is used when a new attachment has just been uploaded (or not, when wp_generate_attachment_metadata() is used).
	 * We use it to tell the difference later in wp_update_attachment_metadata().
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 * @see    $this->do_async_job()
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {

		if ( $this->is_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After an image (maybe) being sent to S3, launch an async optimization.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function do_async_job( $metadata, $attachment_id ) {
		static $auto_optimize;

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! $this->is_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( ! isset( $auto_optimize ) ) {
			$auto_optimize = get_imagify_option( 'api_key' ) && get_imagify_option( 'auto_optimize' );
		}

		if ( $is_new_upload && ! $auto_optimize ) {
			// It's a new upload and auto-optimization is disabled.
			return $metadata;
		}

		if ( ! $is_new_upload && ! get_post_meta( $attachment_id, '_imagify_data', true ) ) {
			// It's not a new upload and the attachment is not optimized yet.
			return $metadata;
		}

		$data = array();

		// Some specifics for the image editor.
		if ( isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) && 'image-editor' === $_POST['action'] && (int) $_POST['postid'] === $attachment_id ) { // WPCS: CSRF ok.
			check_ajax_referer( 'image_editor-' . $_POST['postid'] );
			$data = $_POST;
		}

		imagify_do_async_job( array(
			'action'      => 'imagify_async_optimize_as3cf',
			'_ajax_nonce' => wp_create_nonce( 'imagify_async_optimize_as3cf' ),
			'post_id'     => $attachment_id,
			'metadata'    => $metadata,
			'data'        => $data,
		) );

		return $metadata;
	}

	/**
	 * Once an image has been sent to S3, optimize it and send it again.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 */
	public function optimize() {
		global $as3cf;

		check_ajax_referer( 'imagify_async_optimize_as3cf' );

		if ( empty( $_POST['post_id'] ) || ! current_user_can( 'upload_files' ) ) {
			die();
		}

		$attachment_id = absint( $_POST['post_id'] );

		if ( ! $attachment_id || empty( $_POST['metadata'] ) || ! is_array( $_POST['metadata'] ) || empty( $_POST['metadata']['sizes'] ) ) {
			die();
		}

		if ( ! $this->is_mime_type_supported( $attachment_id ) ) {
			die();
		}

		$optimization_level = null;
		$class_name         = get_imagify_attachment_class_name( self::CONTEXT, $attachment_id, 'as3cf_optimize' );
		$attachment         = new $class_name( $attachment_id );

		// Some specifics for the image editor.
		if ( ! empty( $_POST['data']['do'] ) ) {
			$optimization_level = (int) get_post_meta( $attachment_id, '_imagify_optimization_level', true );

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			if ( 'restore' === $_POST['data']['do'] ) {
				// Restore the backup file.
				$attachment->restore();
			}
		}

		// Optimize it.
		$attachment->optimize( $optimization_level, $_POST['metadata'] );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** TOOLS =================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Tell if the attachment has a supported mime type.
	 *
	 * @since  1.6.6
	 * @author Grégory Viguier
	 *
	 * @param  int $post_id The attachment ID.
	 * @return bool
	 */
	public function is_mime_type_supported( $post_id ) {
		static $is = array( false );

		$post_id = absint( $post_id );

		if ( isset( $is[ $post_id ] ) ) {
			return $is[ $post_id ];
		}

		$mime_types     = get_imagify_mime_type();
		$mime_types     = array_flip( $mime_types );
		$mime_type      = get_post_mime_type( $post_id );
		$is[ $post_id ] = isset( $mime_types[ $mime_type ] );

		return $is[ $post_id ];
	}
}

/**
 * Returns the main instance of the Imagify_AS3CF class.
 *
 * @since  1.6.6
 * @author Grégory Viguier
 *
 * @return object The Imagify_AS3CF instance.
 */
function imagify_as3cf() {
	return Imagify_AS3CF::get_instance();
}
