<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class for deprecated methods from Imagify.
 *
 * @since  1.6.5
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Deprecated {

	/**
	 * A shorthand to display a message about a deprecated method.
	 *
	 * @since  1.6.5
	 * @since  1.6.5 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $method_name The deprecated method.
	 */
	protected function deprecated_camelcased_method( $method_name ) {
		$class_name      = get_class( $this );
		$new_method_name = preg_replace( '/[A-Z]/', '_$0', $method_name );
		_deprecated_function( $class_name . '::' . $method_name . '()', '1.6.5', $class_name . '::' . $new_method_name . '()' );
	}

	/**
	 * Main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 * Well, actually it ensures nothing since it's not a full singleton pattern.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object Main instance.
	 */
	public static function instance() {
		_deprecated_function( 'Imagify::instance()', '1.6.5', 'Imagify::get_instance()' );
		return Imagify::get_instance();
	}

	/**
	 * Get your Imagify account infos.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getUser() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_user();
	}

	/**
	 * Create a user on your Imagify account.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  array $data All user data.
	 * @return object
	 */
	public function createUser( $data ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->create_user( $data );
	}

	/**
	 * Update an existing user on your Imagify account.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $data All user data.
	 * @return object
	 */
	public function updateUser( $data ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->update_user( $data );
	}

	/**
	 * Check your Imagify API key status.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $data The license key.
	 * @return object
	 */
	public function getStatus( $data ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_status( $data );
	}

	/**
	 * Get the Imagify API version.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getApiVersion() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_api_version();
	}

	/**
	 * Get Public Info.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getPublicInfo() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_public_info();
	}

	/**
	 * Optimize an image from its binary content.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $data All options.
	 * @return object
	 */
	public function uploadImage( $data ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->upload_image( $data );
	}

	/**
	 * Optimize an image from its URL.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $data All options.
	 * @return object
	 */
	public function fetchImage( $data ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->fetch_image( $data );
	}

	/**
	 * Get prices for plans.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getPlansPrices() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_plans_prices();
	}

	/**
	 * Get prices for packs (One Time).
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getPacksPrices() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_packs_prices();
	}

	/**
	 * Get all prices (packs & plans included).
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function getAllPrices() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->get_all_prices();
	}

	/**
	 * Get all prices (packs & plans included).
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $coupon A coupon code.
	 * @return object
	 */
	public function checkCouponCode( $coupon ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->check_coupon_code( $coupon );
	}

	/**
	 * Get information about current discount.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @return object
	 */
	public function checkDiscount() {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->check_discount();
	}

	/**
	 * Make an HTTP call using curl.
	 *
	 * @since 1.6.5 Deprecated
	 * @deprecated
	 *
	 * @param  string $url  The URL to call.
	 * @param  array  $args The request args.
	 * @return object
	 */
	private function httpCall( $url, $args = array() ) {
		$this->deprecated_camelcased_method( __FUNCTION__ );
		return $this->http_call( $url, $args );
	}
}

/**
 * Class for deprecated methods from Imagify_Abstract_DB.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Abstract_DB_Deprecated {

	/**
	 * Check if the given table exists.
	 *
	 * @since  1.5 In Imagify_Abstract_DB.
	 * @since  1.7 Deprecated.
	 * @access public
	 * @deprecated
	 *
	 * @param  string $table The table name.
	 * @return bool          True if the table name exists.
	 */
	public function table_exists( $table ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.7.0', 'Imagify_DB::table_exists( $table )' );

		return Imagify_DB::table_exists( $table );
	}

	/**
	 * Main Instance.
	 * Ensures only one instance of class is loaded or can be loaded.
	 * Well, actually it ensures nothing since it's not a full singleton pattern.
	 *
	 * @since  1.5 In Imagify_NGG_DB.
	 * @since  1.7 Deprecated.
	 * @access public
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @return object Main instance.
	 */
	public static function instance() {
		_deprecated_function( 'Imagify_Abstract_DB::instance()', '1.6.5', 'Imagify_Abstract_DB::get_instance()' );

		return self::get_instance();
	}
}

/**
 * Class for deprecated methods from Imagify_Abstract_Attachment.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Abstract_Attachment_Deprecated {

	/**
	 * Maybe backup a file.
	 *
	 * @since  1.6.6 In Imagify_AS3CF_Attachment.
	 * @since  1.6.8 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $attachment_path  The file path.
	 * @return bool|null                True on success. False on failure. Null if backup is not needed.
	 */
	protected function maybe_backup( $attachment_path ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.6.8', 'imagify_backup_file()' );

		$result = imagify_backup_file( $attachment_path );

		if ( false === $result ) {
			return null;
		}

		return ! is_wp_error( $result );
	}
}

