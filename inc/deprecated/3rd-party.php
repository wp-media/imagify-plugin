<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

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
