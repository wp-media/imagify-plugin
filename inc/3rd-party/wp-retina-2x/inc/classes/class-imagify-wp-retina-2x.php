<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class that handles compatibility with WP Retina 2x plugin.
 *
 * @since  1.8
 * @author Grégory Viguier
 */
class Imagify_WP_Retina_2x {

	/**
	 * Class version.
	 *
	 * @var    string
	 * @since  1.8
	 * @author Grégory Viguier
	 */
	const VERSION = '1.0';

	/**
	 * Core instance.
	 *
	 * @var    object Imagify_WP_Retina_2x_Core
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected $core;

	/**
	 * The single instance of the class.
	 *
	 * @var    object Imagify_WP_Retina_2x
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected static $_instance;


	/** ----------------------------------------------------------------------------------------- */
	/** INSTANCE ================================================================================ */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Get the main Instance.
	 *
	 * @since  1.8
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
	 * @since  1.8
	 * @access protected
	 * @author Grégory Viguier
	 */
	protected function __construct() {}

	/**
	 * Get the core Instance.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return object Imagify_WP_Retina_2x_Core instance.
	 */
	public function get_core() {
		if ( ! isset( $this->core ) ) {
			$this->core = new Imagify_WP_Retina_2x_Core();
		}

		return $this->core;
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INIT ==================================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Launch the hooks.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function init() {
		// Deal with Imagify when WPR2X is working.
		add_action( 'wp_ajax_wr2x_generate',                    array( $this, 'wr2x_generate_ajax_cb' ), 5 );
		add_action( 'wp_ajax_wr2x_delete',                      array( $this, 'wr2x_delete_all_retina_ajax_cb' ), 5 );
		add_action( 'wp_ajax_wr2x_delete_full',                 array( $this, 'wr2x_delete_full_retina_ajax_cb' ), 5 );
		add_action( 'wp_ajax_wr2x_replace',                     array( $this, 'wr2x_replace_all_ajax_cb' ), 5 );
		add_action( 'wp_ajax_wr2x_upload',                      array( $this, 'wr2x_replace_full_retina_ajax_cb' ), 5 );
		add_action( 'imagify_assets_enqueued',                  array( $this, 'enqueue_scripts' ) );
		add_action( 'wr2x_retina_file_removed',                 array( $this, 'remove_retina_thumbnail_data_hook' ), 10, 2 );
		// Deal with Imagify when WP is working.
		add_action( 'delete_attachment',                        array( $this, 'delete_full_retina_backup_file_hook' ) );
		// Deal with retina thumbnails when Imagify processes the "normal" images.
		add_filter( 'imagify_fill_full_size_data',              array( $this, 'optimize_full_retina_version_hook' ), 10, 8 );
		add_filter( 'imagify_fill_thumbnail_data',              array( $this, 'optimize_retina_version_hook' ), 10, 8 );
		add_filter( 'imagify_fill_unauthorized_thumbnail_data', array( $this, 'maybe_optimize_unauthorized_retina_version_hook' ), 10, 7 );
		add_action( 'after_imagify_restore_attachment',         array( $this, 'restore_retina_images_hook' ) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** AJAX CALLBACKS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * (Re)generate the retina thumbnails (except the full size).
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function wr2x_generate_ajax_cb() {
		$this->check_nonce( 'imagify_wr2x_generate' );
		$this->check_user_capacity();

		$attachment = $this->get_requested_attachment( 'wr2x_generate' );

		// Delete previous retina images and recreate them.
		$result = $this->get_core()->regenerate_retina_images( $attachment );

		// Send results.
		$this->maybe_send_json_error( $result );

		$this->send_json( array(
			'results'      => $this->get_core()->get_retina_info( $attachment ),
			'message'      => __( 'Retina files generated.', 'imagify' ),
			'imagify_info' => $this->get_imagify_info( $attachment ),
		) );
	}

	/**
	 * Delete all retina images, including the one for the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function wr2x_delete_all_retina_ajax_cb() {
		$this->check_nonce( 'imagify_wr2x_delete' );
		$this->check_user_capacity();

		$attachment = $this->get_requested_attachment( 'wr2x_delete_all' );

		// Delete the retina versions, including the full size.
		$result = $this->get_core()->delete_retina_images( $attachment, true );

		// Send results.
		$this->maybe_send_json_error( $result );

		$this->send_json( array(
			'results'      => $this->get_core()->get_retina_info( $attachment ),
			'results_full' => $this->get_core()->get_retina_info( $attachment, 'full' ),
			'message'      => __( 'Retina files deleted.', 'imagify' ),
		) );
	}

	/**
	 * Delete the retina version of the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function wr2x_delete_full_retina_ajax_cb() {
		$this->check_nonce( 'imagify_wr2x_delete_full' );
		$this->check_user_capacity();

		$attachment = $this->get_requested_attachment( 'wr2x_delete_full' );

		$result = $this->get_core()->delete_full_retina_image( $attachment );

		// Send results.
		$this->maybe_send_json_error( $result );

		$this->send_json( array(
			'results' => $this->get_core()->get_retina_info( $attachment, 'full' ),
			'message' => __( 'Full retina file deleted.', 'imagify' ),
		) );
	}

	/**
	 * Replace an attachment (except the retina version of the full size).
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function wr2x_replace_all_ajax_cb() {
		$this->check_nonce( 'imagify_wr2x_replace' );
		$this->check_user_capacity();

		$attachment    = $this->get_requested_attachment( 'wr2x_replace_all' );
		$tmp_file_path = $this->get_uploaded_file_path();

		$result = $this->get_core()->replace_attachment( $attachment, $tmp_file_path );

		// Send results.
		$this->maybe_send_json_error( $result );

		$this->send_json( array(
			'results' => $this->get_core()->get_retina_info( $attachment ),
			'message' => __( 'Images replaced successfully.', 'imagify' ),
		) );
	}

	/**
	 * Upload a new retina version for the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function wr2x_replace_full_retina_ajax_cb() {
		$this->check_nonce( 'imagify_wr2x_upload' );
		$this->check_user_capacity();

		$attachment    = $this->get_requested_attachment( 'wr2x_replace_full' );
		$tmp_file_path = $this->get_uploaded_file_path();

		$result = $this->get_core()->replace_full_retina_image( $attachment, $tmp_file_path );

		// Send results.
		$this->maybe_send_json_error( $result );

		$this->send_json( array(
			'results' => $this->get_core()->get_retina_info( $attachment ),
			'message' => __( 'Image replaced successfully.', 'imagify' ),
		) );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** OTHER HOOKS ============================================================================= */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Queue some JS to add our nonce parameter to all WR2X jQuery ajax requests.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function enqueue_scripts() {
		if ( ! $this->user_can() ) {
			return;
		}

		$assets = Imagify_Assets::get_instance();

		$assets->register_script( 'weakmap-polyfill', 'weakmap-polyfill', array(), '2.0.0' );
		$assets->register_script( 'formdata-polyfill', 'formdata-polyfill', array( 'weakmap-polyfill' ), '3.0.10-beta' );
		$assets->register_script( 'wp-retina-2x', 'imagify-wp-retina-2x', array( 'formdata-polyfill', 'jquery' ) );

		if ( imagify_is_screen( 'library' ) || imagify_is_screen( 'media_page_wp-retina-2x' ) ) {
			$assets->localize_script( 'wp-retina-2x', 'imagifyRetina2x', array(
				'wr2x_generate'    => wp_create_nonce( 'imagify_wr2x_generate' ),
				'wr2x_delete'      => wp_create_nonce( 'imagify_wr2x_delete' ),
				'wr2x_delete_full' => wp_create_nonce( 'imagify_wr2x_delete_full' ),
				'wr2x_replace'     => wp_create_nonce( 'imagify_wr2x_replace' ),
				'wr2x_upload'      => wp_create_nonce( 'imagify_wr2x_upload' ),
			) );
			$assets->enqueue( 'wp-retina-2x' );
		}
	}

	/**
	 * After a retina thumbnail is deleted, remove its Imagify data.
	 * This should be useless since we replaced every AJAX callbacks.
	 *
	 * @since  1.8
	 * @access public
	 * @see    wr2x_delete_attachment()
	 * @author Grégory Viguier
	 *
	 * @param int    $attachment_id   An attachment ID.
	 * @param string $retina_filename The retina thumbnail file name.
	 */
	public function remove_retina_thumbnail_data_hook( $attachment_id, $retina_filename ) {
		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'wr2x_delete' );

		$this->get_core()->remove_retina_image_data_by_filename( $attachment, $retina_filename );
	}

	/**
	 * Delete the backup of the retina version of the full size file when an attachement is deleted.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id An attachment ID.
	 */
	public function delete_full_retina_backup_file_hook( $attachment_id ) {
		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			return;
		}

		$attachment  = get_imagify_attachment( 'wp', $attachment_id, 'delete_attachment' );
		$retina_path = $this->get_core()->get_retina_path( $attachment->get_original_path() );

		if ( $retina_path ) {
			$this->get_core()->delete_file_backup( $retina_path );
		}
	}

	/**
	 * Filter the optimization data of the full size.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $data               The statistics data.
	 * @param  object $response           The API response.
	 * @param  int    $attachment_id      The attachment ID.
	 * @param  string $path               The attachment path.
	 * @param  string $url                The attachment URL.
	 * @param  string $size_key           The attachment size key. The value is obviously 'full' but it's kept for oncistancy with other filters.
	 * @param  int    $optimization_level The optimization level.
	 * @param  array  $metadata           WP metadata.
	 * @return array  $data               The new optimization data.
	 */
	public function optimize_full_retina_version_hook( $data, $response, $attachment_id, $path, $url, $size_key, $optimization_level, $metadata ) {
		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			return $data;
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'optimize_full_retina_version_hook' );

		return $this->get_core()->optimize_retina_image( array(
			'data'               => $data,
			'attachment'         => get_imagify_attachment( 'wp', $attachment_id, 'optimize_full_retina_version_hook' ),
			'retina_path'        => wr2x_get_retina( $path ),
			'size_key'           => $size_key,
			'optimization_level' => $optimization_level,
			'metadata'           => $metadata,
		) );
	}