/**
 * Class for deprecated methods from Imagify_AS3CF.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_AS3CF_Deprecated {

	/**
	 * Tell if the attachment has a supported mime type.
	 *
	 * @since  1.6.6 In Imagify_AS3CF.
	 * @since  1.6.8 Deprecated.
	 * @see    imagify_is_attachment_mime_type_supported()
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  int $post_id The attachment ID.
	 * @return bool
	 */
	public function is_mime_type_supported( $post_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.6.8', 'imagify_is_attachment_mime_type_supported( $post_id )' );

		return imagify_is_attachment_mime_type_supported( $post_id );
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
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->do_async_job()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function store_upload_ids( $metadata, $attachment_id ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->store_upload_ids( $attachment_id )' );

		if ( imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			$this->uploads[ $attachment_id ] = 1;
		}

		return $metadata;
	}

	/**
	 * After an image (maybe) being sent to S3, launch an async optimization.
	 *
	 * @since  1.6.6
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->store_upload_ids()
	 * @deprecated
	 *
	 * @param  array $metadata      An array of attachment meta data.
	 * @param  int   $attachment_id Current attachment ID.
	 * @return array
	 */
	public function do_async_job( $metadata, $attachment_id ) {
		static $auto_optimize;

		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->do_auto_optimization( $meta_id, $attachment_id, $meta_key, $metadata )' );

		$is_new_upload = ! empty( $this->uploads[ $attachment_id ] );
		unset( $this->uploads[ $attachment_id ] );

		if ( ! $metadata || ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			return $metadata;
		}

		if ( ! isset( $auto_optimize ) ) {
			$auto_optimize = Imagify_Requirements::is_api_key_valid() && get_imagify_option( 'auto_optimize' );
		}

		if ( $is_new_upload ) {
			// It's a new upload.
			if ( ! $auto_optimize ) {
				// Auto-optimization is disabled.
				return $metadata;
			}

			/** This filter is documented in inc/common/attachments.php. */
			$optimize = apply_filters( 'imagify_auto_optimize_attachment', true, $attachment_id, $metadata );

			if ( ! $optimize ) {
				return $metadata;
			}
		}

		if ( ! $is_new_upload ) {
			$attachment = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_async_job' );

			if ( ! $attachment->get_data() ) {
				// It's not a new upload and the attachment is not optimized yet.
				return $metadata;
			}
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
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function optimize() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		check_ajax_referer( 'imagify_async_optimize_as3cf' );

		if ( empty( $_POST['post_id'] ) || ! imagify_current_user_can( 'auto-optimize' ) ) {
			die();
		}

		$attachment_id = absint( $_POST['post_id'] );

		if ( ! $attachment_id || empty( $_POST['metadata'] ) || ! is_array( $_POST['metadata'] ) || empty( $_POST['metadata']['sizes'] ) ) {
			die();
		}

		if ( ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
			die();
		}

		$optimization_level = null;
		$attachment         = get_imagify_attachment( self::CONTEXT, $attachment_id, 'as3cf_optimize' );

		// Some specifics for the image editor.
		if ( ! empty( $_POST['data']['do'] ) ) {
			$optimization_level = $attachment->get_optimization_level();

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
}

/**
 * Class for deprecated methods from Imagify_Notices.
 *
 * @since  1.7
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Notices_Deprecated {

	/**
	 * Include the view file.
	 *
	 * @since  1.6.10 In Imagify_Notices
	 * @since  1.7 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $view The view ID.
	 * @param mixed  $data Some data to pass to the view.
	 */
	public function render_view( $view, $data = array() ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'notice-\' . $view, $data )' );

		Imagify_Views::get_instance()->print_template( 'notice-' . $view, $data );
	}
}

/**
 * Class for deprecated methods from Imagify_Admin_Ajax_Post.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Admin_Ajax_Post_Deprecated {

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @see    _imagify_optimize_attachment()
	 * @deprecated
	 */
	public function imagify_async_optimize_upload_new_media_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		if ( empty( $_POST['_ajax_nonce'] ) || empty( $_POST['attachment_id'] ) || empty( $_POST['metadata'] ) || empty( $_POST['context'] ) ) { // WPCS: CSRF ok.
			return;
		}

		$context       = imagify_sanitize_context( $_POST['context'] );
		$attachment_id = absint( $_POST['attachment_id'] );

		imagify_check_nonce( 'new_media-' . $attachment_id );
		imagify_check_user_capacity( 'auto-optimize' );

		$attachment = get_imagify_attachment( $context, $attachment_id, 'imagify_async_optimize_upload_new_media' );

		// Optimize it!!!!!
		$attachment->optimize( null, $_POST['metadata'] );
		die( 1 );
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.6.11
	 * @since  1.8.4 Deprecated
	 * @access public
	 * @author Julio Potier
	 * @deprecated
	 */
	public function imagify_async_optimize_save_image_editor_file_callback() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_auto_optimize_callback()' );

		$attachment_id = ! empty( $_POST['postid'] ) ? absint( $_POST['postid'] ) : 0;

		if ( ! $attachment_id || empty( $_POST['do'] ) ) {
			return;
		}

		imagify_check_nonce( 'image_editor-' . $attachment_id );
		imagify_check_user_capacity( 'edit_post', $attachment_id );

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'wp_ajax_imagify_async_optimize_save_image_editor_file' );

		if ( ! $attachment->get_data() ) {
			return;
		}

		$optimization_level = $attachment->get_optimization_level();
		$metadata           = wp_get_attachment_metadata( $attachment_id );

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		if ( 'restore' === $_POST['do'] ) {
			// Restore the backup file.
			$attachment->restore();

			// Get old metadata to regenerate all thumbnails.
			$metadata     = array( 'sizes' => array() );
			$backup_sizes = (array) get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			foreach ( $backup_sizes as $size_key => $size_data ) {
				$size_key = str_replace( '-origin', '' , $size_key );
				$metadata['sizes'][ $size_key ] = $size_data;
			}
		}

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level, $metadata );
		die( 1 );
	}
}

/**
 * Compat class for Enable Media Replace plugin.
 *
 * @since  1.8.4
 * @author Grégory Viguier
 * @deprecated
 */
class Imagify_Enable_Media_Replace_Deprecated {

	/**
	 * Filesystem object.
	 *
	 * @var    object Imagify_Filesystem
	 * @since  1.7.1
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @access protected
	 */
	protected $filesystem;

