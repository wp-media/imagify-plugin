<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Deprecated imagify.io API for WordPress.
 *
 * @since 1.6.5
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
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.6.5', 'Imagify::get_instance()' );
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
	 * Get prices for packs (one time).
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

		$attachment_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $guid ) );

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
	_deprecated_function( __FUNCTION__ . '()', '1.6.10', 'imagify_make_file_path_relative( $file_path )' );

	return imagify_make_file_path_relative( $file_path );
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

endif;

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
			$this->render_view( str_replace( '_', '-', $notice_id ), $data );
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

endif;
