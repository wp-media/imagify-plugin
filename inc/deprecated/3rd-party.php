<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( class_exists( 'C_NextGEN_Bootstrap' ) && class_exists( 'Mixin' ) && get_site_option( 'ngg_options' ) ) :

	/**
	 * Create the Imagify table needed for NGG compatibility.
	 *
	 * @since  1.5
	 * @since  1.7 Deprecated.
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _imagify_create_ngg_table() {
		_deprecated_function( __FUNCTION__ . '()', '1.7', '\\Imagify\\ThirdParty\\NGG\\DB::get_instance()->maybe_upgrade_table()' );

		\Imagify\ThirdParty\NGG\DB::get_instance()->maybe_upgrade_table();
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

	/**
	 * Filter the current user capability to operate Imagify.
	 *
	 * @since  1.6.11
	 * @since  1.9 Deprecated.
	 * @see    imagify_get_capacity()
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  bool   $user_can  Tell if the current user has the required capacity to operate Imagify.
	 * @param  string $capacity  The user capacity.
	 * @param  string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
	 * @param  int    $post_id   A post ID (a gallery ID for NGG).
	 * @return bool
	 */
	function imagify_ngg_current_user_can( $user_can, $capacity, $describer, $post_id ) {
		static $user_can_per_gallery = array();

		_deprecated_function( __FUNCTION__ . '()', '1.9' );

		if ( ! $user_can || ! $post_id || 'NextGEN Manage gallery' !== $capacity ) {
			return $user_can;
		}

		$image = nggdb::find_image( $post_id );

		if ( isset( $user_can_per_gallery[ $image->galleryid ] ) ) {
			return $user_can_per_gallery[ $image->galleryid ];
		}

		$gallery_mapper = C_Gallery_Mapper::get_instance();
		$gallery        = $gallery_mapper->find( $image->galleryid, false );

		if ( get_current_user_id() === $gallery->author || current_user_can( 'NextGEN Manage others gallery' ) ) {
			// The user created this gallery or can edit others galleries.
			$user_can_per_gallery[ $image->galleryid ] = true;
			return $user_can_per_gallery[ $image->galleryid ];
		}

		// The user can't edit this gallery.
		$user_can_per_gallery[ $image->galleryid ] = false;
		return $user_can_per_gallery[ $image->galleryid ];
	}

	/**
	 * Get user capacity to operate Imagify within NGG galleries.
	 * It is meant to be used to filter 'imagify_capacity'.
	 *
	 * @since  1.6.11
	 * @since  1.9 Deprecated.
	 * @see    imagify_get_capacity()
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param string $capacity  The user capacity.
	 * @param string $describer Capacity describer. See imagify_get_capacity() for possible values. Can also be a "real" user capacity.
	 * @return string
	 */
	function imagify_get_ngg_capacity( $capacity = 'edit_post', $describer = 'manual-optimize' ) {
		if ( 'manual-optimize' === $describer ) {
			return 'NextGEN Manage gallery';
		}

		return $capacity;
	}

	/**
	 * Dispatch the optimization process.
	 *
	 * @since  1.8
	 * @since  1.9 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	function imagify_ngg_dispatch_dynamic_thumbnail_background_process() {
		_deprecated_function( __FUNCTION__ . '()', '1.9' );

		Imagify_NGG_Dynamic_Thumbnails_Background_Process::get_instance()->save()->dispatch();
	}

	/**
	 * On manual optimization, manual re-optimization, and manual restoration, filter the user capacity to operate Imagify within NGG.
	 *
	 * @since  1.6.11
	 * @since  1.9 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	function _do_admin_post_imagify_ngg_user_capacity() {
		_deprecated_function( __FUNCTION__ . '()', '1.9' );

		if ( ! empty( $_GET['context'] ) && 'NGG' === $_GET['context'] ) { // WPCS: CSRF ok.
			add_filter( 'imagify_capacity', 'imagify_get_ngg_capacity', 10, 2 );
		}
	}

	/**
	 * Get all unoptimized attachment ids.
	 *
	 * @since  1.0
	 * @since  1.9 Deprecated
	 * @author Jonathan Buttigieg
	 * @deprecated
	 */
	function _do_wp_ajax_imagify_ngg_get_unoptimized_attachment_ids() {
		_deprecated_function( __FUNCTION__ . '()', '1.9', '\\Imagify\\ThirdParty\\NGG\\AdminAjaxPost::get_instance()->get_media_ids()' );

		\Imagify\ThirdParty\NGG\AdminAjaxPost::get_instance()->get_media_ids();
	}

	/**
	 * Provide custom folder type data.
	 *
	 * @since  1.7
	 * @since  1.9 Deprecated
	 * @author Grégory Viguier
	 * @deprecated
	 *
	 * @param  array  $data    An array with keys corresponding to cell classes, and values formatted with HTML.
	 * @param  string $context A context.
	 * @return array
	 */
	function imagify_ngg_get_folder_type_data( $data, $context ) {
		_deprecated_function( __FUNCTION__ . '()', '1.9' );

		if ( 'ngg' !== $context ) {
			return $data;
		}

		// Already filtered in imagify_ngg_bulk_page_data().
		$total_saving_data = imagify_count_saving_data();

		return [
			'images-optimized' => imagify_ngg_count_optimized_attachments(),
			'errors'           => imagify_ngg_count_error_attachments(),
			'optimized'        => $total_saving_data['optimized_size'],
			'original'         => $total_saving_data['original_size'],
			'errors_url'       => admin_url( 'admin.php?page=nggallery-manage-gallery' ),
		];
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

if ( defined( 'WP_ROCKET_VERSION' ) ) :

	/**
	 * Don't load Imagify CSS & JS files on WP Rocket options screen to avoid conflict with older version of SweetAlert.
	 * Since 1.6.10 they should be enqueued only if one of our notices displays here.
	 *
	 * @since  1.6.9.1
	 * @since  1.6.10 Use the new class Imagify_Assets.
	 * @since  1.9.3  Deprecated.
	 * @author Jonathan Buttigieg
	 * @author Grégory Viguier
	 * @deprecated
	 */
	function imagify_dequeue_sweetalert_wprocket() {
		_deprecated_function( __FUNCTION__ . '()', '1.9.3', '\\Imagify\\ThirdParty\\WPRocket\\Main::dequeue_sweetalert()' );

		if ( ! defined( 'WP_ROCKET_PLUGIN_SLUG' ) ) {
			return;
		}

		if ( ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG ) && ! imagify_is_screen( 'settings_page_' . WP_ROCKET_PLUGIN_SLUG . '-network' ) ) {
			return;
		}

		Imagify_Assets::get_instance()->dequeue_script( array( 'sweetalert-core', 'sweetalert', 'notices' ) );
	}

endif;
