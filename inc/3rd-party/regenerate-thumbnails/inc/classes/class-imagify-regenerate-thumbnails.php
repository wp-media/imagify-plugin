<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles compatibility with Regenerate Thumbnails plugin.
 *
 * @since  1.7.1
 * @author Grégory Viguier
 */
class Imagify_Regenerate_Thumbnails {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @author Grégory Viguier
	 */
	const VERSION = '1.1';

	/**
	 * Action used for the ajax callback.
	 *
	 * @var    string
	 * @since  1.7.1
	 * @author Grégory Viguier
	 */
	const ACTION = 'imagify_regenerate_thumbnails';

	/**
	 * List of the attachments to regenerate.
	 *
	 * @var    array An array of Imagify attachments. The array keys are the attachment IDs.
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $attachments = array();

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCE ================================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.6.11
	 * @access public
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
	 * The constructor.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {}


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
		add_filter( 'rest_dispatch_request',           array( $this, 'maybe_init_attachment' ), 4, 4 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'launch_async_optimization' ), IMAGIFY_INT_MAX, 2 );
		add_action( 'wp_ajax_' . self::ACTION,         array( $this, 'regenerate_thumbnails_callback' ) );
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
		if ( strpos( $route, self::get_route_prefix() ) === false ) {
			return $dispatch_result;
		}

		$attachment_id = $request->get_param( 'id' );
		$attachment    = $this->set_attachment( $attachment_id );

		if ( $attachment ) {
			// The attachment can be regenerated: backup the optimized full-sized file.
			$this->backup_optimized_file( $attachment_id );
			// Prevent automatic optimization.
			Imagify_Auto_Optimization::prevent_optimization( $attachment_id );
		}

		return $dispatch_result;
	}

	/**
	 * Auto-optimize after an attachment is regenerated.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function launch_async_optimization( $metadata, $attachment_id ) {
		if ( ! $this->get_attachment( $attachment_id ) ) {
			return $metadata;
		}

		$sizes = is_array( $metadata['sizes'] ) ? $metadata['sizes'] : array();

		if ( ! $sizes ) {
			// Put the optimized full-sized file back.
			$this->put_optimized_file_back( $this->get_attachment( $attachment_id ) );
			// Allow auto-optimization back.
			Imagify_Auto_Optimization::allow_optimization( $attachment_id );

			return $metadata;
		}

		$action      = self::ACTION;
		$context     = $this->get_attachment( $attachment_id )->get_context();
		$_ajax_nonce = wp_create_nonce( self::get_nonce_name( $attachment_id, $context ) );

		imagify_do_async_job( compact( 'action', '_ajax_nonce', 'sizes', 'attachment_id', 'context' ) );

		return $metadata;
	}

	/**
	 * Optimize the newly regenerated thumbnails.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 */
	public function regenerate_thumbnails_callback() {
		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'Invalid request', 'imagify' ) );
		}

		if ( empty( $_POST['sizes'] ) || ! is_array( $_POST['sizes'] ) ) { // WPCS: CSRF ok.
			imagify_die( __( 'No thumbnail sizes selected', 'imagify' ) );
		}

		$attachment_id = absint( $_POST['attachment_id'] );
		$context       = imagify_sanitize_context( $_POST['context'] );

		imagify_check_nonce( self::get_nonce_name( $attachment_id, $context ) );
		imagify_check_user_capacity( 'manual-optimize', $attachment_id );

		$attachment = get_imagify_attachment( $context, $attachment_id, self::ACTION );

		if ( ! $attachment->is_valid() || ! $attachment->is_image() ) {
			wp_send_json_error();
		}

		// Optimize.
		$attachment->reoptimize_thumbnails( $_POST['sizes'] );

		// Put the optimized original file back.
		$this->put_optimized_file_back( $attachment );

		wp_send_json_success();
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Set the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @return object|false       An Imagify attachment object. False on failure.
	 */
	protected function set_attachment( $attachment_id ) {
		if ( ! $attachment_id || ! Imagify_Requirements::is_api_key_valid() ) {
			return false;
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'regenerate_thumbnails' );

		if ( ! $attachment->is_valid() || ! $attachment->is_image() || ! $attachment->is_optimized() ) {
			return false;
		}

		// This attachment can be optimized.
		$this->attachments[ $attachment_id ] = $attachment;
		return $this->attachments[ $attachment_id ];
	}

	/**
	 * Unset the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	protected function unset_attachment( $attachment_id ) {
		unset( $this->attachments[ $attachment_id ] );
	}

	/**
	 * Get the Imagify attachment.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @return object|false       An Imagify attachment object. False on failure.
	 */
	protected function get_attachment( $attachment_id ) {
		return ! empty( $this->attachments[ $attachment_id ] ) ? $this->attachments[ $attachment_id ] : false;
	}

	/**
	 * Backup the optimized full-sized file and replace it by the original backup file.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	protected function backup_optimized_file( $attachment_id ) {
		$attachment  = $this->get_attachment( $attachment_id );
		$backup_path = $attachment->get_backup_path();

		if ( ! $backup_path ) {
			return;
		}

		/**
		 * Replace the optimized full-sized file by the backup, so any optimization will not use an optimized file, but the original one.
		 * The optimized full-sized file is kept and renamed, and will be put back in place at the end of the optimization process.
		 */
		$filesystem    = Imagify_Filesystem::get_instance();
		$file_path     = $attachment->get_original_path();
		$tmp_file_path = self::get_temporary_file_path( $file_path );

		if ( $filesystem->exists( $file_path ) ) {
			$filesystem->move( $file_path, $tmp_file_path, true );
		}

		$filesystem->copy( $backup_path, $file_path );
	}

	/**
	 * Put the optimized full-sized file back.
	 *
	 * @since  1.7.1
	 * @access protected
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 */
	protected function put_optimized_file_back( $attachment ) {
		$filesystem    = Imagify_Filesystem::get_instance();
		$file_path     = $attachment->get_original_path();
		$tmp_file_path = self::get_temporary_file_path( $file_path );

		if ( $filesystem->exists( $tmp_file_path ) ) {
			$filesystem->move( $tmp_file_path, $file_path, true );
		}
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
			$route = '/' . trim( RegenerateThumbnails()->rest_api->namespace, '/' ) . '/regenerate/';
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

	/**
	 * Get the name of the nonce used for the ajax callback.
	 *
	 * @since  1.7.1
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  int    $attachment_id The attachment ID.
	 * @param  string $context       The context.
	 * @return string
	 */
	public static function get_nonce_name( $attachment_id, $context ) {
		return self::ACTION . '-' . $attachment_id . '-' . $context;
	}
}
