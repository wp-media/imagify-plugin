<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Get all translations we can use with wp_localize_script().
 *
 * @since  1.5
 * @author Jonathan Buttigieg
 *
 * @param  string $context       The translation context.
 * @return array  $translations  The translations.
 */
function get_imagify_localize_script_translations( $context ) {
	global $post_id;

	switch ( $context ) {
		case 'admin-bar':
			if ( is_admin() ) {
				return array();
			}

			return array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			);

		case 'notices':
			return array(
				'labels' => array(
					/* translators: Don't use escaped HTML entities here (like &nbsp;). */
					'signupTitle'                 => __( 'Let\'s get you started!', 'imagify' ),
					'signupText'                  => __( 'Enter your email to get an API key:', 'imagify' ),
					'signupConfirmButtonText'     => __( 'Sign Up', 'imagify' ),
					'signupErrorEmptyEmail'       => __( 'You need to specify an email!', 'imagify' ),
					/* translators: Don't use escaped HTML entities here (like &nbsp;). */
					'signupSuccessTitle'          => __( 'Congratulations!', 'imagify' ),
					'signupSuccessText'           => __( 'Your account has been successfully created. Please check your mailbox, you are going to receive an email with API key.', 'imagify' ),
					/* translators: Don't use escaped HTML entities here (like &nbsp;). */
					'saveApiKeyTitle'             => __( 'Connect to Imagify!', 'imagify' ),
					'saveApiKeyText'              => __( 'Paste your API key below:', 'imagify' ),
					'saveApiKeyConfirmButtonText' => __( 'Connect me', 'imagify' ),
					'ApiKeyErrorEmpty'            => __( 'You need to specify your api key!', 'imagify' ),
					'ApiKeyCheckSuccessTitle'     => __( 'Congratulations!', 'imagify' ),
					'ApiKeyCheckSuccessText'      => __( 'Your API key is valid. You can now configure the Imagify settings to optimize your images.', 'imagify' ),
				),
			);

		case 'sweetalert':
			return array(
				'labels' => array(
					'cancelButtonText' => __( 'Cancel' ),
				),
			);

		case 'options':
			return array(
				'getFilesTree' => imagify_can_optimize_custom_folders() ? get_imagify_admin_url( 'get-files-tree' ) : false,
				'labels'       => array(
					'ValidApiKeyText'         => __( 'Your API key is valid.', 'imagify' ),
					'waitApiKeyCheckText'     => __( 'Check in progress...', 'imagify' ),
					'ApiKeyCheckSuccessTitle' => __( 'Congratulations!', 'imagify' ),
					'ApiKeyCheckSuccessText'  => __( 'Your API key is valid. You can now configure the Imagify settings to optimize your images.', 'imagify' ),
					'noBackupTitle'           => __( 'Don\'t Need a Parachute?', 'imagify' ),
					'noBackupText'            => __( 'If you keep this option deactivated, you won\'t be able to re-optimize your images to another compression level and restore your original images in case of need.', 'imagify' ),
					'removeFolder'            => _x( 'Remove', 'custom folder', 'imagify' ),
					'filesTreeTitle'          => __( 'Select Folders', 'imagify' ),
					'filesTreeSubTitle'       => __( 'Select one or several folders to optimize.', 'imagify' ),
					'cleaningInfo'            => __( 'Some folders that do not contain any images are hidden.', 'imagify' ),
					'confirmFilesTreeBtn'     => __( 'Select Folders', 'imagify' ),
					'customFilesLegend'       => __( 'Choose the folders to optimize', 'imagify' ),
					'error'                   => __( 'Error', 'imagify' ),
					'themesAdded'             => __( 'Added! All Good!', 'imagify' ),
				),
			);

		case 'pricing-modal':
			return array(
				'labels' => array(
					'errorCouponAPI'   => __( 'Error with checking this coupon.', 'imagify' ),
					/* translators: 1 is a percentage, 2 is a coupon code. */
					'successCouponAPI' => sprintf( _x( '%1$s off with %2$s', 'coupon validated', 'imagify' ), '<span class="imagify-coupon-offer"></span>', '<strong class="imagify-coupon-word"></strong>' ),
					'errorPriceAPI'    => __( 'Something went wrong with getting our updated offers. Please retry later.', 'imagify' ),
				),
			);

		case 'twentytwenty':
			$image = array( '', 0, 0 );

			if ( imagify_is_screen( 'attachment' ) && wp_attachment_is_image( $post_id ) ) {
				$attachment = get_imagify_attachment( 'wp', $post_id, 'imagify_localize_script_translations' );

				if ( $attachment->is_image() ) {
					$image = wp_get_attachment_image_src( $post_id, 'full' );
					$image = $image && is_array( $image ) ? $image : array( '', 0, 0 );
				}
			}

			return array(
				'imageSrc'    => $image[0],
				'imageWidth'  => $image[1],
				'imageHeight' => $image[2],
				'widthLimit'  => 360, // See _imagify_add_actions_to_media_list_row().
				'labels'      => array(
					'filesize'   => __( 'File Size:', 'imagify' ),
					'saving'     => __( 'Original Saving:', 'imagify' ),
					'close'      => __( 'Close', 'imagify' ),
					'originalL'  => __( 'Original File', 'imagify' ),
					'optimizedL' => __( 'Optimized File', 'imagify' ),
					'compare'    => __( 'Compare Original VS Optimized', 'imagify' ),
					'optimize'   => __( 'Optimize', 'imagify' ),
				),
			);

		case 'library':
			return array(
				'backupOption' => get_imagify_option( 'backup' ),
				'labels'       => array(
					'bulkActionsOptimize'             => __( 'Optimize', 'imagify' ),
					'bulkActionsOptimizeMissingSizes' => __( 'Optimize Missing Sizes', 'imagify' ),
					'bulkActionsRestore'              => __( 'Restore Original', 'imagify' ),
				),
			);

		case 'bulk':
			$translations = array(
				'curlMissing'     => ! Imagify_Requirements::supports_curl(),
				'editorMissing'   => ! Imagify_Requirements::supports_image_editor(),
				'extHttpBlocked'  => Imagify_Requirements::is_imagify_blocked(),
				'apiDown'         => Imagify_Requirements::is_imagify_blocked() || ! Imagify_Requirements::is_api_up(),
				'keyIsValid'      => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid(),
				'isOverQuota'     => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid() && Imagify_Requirements::is_over_quota(),
				'heartbeatId'     => 'update_bulk_data',
				'reqsHeartbeatId' => 'update_bulk_requirements',
				'waitImageUrl'    => IMAGIFY_ASSETS_IMG_URL . 'popin-loader.svg',
				'ajaxActions'     => array(
					'libraryFetch'          => 'imagify_get_unoptimized_attachment_ids',
					'customFoldersFetch'    => 'imagify_get_unoptimized_file_ids',
					'libraryOptimize'       => 'imagify_bulk_upload',
					'customFoldersOptimize' => 'imagify_bulk_optimize_file',
					'getFolderData'         => 'imagify_get_folder_type_data',
					'bulkInfoSeen'          => 'imagify_bulk_info_seen',
				),
				'ajaxNonce'       => wp_create_nonce( 'imagify-bulk-upload' ),
				'bufferSizes'     => array(
					'wp'   => get_imagify_bulk_buffer_size(),
					'File' => get_imagify_bulk_buffer_size( 1 ),
				),
				'labels'          => array(
					'overviewChartLabels'            => array(
						'unoptimized' => __( 'Unoptimized', 'imagify' ),
						'optimized'   => __( 'Optimized', 'imagify' ),
						'error'       => __( 'Error', 'imagify' ),
					),
					'curlMissing'                    => __( 'cURL is not available on the server.', 'imagify' ),
					'editorMissing'                  => sprintf(
						/* translators: %s is a "More info?" link. */
						__( 'No php extensions are available to edit images on the server. ImageMagick or GD is required. %s', 'imagify' ),
						'<a href="' . esc_url( imagify_get_external_url( 'documentation-imagick-gd' ) ) . '" target="_blank">' . __( 'More info?', 'imagify' ) . '</a>'
					),
					'extHttpBlocked'                 => __( 'External HTTP requests are blocked.', 'imagify' ),
					'apiDown'                        => __( 'Sorry, our servers are temporarily unavailable. Please, try again in a couple of minutes.', 'imagify' ),
					'invalidAPIKeyTitle'             => __( 'Your API key is not valid!', 'imagify' ),
					'overQuotaTitle'                 => __( 'You have used all your credits!', 'imagify' ),
					'processing'                     => __( 'Imagify is still processing. Are you sure you want to leave this page?', 'imagify' ),
					'waitTitle'                      => __( 'Please wait...', 'imagify' ),
					'waitText'                       => __( 'We are trying to get your unoptimized media files, it may take time depending on the number of files.', 'imagify' ),
					'noAttachmentToOptimizeTitle'    => __( 'Hold on!', 'imagify' ),
					'noAttachmentToOptimizeText'     => __( 'All your media files have been optimized by Imagify. Congratulations!', 'imagify' ),
					'optimizing'                     => __( 'Optimizing', 'imagify' ),
					'error'                          => __( 'Error', 'imagify' ),
					'ajaxErrorText'                  => __( 'The operation failed.', 'imagify' ),
					'complete'                       => _x( 'Complete', 'adjective', 'imagify' ),
					'alreadyOptimized'               => _x( 'Already Optimized', 'file', 'imagify' ),
					/* translators: %s is a number. Don't use %d. */
					'nbrFiles'                       => __( '%s file(s)', 'imagify' ),
					'notice'                         => _x( 'Notice', 'noun', 'imagify' ),
					/* translators: %s is a number. Don't use %d. */
					'nbrErrors'                      => __( '%s error(s)', 'imagify' ),
					/* translators: 1 and 2 are file sizes. Don't use HTML entities here (like &nbsp;). */
					'textToShare'                    => __( 'Discover @imagify, the new compression tool to optimize your images for free. I saved %1$s out of %2$s!', 'imagify' ),
					'twitterShareURL'                => imagify_get_external_url( 'share-twitter' ),
					'getUnoptimizedImagesErrorTitle' => __( 'Oops, There is something wrong!', 'imagify' ),
					'getUnoptimizedImagesErrorText'  => __( 'An unknown error occurred when we tried to get all your unoptimized media files. Try again and if the issue still persists, please contact us!', 'imagify' ),
					'waitingOtimizationsText'        => __( 'Waiting other optimizations to finish.', 'imagify' ),
					/* translators: %s is a formatted number, dont use %d. */
					'imagesOptimizedText'            => __( '%s Media File(s) Optimized', 'imagify' ),
					/* translators: %s is a formatted number, dont use %d. */
					'imagesErrorText'                => __( '%s Error(s)', 'imagify' ),
					'bulkInfoTitle'                  => __( 'Information', 'imagify' ),
					'confirmBulk'                    => __( 'Start the optimization', 'imagify' ),
				),
			);

			if ( get_transient( 'imagify_large_library' ) ) {
				// On huge media libraries, don't use heartbeat, and fetch stats only when the process ends.
				$translations['ajaxActions']['getStats'] = 'imagify_bulk_get_stats';
			}

			if ( isset( $translations['bufferSizes']['wp'] ) ) {
				/**
				 * Filter the number of parallel queries during the Bulk Optimization (library).
				 *
				 * @since 1.5.4
				 * @since 1.7 Deprecated
				 * @deprecated
				 *
				 * @param int $buffer_size Number of parallel queries.
				 */
				$translations['bufferSizes']['wp'] = apply_filters_deprecated( 'imagify_bulk_buffer_size', array( $translations['bufferSizes']['wp'] ), '1.7', 'imagify_bulk_buffer_sizes' );
			}

			/**
			 * Filter the number of parallel queries during the Bulk Optimization.
			 *
			 * @since  1.7
			 * @author Grégory Viguier
			 *
			 * @param array $buffer_sizes An array of number of parallel queries, keyed by context.
			 */
			$translations['bufferSizes'] = apply_filters( 'imagify_bulk_buffer_sizes', $translations['bufferSizes'] );

			return $translations;

		case 'files-list':
			return array(
				'backupOption' => get_imagify_option( 'backup' ),
				'labels'       => array(
					'bulkActionsOptimize' => __( 'Optimize', 'imagify' ),
					'bulkActionsRestore'  => __( 'Restore Original', 'imagify' ),
				),
			);

		default:
			return array();
	} // End switch().
}
