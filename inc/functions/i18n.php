<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

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
				'labels' => array(
					'ValidApiKeyText'         => __( 'Your API key is valid.', 'imagify' ),
					'waitApiKeyCheckText'     => __( 'Check in progress...', 'imagify' ),
					'ApiKeyCheckSuccessTitle' => __( 'Congratulations!', 'imagify' ),
					'ApiKeyCheckSuccessText'  => __( 'Your API key is valid. You can now configure the Imagify settings to optimize your images.', 'imagify' ),
					'noBackupTitle'           => __( 'Don\'t Need a Parachute?', 'imagify' ),
					'noBackupText'            => __( 'If you keep this option deactivated, you won\'t be able to re-optimize your images to another compression level and restore your original images in case of need.', 'imagify' ),
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
			if ( imagify_is_screen( 'attachment' ) ) {
				$image = wp_get_attachment_image_src( $post_id, 'full' );
				$image = $image && is_array( $image ) ? $image : array( '', 0, 0 );
			} else {
				$image = array( '', 0, 0 );
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
					'originalL'  => __( 'Original Image', 'imagify' ),
					'optimizedL' => __( 'Optimized Image', 'imagify' ),
					'compare'    => __( 'Compare Original VS Optimized', 'imagify' ),
					'optimize'   => __( 'Optimize', 'imagify' ),
				),
			);

		case 'library':
			return array(
				'backupOption' => (int) get_imagify_option( 'backup' ),
				'labels'       => array(
					'bulkActionsOptimize'             => __( 'Optimize', 'imagify' ),
					'bulkActionsOptimizeMissingSizes' => __( 'Optimize Missing Sizes', 'imagify' ),
					'bulkActionsRestore'              => __( 'Restore Original', 'imagify' ),
				),
			);

		case 'bulk':
			$translations = array(
				'totalUnoptimizedAttachments' => imagify_count_unoptimized_attachments(),
				'totalOptimizedAttachments'   => imagify_count_optimized_attachments(),
				'totalErrorsAttachments'      => imagify_count_error_attachments(),
				'heartbeatId'                 => 'update_bulk_data',
				'waitImageUrl'                => IMAGIFY_ASSETS_IMG_URL . 'popin-loader.svg',
				'ajaxAction'                  => 'imagify_get_unoptimized_attachment_ids',
				'ajaxContext'                 => 'wp',
				'bufferSize'                  => get_imagify_bulk_buffer_size(),
				'labels'                      => array(
					'overviewChartLabels'            => array(
						'unoptimized' => __( 'Unoptimized', 'imagify' ),
						'optimized'   => __( 'Optimized', 'imagify' ),
						'error'       => __( 'Error', 'imagify' ),
					),
					'processing'                     => __( 'Imagify is still processing. Are you sure you want to leave this page?', 'imagify' ),
					'waitTitle'                      => __( 'Please wait...', 'imagify' ),
					'waitText'                       => __( 'We are trying to get your unoptimized images, it may take time depending on the number of images.', 'imagify' ),
					'invalidAPIKeyTitle'             => __( 'Your API key isn\'t valid!', 'imagify' ),
					'overQuotaTitle'                 => __( 'Oops, It\'s Over!', 'imagify' ),
					'overQuotaText'                  => '',
					'noAttachmentToOptimizeTitle'    => __( 'Hold on!', 'imagify' ),
					'noAttachmentToOptimizeText'     => __( 'All your images have been optimized by Imagify. Congratulations!', 'imagify' ),
					'optimizing'                     => __( 'Optimizing', 'imagify' ),
					'error'                          => __( 'Error', 'imagify' ),
					'complete'                       => _x( 'Complete', 'adjective', 'imagify' ),
					/* translators: %s is a number. Don't use %d. */
					'nbrFiles'                       => __( '%s file(s)', 'imagify' ),
					'notice'                         => _x( 'Notice', 'noun', 'imagify' ),
					/* translators: %s is a number. Don't use %d. */
					'nbrErrors'                      => __( '%s error(s)', 'imagify' ),
					/* translators: 1 and 2 are file sizes. Don't use HTML entities here (like &nbsp;). */
					'textToShare'                    => __( 'Discover @imagify, the new compression tool to optimize your images for free. I saved %1$s out of %2$s!', 'imagify' ),
					'twitterShareURL'                => imagify_get_external_url( 'share-twitter' ),
					'getUnoptimizedImagesErrorTitle' => __( 'Oops, There is something wrong!', 'imagify' ),
					'getUnoptimizedImagesErrorText'  => __( 'An unknown error occurred when we tried to get all your unoptimized images. Try again and if the issue still persists, please contact us!', 'imagify' ),
				),
			);

			/**
			 * Filter the number of parallel queries during the Bulk Optimization.
			 *
			 * @since 1.5.4
			 *
			 * @param int $buffer_size Number of parallel queries.
			 */
			$translations['bufferSize'] = apply_filters( 'imagify_bulk_buffer_size', $translations['bufferSize'] );

			if ( ! imagify_valid_key() ) {
				return $translations;
			}

			$user = get_imagify_user();

			if ( is_wp_error( $user ) ) {
				$translations['labels']['overQuotaText'] = sprintf(
					/* translators: 1 is a link tag start, 2 is the link tag end. */
					__( 'To continue to optimize your images, log in to your Imagify account to %1$sbuy a pack or subscribe to a plan%2$s.', 'imagify' ),
					'<a target="_blank" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '">',
					'</a>'
				);
			} else {
				$translations['labels']['overQuotaText']  = sprintf(
					/* translators: 1 is a data quota, 2 is a date. */
					__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
					size_format( $user->quota * 1048576 ),
					date_i18n( __( 'F j, Y' ), strtotime( $user->next_date_update ) )
				);
				$translations['labels']['overQuotaText'] .= '<br/><br/>';
				$translations['labels']['overQuotaText'] .= sprintf(
					/* translators: 1 is a link tag start, 2 is the link tag end. */
					__( 'To continue to optimize your images, log in to your Imagify account to %1$sbuy a pack or subscribe to a plan%2$s.', 'imagify' ),
					'<a target="_blank" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '">',
					'</a>'
				);
			}

			return $translations;

		default:
			return array();
	} // End switch().
}