	/**
	 * Optimize the attachment files if the old ones were also optimized.
	 * Delete the old backup file.
	 *
	 * @since  1.6.9
	 * @since  1.8.4 Deprecated
	 * @author Grégory Viguier
	 * @see    $this->store_old_backup_path()
	 * @access protected
	 *
	 * @param  string $return_url The URL the user will be redirected to.
	 * @return string             The same URL.
	 */
	public function optimize( $return_url ) {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.8.4' );

		$attachment = $this->get_attachment();

		if ( $attachment->get_data() ) {
			/**
			 * The old images have been optimized in the past.
			 */
			// Use the same otimization level for the new ones.
			$optimization_level = $attachment->get_optimization_level();

			// Remove old optimization data.
			$attachment->delete_imagify_data();

			// Optimize and overwrite the previous backup file if exists and needed.
			add_filter( 'imagify_backup_overwrite_backup', '__return_true', 42 );
			$attachment->optimize( $optimization_level );
			remove_filter( 'imagify_backup_overwrite_backup', '__return_true', 42 );
		}

		$filesystem = Imagify_Filesystem::get_instance();

		/**
		 * Delete the old backup file.
		 */
		if ( ! $this->old_backup_path || ! $filesystem->exists( $this->old_backup_path ) ) {
			// The user didn't choose to rename the files, or there is no old backup.
			$this->old_backup_path = null;
			return $return_url;
		}

		$new_backup_path = $attachment->get_raw_backup_path();

		if ( $new_backup_path === $this->old_backup_path ) {
			// We don't want to delete the new backup.
			$this->old_backup_path = null;
			return $return_url;
		}

		// Finally, delete the old backup file.
		$filesystem->delete( $this->old_backup_path );

		$this->old_backup_path = null;
		return $return_url;
	}
}

if ( class_exists( 'WpeCommon' ) ) :

	/**
	 * Change the limit for the number of posts: WP Engine limits SQL queries size to 2048 octets (16384 characters).
	 *
	 * @since  1.4.7
	 * @since  1.6.7 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @return int
	 */
	function _imagify_wengine_unoptimized_attachment_limit() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.7', '_imagify_wpengine_unoptimized_attachment_limit()' );
		return _imagify_wpengine_unoptimized_attachment_limit();
	}

endif;

if ( function_exists( 'emr_delete_current_files' ) ) :

	/**
	 * Re-Optimize an attachment after replace it with Enable Media Replace.
	 *
	 * @since  1.0
	 * @since  1.6.9 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @param string $guid A post guid.
	 */
	function _imagify_optimize_enable_media_replace( $guid ) {
		global $wpdb;

		_deprecated_function( __FUNCTION__ . '()', '1.6.9', 'imagify_enable_media_replace()->optimize()' );

		$attachment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s;", $guid ) );

		if ( ! $attachment_id ) {
			return;
		}

		$attachment = get_imagify_attachment( 'wp', $attachment_id, 'enable-media-replace-upload-done' );

		// Stop if the attachment wasn't optimized yet by Imagify.
		if ( ! $attachment->get_data() ) {
			return;
		}

		$optimization_level = $attachment->get_optimization_level();

		// Remove old optimization data.
		$attachment->delete_imagify_data();

		// Optimize it!!!!!
		$attachment->optimize( $optimization_level );
	}

endif;

/**
 * Include Admin Bar Profile informations styles in front.
 *
 * @since 1.2
 * @since 1.6.10 Deprecated.
 * @deprecated
 */
function _imagify_admin_bar_styles() {
	_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->enqueue_styles_and_scripts_frontend()' );

	if ( ! is_admin() ) {
		Imagify_Assets::get_instance()->enqueue_styles_and_scripts_frontend();
	}
}

/**
 * Make an absolute path relative to WordPress' root folder.
 * Also works for files from registered symlinked plugins.
 *
 * @since  1.6.8
 * @since  1.6.10 Deprecated. Don't laugh.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @return string            A relative path. Can return the absolute path in case of a failure.
 */
function imagify_make_file_path_replative( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'imagify_get_filesystem()->make_path_relative( $file_path )' );

	return imagify_get_filesystem()->make_path_relative( $file_path );
}

if ( is_admin() && ( function_exists( 'as3cf_init' ) || function_exists( 'as3cf_pro_init' ) ) ) :

	/**
	 * Returns the main instance of the Imagify_AS3CF class.
	 *
	 * @since  1.6.6
	 * @since  1.6.12 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return object The Imagify_AS3CF instance.
	 */
	function imagify_as3cf() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.12', 'Imagify_AS3CF::get_instance()' );

		return Imagify_AS3CF::get_instance();
	}

endif;

if ( function_exists( 'enable_media_replace' ) ) :

	/**
	 * Returns the main instance of the Imagify_Enable_Media_Replace class.
	 *
	 * @since  1.6.9
	 * @since  1.6.12 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return object The Imagify_Enable_Media_Replace instance.
	 */
	function imagify_enable_media_replace() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.12', 'Imagify_Enable_Media_Replace::get_instance()' );

		return Imagify_Enable_Media_Replace::get_instance();
	}

endif;

