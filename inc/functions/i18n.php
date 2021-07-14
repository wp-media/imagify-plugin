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

	$imagifybeat_actions = \Imagify\Imagifybeat\Actions::get_instance();

	switch ( $context ) {
		case 'admin-bar':
			if ( is_admin() ) {
				return [];
			}

			return [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			];

		case 'notices':
			return [
				'labels' => [
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
				],
			];

		case 'sweetalert':
			return [
				'labels' => [
					'cancelButtonText' => __( 'Cancel' ),
				],
			];

		case 'options':
			$translations = [
				'getFilesTree' => imagify_can_optimize_custom_folders() ? get_imagify_admin_url( 'get-files-tree' ) : false,
				'labels'       => [
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
				],
			];

			if ( \Imagify\Stats\OptimizedMediaWithoutWebp::get_instance()->get_cached_stat() ) {
				$contexts             = imagify_get_context_names();
				$translations['bulk'] = [
					'curlMissing'      => ! Imagify_Requirements::supports_curl(),
					'editorMissing'    => ! Imagify_Requirements::supports_image_editor(),
					'extHttpBlocked'   => Imagify_Requirements::is_imagify_blocked(),
					'apiDown'          => ! Imagify_Requirements::is_api_up(),
					'keyIsValid'       => Imagify_Requirements::is_api_key_valid(),
					'isOverQuota'      => Imagify_Requirements::is_over_quota(),
					'imagifybeatIDs'   => [
						'queue'        => $imagifybeat_actions->get_imagifybeat_id( 'bulk_optimization_status' ),
						'requirements' => $imagifybeat_actions->get_imagifybeat_id( 'requirements' ),
					],
					'ajaxActions'      => [
						'getMediaIds' => 'imagify_get_media_ids',
						'bulkProcess' => 'imagify_bulk_optimize',
					],
					'ajaxNonce'        => wp_create_nonce( 'imagify-bulk-optimize' ),
					'contexts'         => $contexts,
					'labels'           => [
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
						'nothingToDoTitle'               => __( 'Hold on!', 'imagify' ),
						'nothingToDoText'                => __( 'All your optimized images already have a WebP version. Congratulations!', 'imagify' ),
						'nothingToDoNoBackupText'        => __( 'Because the selected images did not have a backup copy, Imagify was unable to create WebP versions.', 'imagify' ),
						'error'                          => __( 'Error', 'imagify' ),
						'ajaxErrorText'                  => __( 'The operation failed.', 'imagify' ),
						'getUnoptimizedImagesErrorTitle' => __( 'Oops, There is something wrong!', 'imagify' ),
						'getUnoptimizedImagesErrorText'  => __( 'An unknown error occurred when we tried to get all your unoptimized media files. Try again and if the issue still persists, please contact us!', 'imagify' ),
					],
				];

				/**
				 * Filter the number of parallel queries generating WebP images by bulk method.
				 *
				 * @since  1.9
				 * @author Grégory Viguier
				 *
				 * @param int $bufferSize Number of parallel queries.
				 */
				$translations['bulk']['bufferSize'] = apply_filters( 'imagify_bulk_generate_webp_buffer_size', 4 );
				$translations['bulk']['bufferSize'] = max( 1, (int) $translations['bulk']['bufferSize'] );
			}

			return $translations;

		case 'pricing-modal':
			$translations = [
				'imagify_app_domain'  => IMAGIFY_APP_DOMAIN,
				'labels' => [
					'errorCouponAPI'   => __( 'Error with checking this coupon.', 'imagify' ),
					/* translators: 1 is a percentage, 2 is a coupon code. */
					'successCouponAPI' => sprintf( _x( '%1$s off with %2$s', 'coupon validated', 'imagify' ), '<span class="imagify-coupon-offer"></span>', '<strong class="imagify-coupon-word"></strong>' ),
					'errorPriceAPI'    => __( 'Something went wrong with getting our updated offers. Please retry later.', 'imagify' ),
					'defaultCouponLabel'    => __( 'If you have a <strong>coupon code</strong><br> use it here:', 'imagify' ),
					'errorCouponPlan'    => __( 'This coupon is not valid with this plan.', 'imagify' ),
				],
			];

			if ( Imagify_Requirements::is_api_key_valid() ) {
				$translations['userDataCache'] = [
					'deleteAction' => 'imagify_delete_user_data_cache',
					'deleteNonce'  => wp_create_nonce( 'imagify_delete_user_data_cache' ),
				];
			}

			return $translations;

		case 'twentytwenty':
			$image = [
				'src'    => '',
				'width'  => 0,
				'height' => 0,
			];

			if ( imagify_is_screen( 'attachment' ) && wp_attachment_is_image( $post_id ) ) {
				$process = imagify_get_optimization_process( $post_id, 'wp' );

				if ( $process->is_valid() ) {
					$media = $process->get_media();

					if ( $media->is_image() ) {
						$dimensions = $media->get_dimensions();
						$image = [
							'src'    => $media->get_fullsize_url(),
							'width'  => $dimensions['width'],
							'height' => $dimensions['height'],
						];
					}
				}
			}

			return [
				'imageSrc'    => $image['src'],
				'imageWidth'  => $image['width'],
				'imageHeight' => $image['height'],
				'widthLimit'  => 360, // See _imagify_add_actions_to_media_list_row().
				'labels'      => [
					'filesize'   => __( 'File Size:', 'imagify' ),
					'saving'     => __( 'Original Saving:', 'imagify' ),
					'close'      => __( 'Close', 'imagify' ),
					'originalL'  => __( 'Original File', 'imagify' ),
					'optimizedL' => __( 'Optimized File', 'imagify' ),
					'compare'    => __( 'Compare Original VS Optimized', 'imagify' ),
					'optimize'   => __( 'Optimize', 'imagify' ),
				],
			];

		case 'beat':
			return \Imagify\Imagifybeat\Core::get_instance()->get_settings();

		case 'media-modal':
			return [
				'imagifybeatID' => $imagifybeat_actions->get_imagifybeat_id( 'library_optimization_status' ),
			];

		case 'library':
			return [
				'backupOption' => get_imagify_option( 'backup' ),
				'labels'       => [
					'bulkActionsOptimize'             => __( 'Optimize', 'imagify' ),
					'bulkActionsOptimizeMissingSizes' => __( 'Optimize Missing Sizes', 'imagify' ),
					'bulkActionsRestore'              => __( 'Restore Original', 'imagify' ),
				],
			];

		case 'bulk':
			$translations = [
				'curlMissing'     => ! Imagify_Requirements::supports_curl(),
				'editorMissing'   => ! Imagify_Requirements::supports_image_editor(),
				'extHttpBlocked'  => Imagify_Requirements::is_imagify_blocked(),
				'apiDown'         => Imagify_Requirements::is_imagify_blocked() || ! Imagify_Requirements::is_api_up(),
				'keyIsValid'      => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid(),
				'isOverQuota'     => ! Imagify_Requirements::is_imagify_blocked() && Imagify_Requirements::is_api_up() && Imagify_Requirements::is_api_key_valid() && Imagify_Requirements::is_over_quota(),
				'imagifybeatIDs'   => [
					'stats'        => $imagifybeat_actions->get_imagifybeat_id( 'bulk_optimization_stats' ),
					'queue'        => $imagifybeat_actions->get_imagifybeat_id( 'bulk_optimization_status' ),
					'requirements' => $imagifybeat_actions->get_imagifybeat_id( 'requirements' ),
				],
				'waitImageUrl'    => IMAGIFY_ASSETS_IMG_URL . 'popin-loader.svg',
				'ajaxActions'     => [
					'getMediaIds'   => 'imagify_get_media_ids',
					'bulkProcess'   => 'imagify_bulk_optimize',
					'getFolderData' => 'imagify_get_folder_type_data',
					'bulkInfoSeen'  => 'imagify_bulk_info_seen',
				],
				'ajaxNonce'       => wp_create_nonce( 'imagify-bulk-optimize' ),
				'bufferSizes'     => [
					'wp'             => 4,
					'custom-folders' => 4,
				],
				'labels'          => [
					'overviewChartLabels'            => [
						'unoptimized' => __( 'Unoptimized', 'imagify' ),
						'optimized'   => __( 'Optimized', 'imagify' ),
						'error'       => __( 'Error', 'imagify' ),
					],
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
					'nothingToDoTitle'               => __( 'Hold on!', 'imagify' ),
					'nothingToDoText'                => [
						'optimize'      => __( 'All your media files have been optimized by Imagify. Congratulations!', 'imagify' ),
						'generate_webp' => __( 'All your optimized images already have a WebP version. Congratulations!', 'imagify' ),
					],
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
				],
			];

			if ( get_transient( 'imagify_large_library' ) ) {
				// On huge media libraries, don't use Imagifybeat, and fetch stats only when the process ends.
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
				$translations['bufferSizes']['wp'] = apply_filters_deprecated( 'imagify_bulk_buffer_size', [ $translations['bufferSizes']['wp'] ], '1.7', 'imagify_bulk_buffer_sizes' );
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
			return [
				'backupOption'  => get_imagify_option( 'backup' ),
				'context'       => 'custom-folders',
				'imagifybeatID' => $imagifybeat_actions->get_imagifybeat_id( 'custom_folders_optimization_status' ),
				'labels'        => [
					'bulkActionsOptimize' => __( 'Optimize', 'imagify' ),
					'bulkActionsRestore'  => __( 'Restore Original', 'imagify' ),
				],
			];

		default:
			return [];
	} // End switch().
}