	/**
	 * Filter the optimization data of each thumbnail.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $data               The statistics data.
	 * @param  object $response           The API response.
	 * @param  int    $attachment_id      The attachment ID.
	 * @param  string $path               The thumbnail path.
	 * @param  string $url                The thumbnail URL.
	 * @param  string $size_key           The thumbnail size key.
	 * @param  int    $optimization_level The optimization level.
	 * @param  array  $metadata           WP metadata.
	 * @return array  $data               The new optimization data.
	 */
	public function optimize_retina_version_hook( $data, $response, $attachment_id, $path, $url, $size_key, $optimization_level, $metadata ) {
		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			return $data;
		}

		return $this->get_core()->optimize_retina_image( array(
			'data'               => $data,
			'attachment'         => get_imagify_attachment( 'wp', $attachment_id, 'optimize_retina_version_hook' ),
			'retina_path'        => wr2x_get_retina( $path ),
			'size_key'           => $size_key,
			'optimization_level' => $optimization_level,
			'metadata'           => $metadata,
		) );
	}

	/**
	 * If a thumbnail size is disallowed in Imagify' settings, we can still try to optimize its "@2x" version.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  array  $data               The statistics data.
	 * @param  int    $attachment_id      The attachment ID.
	 * @param  string $path               The thumbnail path.
	 * @param  string $url                The thumbnail URL.
	 * @param  string $size_key           The thumbnail size key.
	 * @param  int    $optimization_level The optimization level.
	 * @param  array  $metadata           WP metadata.
	 * @return array  $data               The new optimization data.
	 */
	public function maybe_optimize_unauthorized_retina_version_hook( $data, $attachment_id, $path, $url, $size_key, $optimization_level, $metadata ) {
		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			return $data;
		}

		return $this->get_core()->optimize_retina_image( array(
			'data'               => $data,
			'attachment'         => get_imagify_attachment( 'wp', $attachment_id, 'maybe_optimize_unauthorized_retina_version_hook' ),
			'retina_path'        => wr2x_get_retina( $path ),
			'size_key'           => $size_key,
			'optimization_level' => $optimization_level,
			'metadata'           => $metadata,
		) );
	}

	/**
	 * Delete previous retina images and recreate them.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param int $attachment_id An attachment ID.
	 */
	public function restore_retina_images_hook( $attachment_id ) {
		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			return;
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'restore_retina_images_hook' );

		if ( ! $this->get_core()->has_retina_images( $attachment ) ) {
			return;
		}

		// At this point, previous Imagify data has been removed.
		$this->get_core()->regenerate_retina_images( $attachment );
		$this->get_core()->restore_full_retina_file( $attachment );
	}


	/** ----------------------------------------------------------------------------------------- */
	/** INTERNAL TOOLS ========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Check for nonce.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param string      $action Action nonce.
	 * @param string|bool $query_arg Optional. Key to check for the nonce in `$_REQUEST`. If false, `$_REQUEST` values will be evaluated for '_ajax_nonce', and '_wpnonce' (in that order). Default false.
	 */
	public function check_nonce( $action, $query_arg = 'imagify_nonce' ) {
		if ( ! check_ajax_referer( $action, $query_arg, false ) ) {
			$this->send_json( array(
				'success' => false,
				'message' => __( 'Sorry, you are not allowed to do that.', 'imagify' ),
			) );
		}
	}

	/**
	 * Check for user capacity.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function check_user_capacity() {
		if ( ! $this->user_can() ) {
			$this->send_json( array(
				'success' => false,
				'message' => __( 'Sorry, you are not allowed to do that.', 'imagify' ),
			) );
		}
	}

	/**
	 * Tell if the current user can re-optimize files.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 */
	public function user_can() {
		return imagify_current_user_can( 'auto-optimize' );
	}

	/**
	 * Shorthand to get the attachment ID sent via $_POST.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  string $context The context to use in get_imagify_attachment().
	 * @param  string $key     The $_POST key.
	 * @return int    $attachment_id
	 */
	public function get_requested_attachment( $context, $key = 'attachmentId' ) {
		$attachment_id = filter_input( INPUT_POST, $key, FILTER_VALIDATE_INT );

		if ( $attachment_id <= 0 ) {
			$this->send_json( array(
				'success' => false,
				'message' => __( 'The attachment ID is missing.', 'imagify' ),
			) );
		}

		if ( ! $this->get_core()->is_supported_format( $attachment_id ) ) {
			$this->send_json( array(
				'success' => false,
				'message' => __( 'This format is not supported.', 'imagify' ),
			) );
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, $context );

		if ( ! $this->has_required_metadata( $attachment ) ) {
			$this->send_json( array(
				'success' => false,
				'message' => __( 'This attachment lacks the required metadata.', 'imagify' ),
			) );
		}

		return $attachment;
	}

	/**
	 * Shorthand to get the path to the uploaded file.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return string Path to the temporary file.
	 */
	public function get_uploaded_file_path() {
		$tmp_file_path = ! empty( $_FILES['file']['tmp_name'] ) && is_uploaded_file( $_FILES['file']['tmp_name'] ) ? $_FILES['file']['tmp_name'] : '';
		$filesystem    = Imagify_Filesystem::get_instance();

		if ( ! $tmp_file_path || ! $filesystem->is_image( $tmp_file_path ) ) {
			$this->get_core()->log( 'The file is not an image or the upload went wrong.' );
			$filesystem->delete( $tmp_file_path );

			$this->send_json_string( array(
				'success' => false,
				'message' => __( 'The file is not an image or the upload went wrong.', 'imagify' ),
			) );
		}

		$file_name = filter_input( INPUT_POST, 'filename', FILTER_SANITIZE_STRING );
		$file_data = wp_check_filetype_and_ext( $tmp_file_path, $file_name );

		if ( empty( $file_data['ext'] ) ) {
			$this->get_core()->log( 'You cannot use this file (wrong extension? wrong type?).' );
			$filesystem->delete( $tmp_file_path );

			$this->send_json_string( array(
				'success' => false,
				'message' => __( 'You cannot use this file (wrong extension? wrong type?).', 'imagify' ),
			) );
		}

		$this->get_core()->log( 'The temporary file was written successfully.' );

		return $tmp_file_path;
	}

	/**
	 * Tell if Imagify's column content has been requested.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @return bool
	 */
	public function needs_info() {
		return filter_input( INPUT_POST, 'imagify_info', FILTER_VALIDATE_INT ) === 1;
	}

	/**
	 * Tell if the attachment has the required WP metadata.
	 *
	 * @since  1.8
	 * @access public
	 * @see    $wr2x_core->is_image_meta()
	 * @author Grégory Viguier
	 *
	 * @param object $attachment An Imagify attachment.
	 * @return bool
	 */
	public function has_required_metadata( $attachment ) {
		if ( ! $attachment->has_required_metadata() ) {
			return false;
		}

		$metadata = wp_get_attachment_metadata( $attachment->get_id() );

		if ( ! isset( $metadata['sizes'], $metadata['width'], $metadata['height'] ) ) {
			return false;
		}

		return is_array( $metadata['sizes'] );
	}

	/**
	 * Get info about Imagify.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param  object $attachment An Imagify attachment.
	 * @return array              An array containing some HTML, indexed by the attachment ID.
	 */
	public function get_imagify_info( $attachment ) {
		if ( ! $this->needs_info() ) {
			return array();
		}

		return array(
			$attachment->get_id() => get_imagify_media_column_content( $attachment ),
		);
	}

	/**
	 * Send a JSON response back to an Ajax request.
	 * It sends a "success" by default.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param array $data An array of data to print and die.
	 */
	public function send_json( $data ) {
		// Use the same JSON format than WPR2X.
		$data = array_merge( array(
			'success' => true,
			'message' => '',
			'source'  => 'imagify',
			'context' => 'wr2x',
		), $data );

		echo wp_json_encode( $data );
		die;
	}

	/**
	 * Send a JSON error response if the given argument is a WP_Error object.
	 *
	 * @since  1.8
	 * @access public
	 * @author Grégory Viguier
	 *
	 * @param mixed $result Result of an operation.
	 */
	public function maybe_send_json_error( $result ) {
		if ( ! is_wp_error( $result ) ) {
			return;
		}

		// Oh no.
		$this->send_json( array(
			'success' => false,
			'message' => $result->get_error_message(),
		) );
	}
}