if ( class_exists( 'C_NextGEN_Bootstrap' ) && class_exists( 'Mixin' ) && get_site_option( 'ngg_options' ) ) :

	/**
	 * Returns the main instance of the Imagify_NGG class.
	 *
	 * @since  1.6.5
	 * @since  1.6.12 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @return object The Imagify_NGG instance.
	 */
	function imagify_ngg() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.12', 'Imagify_NGG::get_instance()' );

		return Imagify_NGG::get_instance();
	}

	/**
	 * Returns the main instance of the Imagify_NGG_DB class.
	 *
	 * @since  1.6.5
	 * @since  1.6.12 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @return object The Imagify_NGG_DB instance.
	 */
	function imagify_ngg_db() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.12', 'Imagify_NGG_DB::get_instance()' );

		return Imagify_NGG_DB::get_instance();
	}

	/**
	 * Delete the Imagify data when an image is deleted.
	 *
	 * @since  1.5
	 * @since  1.6.13 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 *
	 * @param int $image_id An image ID.
	 */
	function _imagify_ngg_delete_picture( $image_id ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.13', 'Imagify_NGG_DB::get_instance()->delete( $image_id )' );

		Imagify_NGG_DB::get_instance()->delete( $image_id );
	}

	/**
	 * Create the Imagify table needed for NGG compatibility.
	 *
	 * @since  1.5
	 * @since  1.7 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_create_ngg_table() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_NGG_DB::get_instance()->maybe_upgrade_table()' );

		Imagify_NGG_DB::get_instance()->maybe_upgrade_table();
	}

	/**
	 * Update all Imagify stats for NGG Bulk Optimization.
	 *
	 * @since  1.5
	 * @since  1.7 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_ngg_update_bulk_stats() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'imagify_ngg_bulk_page_data()' );

		if ( empty( $_GET['page'] ) || imagify_get_ngg_bulk_screen_slug() !== $_GET['page'] ) { // WPCS: CSRF ok.
			return;
		}

		add_filter( 'imagify_count_attachments'             , 'imagify_ngg_count_attachments' );
		add_filter( 'imagify_count_optimized_attachments'   , 'imagify_ngg_count_optimized_attachments' );
		add_filter( 'imagify_count_error_attachments'       , 'imagify_ngg_count_error_attachments' );
		add_filter( 'imagify_count_unoptimized_attachments' , 'imagify_ngg_count_unoptimized_attachments' );
		add_filter( 'imagify_percent_optimized_attachments' , 'imagify_ngg_percent_optimized_attachments' );
		add_filter( 'imagify_count_saving_data'             , 'imagify_ngg_count_saving_data', 8 );
	}

	/**
	 * Prepare the data that goes back with the Heartbeat API.
	 *
	 * @since 1.5
	 * @since 1.7.1 Deprecated.
	 * @deprecated
	 *
	 * @param  array $response  The Heartbeat response.
	 * @param  array $data      The $_POST data sent.
	 * @return array
	 */
	function _imagify_ngg_heartbeat_received( $response, $data ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7.1' );

		if ( ! isset( $data['imagify_heartbeat'] ) || 'update_ngg_bulk_data' !== $data['imagify_heartbeat'] ) {
			return $response;
		}

		add_filter( 'imagify_count_saving_data', 'imagify_ngg_count_saving_data', 8 );
		$saving_data = imagify_count_saving_data();
		$user        = new Imagify_User();

		$response['imagify_bulk_data'] = array(
			// User account.
			'unconsumed_quota'              => is_wp_error( $user ) ? 0 : $user->get_percent_unconsumed_quota(),
			// Global chart.
			'optimized_attachments_percent' => imagify_ngg_percent_optimized_attachments(),
			'unoptimized_attachments'       => imagify_ngg_count_unoptimized_attachments(),
			'optimized_attachments'         => imagify_ngg_count_optimized_attachments(),
			'errors_attachments'            => imagify_ngg_count_error_attachments(),
			// Stats block.
			'already_optimized_attachments' => number_format_i18n( $saving_data['count'] ),
			'original_human'                => imagify_size_format( $saving_data['original_size'], 1 ),
			'optimized_human'               => imagify_size_format( $saving_data['optimized_size'], 1 ),
			'optimized_percent'             => $saving_data['percent'],
		);

		return $response;
	}

endif;

if ( function_exists( 'wr2x_delete_attachment' ) ) :

	/**
	 * Remove all retina versions if they exist.
	 *
	 * @since 1.0
	 * @since 1.8 Deprecated.
	 * @deprecated
	 *
	 * @param int $attachment_id An attachment ID.
	 */
	function _imagify_wr2x_delete_attachment_on_restore( $attachment_id ) {
		_deprecated_function( __FUNCTION__ . '()', '1.8' );

		wr2x_delete_attachment( $attachment_id );
	}

	/**
	 * Regenerate all retina versions.
	 *
	 * @since 1.0
	 * @since 1.8 Deprecated.
	 * @deprecated
	 *
	 * @param int $attachment_id An attachment ID.
	 */
	function _imagify_wr2x_generate_images_on_restore( $attachment_id ) {
		_deprecated_function( __FUNCTION__ . '()', '1.8' );

		wr2x_delete_attachment( $attachment_id );
		wr2x_generate_images( wp_get_attachment_metadata( $attachment_id ) );
	}

	/**
	 * Filter the optimization data of each thumbnail.
	 *
	 * @since 1.0
	 * @since 1.8 Deprecated.
	 * @deprecated
	 *
	 * @param  array  $data               The statistics data.
	 * @param  object $response           The API response.
	 * @param  int    $id                 The attachment ID.
	 * @param  string $path               The attachment path.
	 * @param  string $url                The attachment URL.
	 * @param  string $size_key           The attachment size key.
	 * @param  bool   $optimization_level The optimization level.
	 * @return array  $data               The new optimization data.
	 */
	function _imagify_optimize_wr2x( $data, $response, $id, $path, $url, $size_key, $optimization_level ) {
		_deprecated_function( __FUNCTION__ . '()', '1.8', 'Imagify_WP_Retina_2x::optimize_retina_version()' );

		/**
		 * Allow to optimize the retina version generated by WP Retina x2.
		 *
		 * @since 1.0
		 *
		 * @param bool $do_retina True will force the optimization.
		 */
		$do_retina   = apply_filters( 'do_imagify_optimize_retina', true );
		$retina_path = wr2x_get_retina( $path );

		if ( empty( $retina_path ) || ! $do_retina ) {
			return $data;
		}

		$response = do_imagify( $retina_path, array(
			'backup'             => false,
			'optimization_level' => $optimization_level,
			'context'            => 'wp-retina',
		) );
		$attachment = get_imagify_attachment( 'wp', $id, 'imagify_fill_thumbnail_data' );

		return $attachment->fill_data( $data, $response, $size_key . '@2x' );
	}

endif;

/**
 * Combine two arrays with some specific keys.
 * We use this function to combine the result of 2 SQL queries.
 *
 * @since 1.4.5
 * @since 1.6.7  Added the $keep_keys_order parameter.
 * @since 1.6.13 Deprecated.
 * @deprecated
 *
 * @param  array $keys            An array of keys.
 * @param  array $values          An array of arrays like array( 'id' => id, 'value' => value ).
 * @param  int   $keep_keys_order Set to true to return an array ordered like $keys instead of $values.
 * @return array                  The combined arrays.
 */
function imagify_query_results_combine( $keys, $values, $keep_keys_order = false ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.13', 'Imagify_DB::combine_query_results( $keys, $values, $keep_keys_order )' );

	return Imagify_DB::combine_query_results( $keys, $values, $keep_keys_order );
}

/**
 * A helper to retrieve all values from one or several post metas, given a list of post IDs.
 * The $wpdb cache is flushed to save memory.
 *
 * @since  1.6.7
 * @since  1.6.13 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  array $metas An array of meta names like:
 *                      array(
 *                          'key1' => 'meta_name_1',
 *                          'key2' => 'meta_name_2',
 *                          'key3' => 'meta_name_3',
 *                      )
 *                      If a key contains 'data', the results will be unserialized.
 * @param  array $ids   An array of post IDs.
 * @return array        An array of arrays of results like:
 *                      array(
 *                          'key1' => array( post_id_1 => 'result_1', post_id_2 => 'result_2', post_id_3 => 'result_3' ),
 *                          'key2' => array( post_id_1 => 'result_4', post_id_3 => 'result_5' ),
 *                          'key3' => array( post_id_1 => 'result_6', post_id_2 => 'result_7' ),
 *                      )
 */
function imagify_get_wpdb_metas( $metas, $ids ) {
	_deprecated_function( __FUNCTION__ . '()', '1.6.13', 'Imagify_DB::get_metas( $metas, $ids )' );

	return Imagify_DB::get_metas( $metas, $ids );
}

/**
 * Get all mime types which could be optimized by Imagify.
 *
 * @since 1.3
 * @since 1.7 Deprecated.
 * @deprecated
 *
 * @return array $mime_type  The mime type.
 */
function get_imagify_mime_type() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'imagify_get_mime_types()' );

	return imagify_get_mime_types();
}

/**
 * Planning cron.
 * If the task is not programmed, it is automatically triggered.
 *
 * @since 1.4.2
 * @since 1.7 Deprecated.
 * @deprecated
 */
function _imagify_rating_scheduled() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Rating::get_instance()->schedule_event()' );

	Imagify_Cron_Rating::get_instance()->schedule_event();
}

/**
 * Save the user images count to display it later in a notice message to ask him to rate Imagify on WordPress.org.
 *
 * @since 1.4.2
 * @since 1.7 Deprecated.
 * @deprecated
 */
function _do_imagify_rating_cron() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Rating::get_instance()->do_event()' );

	Imagify_Cron_Rating::get_instance()->do_event();
}

/**
 * Adds weekly interval for cron jobs.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 *
 * @param  Array $schedules An array of intervals used by cron jobs.
 * @return Array Updated array of intervals.
 */
function imagify_purge_cron_schedule( $schedules ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->maybe_add_recurrence( $schedules )' );

	return Imagify_Cron_Library_Size::get_instance()->do_event( $schedules );
}

/**
 * Planning cron task to update weekly the size of the images and the size of images uploaded by month.
 * If the task is not programmed, it is automatically triggered.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 */
function _imagify_update_library_size_calculations_scheduled() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->schedule_event()' );

	Imagify_Cron_Library_Size::get_instance()->schedule_event();
}

/**
 * Cron task to update weekly the size of the images and the size of images uploaded by month.
 *
 * @since  1.6
 * @since  1.7 Deprecated.
 * @author Remy Perona
 * @deprecated
 */
function _do_imagify_update_library_size_calculations() {
	_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Cron_Library_Size::get_instance()->do_event()' );

	Imagify_Cron_Library_Size::get_instance()->do_event();
}

/**
 * Set a file permissions using FS_CHMOD_FILE.
 *
 * @since 1.2
 * @since 1.6.5 Use WP Filesystem.
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @param  string $file_path Path to the file.
 * @return bool              True on success, false on failure.
 */
function imagify_chmod_file( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->chmod_file( $file_path )' );

	return imagify_get_filesystem()->chmod_file( $file_path );
}

/**
 * Get a file mime type.
 *
 * @since  1.6.9
 * @since  1.7 Doesn't use exif_imagetype() nor getimagesize() anymore.
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path A file path (prefered) or a filename.
 * @return string|bool       A mime type. False on failure: the test is limited to mime types supported by Imagify.
 */
function imagify_get_mime_type_from_file( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_mime_type( $file_path )' );

	return imagify_get_filesystem()->get_mime_type( $file_path );
}

/**
 * Get a file modification date, formated as "mysql". Fallback to current date.
 *
 * @since  1.7
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path The file path.
 * @return string            The date.
 */
function imagify_get_file_date( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_date( $file_path )' );

	return imagify_get_filesystem()->get_date( $file_path );
}

/**
 * Get a clean value of ABSPATH that can be used in string replacements.
 *
 * @since  1.6.8
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @return string The path to WordPress' root folder.
 */
function imagify_get_abspath() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->get_abspath()' );

	return imagify_get_filesystem()->get_abspath();
}

/**
 * Make an absolute path relative to WordPress' root folder.
 * Also works for files from registered symlinked plugins.
 *
 * @since  1.6.10
 * @since  1.7 The parameter $base is added.
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @param  string $base      A base path to use instead of ABSPATH.
 * @return string|bool       A relative path. Can return the absolute path or false in case of a failure.
 */
function imagify_make_file_path_relative( $file_path, $base = '' ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->make_path_relative( $file_path, $base )' );

	return imagify_get_filesystem()->make_path_relative( $file_path, $base );
}

/**
 * Tell if a file is symlinked.
 *
 * @since  1.7
 * @since  1.7.1 Deprecated.
 * @author Grégory Viguier
 * @deprecated
 *
 * @param  string $file_path An absolute path.
 * @return bool
 */
function imagify_file_is_symlinked( $file_path ) {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'imagify_get_filesystem()->is_symlinked( $file_path )' );

	return imagify_get_filesystem()->is_symlinked( $file_path );
}

/**
 * Determine if the Imagify API key is valid.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if the API key is valid.
 */
function imagify_valid_key() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_api_key_valid()' );

	return Imagify_Requirements::is_api_key_valid();
}

/**
 * Check if external requests are blocked for Imagify.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if Imagify API can't be called.
 */
function is_imagify_blocked() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_imagify_blocked()' );

	return Imagify_Requirements::is_imagify_blocked();
}

/**
 * Determine if the Imagify API is available by checking the API version.
 *
 * @since 1.0
 * @since 1.7.1 Deprecated.
 * @deprecated
 *
 * @return bool True if the Imagify API is available.
 */
function is_imagify_servers_up() {
	_deprecated_function( __FUNCTION__ . '()', '1.7.1', 'Imagify_Requirements::is_api_up()' );

	return Imagify_Requirements::is_api_up();
}

/**
 * Auto-optimize when a new attachment is generated.
 *
 * @since 1.0
 * @since 1.5 Async job.
 * @since 1.8.4 Deprecated
 * @see   Imagify_Admin_Ajax_Post_Deprecated::imagify_async_optimize_upload_new_media_callback()
 * @deprecated
 *
 * @param  array $metadata      An array of attachment meta data.
 * @param  int   $attachment_id Current attachment ID.
 * @return array
 */
function _imagify_optimize_attachment( $metadata, $attachment_id ) {
	_deprecated_function( __FUNCTION__ . '()', '1.8.4', 'Imagify_Auto_Optimization::get_instance()->store_upload_ids()' );

	if ( ! Imagify_Requirements::is_api_key_valid() || ! get_imagify_option( 'auto_optimize' ) ) {
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

	$context     = 'wp';
	$action      = 'imagify_async_optimize_upload_new_media';
	$_ajax_nonce = wp_create_nonce( 'new_media-' . $attachment_id );

	imagify_do_async_job( compact( 'action', '_ajax_nonce', 'metadata', 'attachment_id', 'context' ) );

	return $metadata;
}

/**
 * Optimize an attachment after being resized.
 *
 * @since 1.3.6
 * @since 1.4 Async job.
 * @since 1.8.4 Deprecated
 * @deprecated
 */
function _imagify_optimize_save_image_editor_file() {
	_deprecated_function( __FUNCTION__ . '()', '1.8.4' );

	if ( ! isset( $_POST['action'], $_POST['do'], $_POST['postid'] ) || 'image-editor' !== $_POST['action'] || 'open' === $_POST['do'] ) { // WPCS: CSRF ok.
		return;
	}

	$attachment_id = absint( $_POST['postid'] );

	if ( ! $attachment_id || ! Imagify_Requirements::is_api_key_valid() ) {
		return;
	}

	check_ajax_referer( 'image_editor-' . $attachment_id );

	$attachment = get_imagify_attachment( 'wp', $attachment_id, 'save_image_editor_file' );

	if ( ! $attachment->get_data() ) {
		return;
	}

	$body           = $_POST;
	$body['action'] = 'imagify_async_optimize_save_image_editor_file';

	imagify_do_async_job( $body );
}

if ( is_admin() ) :

	/**
	 * Add some CSS on the whole administration.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 */
	function _imagify_admin_print_styles() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->enqueue_styles_and_scripts()' );

		Imagify_Assets::get_instance()->enqueue_styles_and_scripts();
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 */
	function _imagify_admin_print_intercom() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->print_support_script()' );

		Imagify_Assets::get_instance()->print_support_script();
	}

	/**
	 * Add Intercom on Options page an Bulk Optimization
	 *
	 * @since  1.5
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_ngg_admin_print_intercom() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Assets::get_instance()->print_support_script()' );

		$current_screen = get_current_screen();

		if ( isset( $current_screen ) && false !== strpos( $current_screen->base, '_page_imagify-ngg-bulk-optimization' ) ) {
			Imagify_Assets::get_instance()->print_support_script();
		}
	}

	/**
	 * A helper to deprecate old admin notice functions.
	 *
	 * @since  1.6.10
	 * @author Grégory Viguier
	 * @see    Imagify_Notices::notices()
	 * @deprecated
	 *
	 * @param string $function  The function to deprecate.
	 * @param string $notice_id The notice to deprecate.
	 */
	function _imagify_deprecate_old_notice( $function, $notice_id ) {
		_deprecated_function( $function . '()', '1.6.10' );

		$notices  = Imagify_Notices::get_instance();
		$callback = 'display_' . str_replace( '-', '_', $notice_id );
		$data     = method_exists( $notices, $callback ) ? call_user_func( array( $notices, $callback ) ) : false;

		if ( $data ) {
			Imagify_Views::get_instance()->print_template( 'notice-' . $notice_id, $data );
		}
	}

	/**
	 * This warning is displayed when the API key is empty.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_empty_api_key_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'welcome-steps' );
	}

	/**
	 * This warning is displayed when the API key is empty.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_wrong_api_key_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'wrong-api-key' );
	}

	/**
	 * This warning is displayed when some plugins may conflict with Imagify.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_plugins_to_deactivate_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'plugins-to-deactivate' );
	}

	/**
	 * This notice is displayed when external HTTP requests are blocked via the WP_HTTP_BLOCK_EXTERNAL constant.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_http_block_external_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'http-block-external' );
	}

	/**
	 * This warning is displayed when the grid view is active on the library.
	 *
	 * @since  1.0.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_grid_view_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'grid-view' );
	}

	/**
	 * This warning is displayed to warn the user that its quota is consumed for the current month.
	 *
	 * @since  1.1.1
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_warning_over_quota_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'over-quota' );
	}

	/**
	 * This warning is displayed if the backup folder is not writable.
	 *
	 * @since  1.6.8
	 * @since  1.6.10 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	function _imagify_warning_backup_folder_not_writable_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'backup-folder-not-writable' );
	}

	/**
	 * Add a message about WP Rocket on the "Bulk Optimization" screen.
	 *
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_rocket_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'rocket' );
	}

	/**
	 * This notice is displayed to rate the plugin after 100 optimization & 7 days after the first installation.
	 *
	 * @since  1.4.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_rating_notice() {
		_imagify_deprecate_old_notice( __FUNCTION__, 'rating' );
	}

	/**
	 * Stop the rating cron when the notice is dismissed.
	 *
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param string $notice The notice name.
	 */
	function _imagify_clear_scheduled_rating( $notice ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->clear_scheduled_rating( $notice )' );

		Imagify_Notices::get_instance()->clear_scheduled_rating( $notice );
	}

	/**
	 * Process a dismissed notice.
	 *
	 * @since  1.0
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _do_admin_post_imagify_dismiss_notice() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->admin_post_dismiss_notice()' );

		Imagify_Notices::get_instance()->admin_post_dismiss_notice();
	}

	/**
	 * Disable a plugin which can be in conflict with Imagify
	 *
	 * @since  1.2
	 * @since  1.6.10 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_deactivate_plugin() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::get_instance()->deactivate_plugin()' );

		Imagify_Notices::get_instance()->deactivate_plugin();
	}

	/**
	 * Renew a dismissed Imagify notice.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return void
	 */
	function imagify_renew_notice( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::renew_notice( $notice, $user_id )' );

		Imagify_Notices::renew_notice( $notice, $user_id );
	}

	/**
	 * Dismiss an Imagify notice.
	 *
	 * @since 1.0
	 * @since 1.6.10 Deprecated.
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return void
	 */
	function imagify_dismiss_notice( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::dismiss_notice( $notice, $user_id )' );

		Imagify_Notices::dismiss_notice( $notice, $user_id );
	}

	/**
	 * Tell if an Imagify notice is dismissed.
	 *
	 * @since  1.6.5
	 * @since  1.6.10 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  string $notice  A notice ID.
	 * @param  int    $user_id A user ID.
	 * @return bool
	 */
	function imagify_notice_is_dismissed( $notice, $user_id = 0 ) {
		_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'Imagify_Notices::notice_is_dismissed( $notice, $user_id )' );

		return Imagify_Notices::notice_is_dismissed( $notice, $user_id );
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with the manual method.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_manual_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_upload_callback();
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with a different optimization level.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_manual_override_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_manual_override_upload_callback();
	}

	/**
	 * Process one or some thumbnails that are not optimized yet.
	 *
	 * @since  1.6.10
	 * @since  1.6.11 Deprecated.
	 * @author Grégory Viguier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_optimize_missing_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_optimize_missing_sizes_callback();
	}

	/**
	 * Process a restoration to the original attachment.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback()
	 * @deprecated
	 */
	function _do_admin_post_imagify_restore_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_restore_upload_callback();
	}

	/**
	 * Process all thumbnails of a specific image with Imagify with the bulk method.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_bulk_upload() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_bulk_upload_callback();
	}

	/**
	 * Optimize image on picture uploading with async request.
	 *
	 * @since  1.5
	 * @since  1.6.11 Deprecated.
	 * @author Julio Potier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback()
	 * @deprecated
	 */
	function _do_admin_post_async_optimize_upload_new_media() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_upload_new_media_callback();
	}

	/**
	 * Optimize image on picture editing (resize, crop...) with async request.
	 *
	 * @since  1.4
	 * @since  1.6.11 Deprecated.
	 * @author Julio Potier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback()
	 * @deprecated
	 */
	function _do_admin_post_async_optimize_save_image_editor_file() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_async_optimize_save_image_editor_file_callback();
	}

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_get_unoptimized_attachment_ids() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_unoptimized_attachment_ids_callback();
	}

	/**
	 * Check if the backup directory is writable.
	 * This is used to display an error message in the plugin's settings page.
	 *
	 * @since  1.6.8
	 * @since  1.6.11 Deprecated.
	 * @author Grégory Viguier
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_check_backup_dir_is_writable() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_backup_dir_is_writable_callback();
	}

	/**
	 * Create a new Imagify account.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_signup() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_signup_callback();
	}

	/**
	 * Process an API key check validity.
	 *
	 * @since  1.0
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_check_api_key_validity() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_api_key_validity_callback();
	}

	/**
	 * Get admin bar profile output.
	 *
	 * @since  1.2.3
	 * @since  1.6.11 Deprecated.
	 * @author Jonathan Buttigieg
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback()
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_get_admin_bar_profile() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_admin_bar_profile_callback();
	}

	/**
	 * Get pricings from API for Onetime and Plans at the same time.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback()
	 * @deprecated
	 */
	function _imagify_get_prices_from_api() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_prices_callback();
	}

	/**
	 * Check Coupon code on modal popin.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback()
	 * @deprecated
	 */
	function _imagify_check_coupon_code() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_check_coupon_callback();
	}

	/**
	 * Get current discount promotion to display information on payment modal.
	 *
	 * @since  1.6.3
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback()
	 * @deprecated
	 */
	function _imagify_get_discount() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_discount_callback();
	}

	/**
	 * Get estimated sizes from the WordPress library.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Geoffrey Crofte
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback()
	 * @deprecated
	 */
	function _imagify_get_estimated_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_get_images_counts_callback();
	}

	/**
	 * Estimate sizes and update the options values for them.
	 *
	 * @since  1.6
	 * @since  1.6.11 Deprecated.
	 * @author Remy Perona
	 * @see    Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback()
	 * @deprecated
	 */
	function _imagify_update_estimate_sizes() {
		_deprecated_function( __FUNCTION__ . '()', '1.6.11', 'Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback()' );

		Imagify_Admin_Ajax_Post::get_instance()->imagify_update_estimate_sizes_callback();
	}

	/**
	 * Fix the capability for our capacity filter hook
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 */
	function _imagify_correct_capability_for_options_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->get_capability()' );

		return Imagify_Settings::get_instance()->get_capability();
	}

	/**
	 * Tell to WordPress to be confident with our setting, we are clean!
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 */
	function _imagify_register_setting() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->register()' );

		Imagify_Settings::get_instance()->register();
	}

	/**
	 * Filter specific options before its value is (maybe) serialized and updated.
	 *
	 * @since  1.0
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 *
	 * @param  mixed $value     The new option value.
	 * @param  mixed $old_value The old option value.
	 * @return array The new option value.
	 */
	function _imagify_pre_update_option( $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->sanitize_and_validate( $value )' );

		return Imagify_Settings::get_instance()->sanitize_and_validate( $value );
	}

	/**
	 * If the user clicked the "Save & Go to Bulk Optimizer" button, set a redirection to the bulk optimizer.
	 * We use this hook because it can be triggered even if the option value hasn't changed.
	 *
	 * @since  1.6.8
	 * @since  1.7 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  mixed $value     The new, unserialized option value.
	 * @param  mixed $old_value The old option value.
	 * @return mixed            The option value.
	 */
	function _imagify_maybe_set_redirection_before_save_options( $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->maybe_set_redirection( $value, $old_value )' );

		return Imagify_Settings::get_instance()->maybe_set_redirection( $value, $old_value );
	}

	/**
	 * Used to launch some actions after saving the network options.
	 *
	 * @since  1.6.5
	 * @since  1.7 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $option     Name of the network option.
	 * @param mixed  $value      Current value of the network option.
	 * @param mixed  $old_value  Old value of the network option.
	 */
	function _imagify_after_save_network_options( $option, $value, $old_value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->after_save_network_options( $option, $value, $old_value )' );

		Imagify_Settings::get_instance()->after_save_network_options( $option, $value, $old_value );
	}

	/**
	 * Used to launch some actions after saving the options.
	 *
	 * @since  1.0
	 * @since  1.5    Used to redirect user to Bulk Optimizer (if requested).
	 * @since  1.6.8  Not used to redirect user to Bulk Optimizer anymore: see _imagify_maybe_set_redirection_before_save_options().
	 * @since  1.7 Deprecated.
	 * @author Jonathan
	 * @deprecated
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 */
	function _imagify_after_save_options( $old_value, $value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->after_save_options( $old_value, $value )' );

		Imagify_Settings::get_instance()->after_save_options( $old_value, $value );
	}

	/**
	 * `options.php` do not handle site options. Let's use `admin-post.php` for multisite installations.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_update_site_option_on_network() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Settings::get_instance()->update_site_option_on_network()' );

		Imagify_Settings::get_instance()->update_site_option_on_network();
	}

	/**
	 * Display the plan chooser section.
	 *
	 * @since  1.6
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 *
	 * @return string HTML.
	 */
	function get_imagify_new_to_imagify() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'imagify_get_template( \'part-new-to-imagify\' )' );

		return imagify_get_template( 'part-new-to-imagify' );
	}

	/**
	 * Get the payment modal HTML.
	 *
	 * @since  1.6
	 * @since  1.6.3 Include discount banners.
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 */
	function imagify_payment_modal() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'modal-payment\' )' );

		Imagify_Views::get_instance()->print_template( 'modal-payment' );
	}

	/**
	 * Print the discount banner used inside Payment Modal.
	 *
	 * @since  1.6.3
	 * @since  1.7 Deprecated.
	 * @author Geoffrey Crofte
	 * @deprecated
	 */
	function imagify_print_discount_banner() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->print_template( \'part-discount-banner\' )' );

		Imagify_Views::get_instance()->print_template( 'part-discount-banner' );
	}

	/**
	 * Return the formatted price present in pricing tables.
	 *
	 * @since  1.6
	 * @since  1.7 Deprecated.
	 * @author Geoffrey
	 * @deprecated
	 *
	 * @param  float $value The price value.
	 * @return string       The markuped price.
	 */
	function get_imagify_price_table_format( $value ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7' );

		$v = explode( '.', (string) $value );

		return '<span class="imagify-price-big">' . $v[0] . '</span> <span class="imagify-price-mini">.' . ( strlen( $v[1] ) === 1 ? $v[1] . '0' : $v[1] ) . '</span>';
	}

	/**
	 * Add submenu in menu "Settings".
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_settings_menu() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->add_network_menus()' );

		Imagify_Views::get_instance()->add_network_menus();
	}

	/**
	 * Add submenu in menu "Media".
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_bulk_optimization_menu() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->add_site_menus()' );

		Imagify_Views::get_instance()->add_site_menus();
	}

	/**
	 * The main settings page.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_display_options_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->display_settings_page()' );

		Imagify_Views::get_instance()->display_settings_page();
	}

	/**
	 * The bulk optimization page.
	 *
	 * @since 1.0
	 * @since 1.7 Deprecated.
	 * @deprecated
	 */
	function _imagify_display_bulk_page() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->display_bulk_page()' );

		Imagify_Views::get_instance()->display_bulk_page();
	}

	/**
	 * Add link to the plugin configuration pages.
	 *
	 * @since 1.0
	 *
	 * @param  array $actions An array of action links.
	 * @return array
	 */
	function _imagify_plugin_action_links( $actions ) {
		_deprecated_function( __FUNCTION__ . '()', '1.7', 'Imagify_Views::get_instance()->plugin_action_links( $actions )' );

		return Imagify_Views::get_instance()->plugin_action_links( $actions );
	}

endif;
