<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Get the optimization data list for a specific attachment.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @param  object $attachment The attachment object.
 * @param  string $context    A context.
 * @return string             The output to print.
 */
function get_imagify_attachment_optimization_text( $attachment, $context = 'wp' ) {
	global $pagenow;

	$output                   = ( 'post.php' !== $pagenow ) ? '<ul class="imagify-datas-list">' : '';
	$output_before            = ( 'post.php' !== $pagenow ) ? '<li class="imagify-data-item">' : '<div class="misc-pub-section misc-pub-imagify imagify-data-item">';
	$output_after             = ( 'post.php' !== $pagenow ) ? '</li>' : '</div>';
	$reoptimize_link          = get_imagify_attachment_reoptimize_link( $attachment, $context );
	$reoptimize_output        = $reoptimize_link ? $reoptimize_link : '';
	$reoptimize_output_before = '<div class="imagify-datas-actions-links">';
	$reoptimize_output_after  = '</div><!-- .imagify-datas-actions-links -->';
	$error                    = get_imagify_attachment_error_text( $attachment, $context );

	if ( $error ) {
		if ( 'post.php' !== $pagenow && $reoptimize_link && $attachment->has_backup() ) {
			$reoptimize_output .= '<span class="attachment-has-backup hidden"></span>';
		}

		$reoptimize_output = $reoptimize_output_before . $reoptimize_output . $reoptimize_output_after;

		return 'post.php' === $pagenow ? $output_before . $error . $reoptimize_output . $output_after : $error . $reoptimize_output;
	}

	$attachment_id      = $attachment->id;
	$data               = $attachment->get_data();
	$optimization_level = $attachment->get_optimization_level_label();

	if ( 'post.php' !== $pagenow ) {
		$output .= $output_before . '<span class="data">' . __( 'New Filesize:', 'imagify' ) . '</span> <strong class="big">' . size_format( $data['sizes']['full']['optimized_size'], 2 ) . '</strong>' . $output_after;
	}

	$chart = '<span class="imagify-chart">
				<span class="imagify-chart-container">
					<canvas id="imagify-consumption-chart" width="15" height="15"></canvas>
				</span>
			</span>';

	$output .= $output_before;
	$output .= '<span class="data">' . __( 'Original Saving:', 'imagify' ) . '</span> ';
	$output .= '<strong>' . ( 'post.php' !== $pagenow ? $chart : '' ) . '<span class="imagify-chart-value">' . $data['sizes']['full']['percent'] . '</span>%</strong>';
	$output .= $output_after;

	// More details section.
	if ( 'post.php' !== $pagenow ) {
		// New list.
		$output .= '</ul>';
		$output .= '<p class="imagify-datas-more-action">';
			$output .= '<a href="#imagify-view-details-' . $attachment_id . '" data-close="' . __( 'Close details', 'imagify' ) . '" data-open="' . __( 'View details', 'imagify' ) . '">';
				$output .= '<span class="the-text">' . __( 'View details', 'imagify' ) . '</span>';
				$output .= '<span class="dashicons dashicons-arrow-down-alt2"></span>';
			$output .= '</a>';
		$output .= '</p>';
		$output .= '<ul id="imagify-view-details-' . $attachment_id . '" class="imagify-datas-list imagify-datas-details">';

		// Not in metabox.
		$output .= $output_before . '<span class="data">' . __( 'Original Filesize:', 'imagify' ) . '</span> <strong class="original">' . $attachment->get_original_size() . '</strong>' . $output_after;
	}

	$output .= $output_before . '<span class="data">' . __( 'Level:', 'imagify' ) . '</span> <strong>' . $optimization_level . '</strong>' . $output_after;

	$total_optimized_thumbnails = $attachment->get_optimized_sizes_count();

	if ( $total_optimized_thumbnails ) {
		$output .= $output_before . '<span class="data">' . __( 'Thumbnails Optimized:', 'imagify' ) . '</span> <strong>' . $total_optimized_thumbnails . '</strong>' . $output_after;
		$output .= $output_before . '<span class="data">' . __( 'Overall Saving:', 'imagify' ) . '</span> <strong>' . $data['stats']['percent'] . '%</strong>' . $output_after;
	}

	// End of list.
	$output .= ( 'post.php' !== $pagenow ) ? '</ul>' : '';

	// Actions section.
	$output .= ( 'post.php' !== $pagenow ) ? '' : $output_before;
	$output .= $reoptimize_output_before;
	$output .= $reoptimize_output;

	if ( $attachment->has_backup() ) {
		$args    = array(
			'attachment_id' => $attachment_id,
			'context'       => $context,
		);
		$class   = ( 'post.php' !== $pagenow ) ? 'button-imagify-restore attachment-has-backup' : '';
		$output .= '<a id="imagify-restore-' . $attachment_id . '" href="' . get_imagify_admin_url( 'restore-upload', $args ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Restoring...', 'imagify' ) . '">';
			$output .= '<span class="dashicons dashicons-image-rotate"></span>' . __( 'Restore Original', 'imagify' );
		$output .= '</a>';

		if ( 'upload.php' !== $pagenow ) {
			$image = wp_get_attachment_image_src( $attachment_id, 'full' );

			$output .= '<input id="imagify-original-src" type="hidden" value="' . esc_url( $attachment->get_backup_url() ) . '">';
			$output .= '<input id="imagify-original-size" type="hidden" value="' . $attachment->get_original_size() . '">';
			$output .= '<input id="imagify-full-src" type="hidden" value="' . $image[0] . '">';
			$output .= '<input id="imagify-full-width" type="hidden" value="' . $image[1] . '">';
			$output .= '<input id="imagify-full-height" type="hidden" value="' . $image[2] . '">';
		}
	}

	$output .= $reoptimize_output_after;
	$output .= ( 'post.php' !== $pagenow ) ? '' : $output_after;

	return $output;
}

/**
 * Get the error message for a specific attachment.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @param  object $attachment The attachement object.
 * @param  string $context    A context.
 * @return string             The output to print.
 */
function get_imagify_attachment_error_text( $attachment, $context = 'wp' ) {
	global $pagenow;

	$attachment_id = $attachment->id;
	$data          = $attachment->get_data();
	$output        = '';
	$args          = array(
		'attachment_id' => $attachment_id,
		'context'       => $context,
	);

	if ( isset( $data['sizes']['full']['success'] ) && ! $data['sizes']['full']['success'] ) {
		$class   = ( 'post.php' !== $pagenow ) ? 'button-imagify-manual-upload' : '';
		$output .= '<strong>' . $data['sizes']['full']['error'] . '</strong><br/>';
		$output .= '<a id="imagify-upload-' . $attachment_id . '" class="button ' . $class . '" href="' . esc_url( get_imagify_admin_url( 'manual-upload', $args ) ) . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Try again', 'imagify' ) . '</a>';
	}

	return $output;
}

/**
 * Get the re-optimize link for a specific attachment.
 *
 * @since 1.0
 * @author Jonathan Buttigieg
 *
 * @param  object $attachment The attachement object.
 * @param  string $context    A context.
 * @return string             The output to print.
 */
function get_imagify_attachment_reoptimize_link( $attachment, $context = 'wp' ) {
	global $pagenow;

	// Stop the process if the API key isn't valid.
	if ( ! imagify_valid_key() ) {
		return '';
	}

	$is_already_optimized = $attachment->is_already_optimized();

	// Don't display anything if there is no backup or the image has been optimized.
	if ( ! $attachment->has_backup() && ! $is_already_optimized ) {
		return '';
	}

	$attachment_id = $attachment->id;
	$level         = $attachment->get_optimization_level();
	$args          = array(
		'attachment_id' => $attachment_id,
		'context'       => $context,
	);
	$output        = '';
	$class         = ( 'post.php' !== $pagenow ) ? 'button-imagify-manual-override-upload' : '';

	// Re-optimize to Ultra.
	if ( 1 === $level || 0 === $level ) {
		$args['optimization_level'] = 2;
		$output .= '<a href="' . esc_url( get_imagify_admin_url( 'manual-override-upload', $args ) ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">';
			/* translators: %s is an optimization level. */
			$output .= '<span class="dashicons dashicons-admin-generic"></span>' . sprintf( __( 'Re-Optimize to %s', 'imagify' ), __( 'Ultra', 'imagify' ) );
		$output .= '</a>';
	}

	// Re-optimize to Aggressive.
	if ( ( 2 === $level && ! $is_already_optimized ) || 0 === $level ) {
		$args['optimization_level'] = 1;
		$output .= '<a href="' . esc_url( get_imagify_admin_url( 'manual-override-upload', $args ) ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">';
			/* translators: %s is an optimization level. */
			$output .= '<span class="dashicons dashicons-admin-generic"></span>' . sprintf( __( 'Re-Optimize to %s', 'imagify' ), __( 'Aggressive', 'imagify' ) );
		$output .= '</a>';
	}

	// Re-optimize to Normal.
	if ( ( 2 === $level || 1 === $level ) && ! $is_already_optimized ) {
		$args['optimization_level'] = 0;
		$output .= '<a href="' . esc_url( get_imagify_admin_url( 'manual-override-upload', $args ) ) . '" class="' . $class . '" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">';
			/* translators: %s is an optimization level. */
			$output .= '<span class="dashicons dashicons-admin-generic"></span>' . sprintf( __( 'Re-Optimize to %s', 'imagify' ), __( 'Normal', 'imagify' ) );
		$output .= '</a>';
	}

	return $output;
}

/**
 * Get all data to diplay for a specific attachment.
 *
 * @since 1.2
 * @author Jonathan Buttigieg
 *
 * @param  object $attachment  The attachement object.
 * @param  string $context     A context.
 * @return string              The output to print.
 */
function get_imagify_media_column_content( $attachment, $context = 'wp' ) {
	$attachment_id  = $attachment->id;
	$attachment_ext = $attachment->get_extension();

	// Check if the attachment extension is allowed.
	if ( 'wp' === $context && ! imagify_is_attachment_mime_type_supported( $attachment_id ) ) {
		/* translators: %s is a file extension. */
		return sprintf( __( '%s can\'t be optimized', 'imagify' ), strtoupper( $attachment_ext ) );
	}

	// Check if the API key is valid.
	if ( ! imagify_valid_key() && ! $attachment->is_optimized() ) {
		$output  = __( 'Invalid API key', 'imagify' );
		$output .= '<br/>';
		$output .= '<a href="' . esc_url( get_imagify_admin_url( 'options-general' ) ) . '">' . __( 'Check your Settings', 'imagify' ) . '</a>';
		return $output;
	}

	$transient_context = ( 'wp' !== $context ) ? strtolower( $context ) . '-' : '';
	$transient_name    = 'imagify-' . $transient_context . 'async-in-progress-' . $attachment_id;

	if ( false !== get_transient( $transient_name ) ) {
		return '<div class="button"><span class="imagify-spinner"></span>' . __( 'Optimizing...', 'imagify' ) . '</div>';
	}

	// Check if the image was optimized.
	if ( ! $attachment->is_optimized() && ! $attachment->has_error() ) {
		$args = array(
			'attachment_id' => $attachment_id,
			'context'       => $context,
		);
		$output = '<a id="imagify-upload-' . $attachment_id . '" href="' . esc_url( get_imagify_admin_url( 'manual-upload', $args ) ) . '" class="button-primary button-imagify-manual-upload" data-waiting-label="' . esc_attr__( 'Optimizing...', 'imagify' ) . '">' . __( 'Optimize', 'imagify' ) . '</a>';

		if ( $attachment->has_backup() ) {
			$output .= '<span class="attachment-has-backup hidden"></span>';
		}

		return $output;
	}

	return get_imagify_attachment_optimization_text( $attachment, $context );
}

/**
 * Display the plan chooser section.
 *
 * @since  1.6
 * @author Geoffrey
 *
 * @todo Add only for no-payable users?
 *
 * @return string HTML.
 */
function get_imagify_new_to_imagify() {
	if ( ! imagify_valid_key() ) {
		return '';
	}

	/**
	 * Filter whether the plan chooser section is displayed.
	 *
	 * @param $show_new bool Default to true: display the section.
	 */
	$show_new = apply_filters( 'imagify_show_new_to_imagify', true );

	if ( ! $show_new ) {
		return '';
	}

	return '
		<div class="imagify-section imagify-section-positive">
			<div class="imagify-start imagify-mr2">
				<button id="imagify-get-pricing-modal" data-nonce="' . wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ) . '" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-light imagify-button-big">
					<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
					<span class="button-text">' . esc_html__( 'What plan do I need?', 'imagify' ) . '</span>
				</button>
			</div>
			<div class="imagify-oh">
				<p class="imagify-section-title">' . esc_html__( 'You\'re new to Imagify?', 'imagify' ) . '</p>
				<p>' . esc_html__( 'Let us help you by analyzing your existing images and determine the best plan for you', 'imagify' ) . '</p>
			</div>
		</div>
	';
}

/**
 * Return the formatted price present in pricing tables.
 *
 * @since  1.6
 * @author Geoffrey
 *
 * @param  float $value The price value.
 * @return string       The markuped price.
 */
function get_imagify_price_table_format( $value ) {
	$v = explode( '.', (string) $value );

	return '<span class="imagify-price-big">' . $v[0] . '</span> <span class="imagify-price-mini">.' . ( strlen( $v[1] ) === 1 ? $v[1] . '0' : $v[1] ) . '</span>';
}

/**
 * Get the payment modal HTML.
 *
 * @since 1.6
 * @since 1.6.3 Include discount banners.
 * @author Geoffrey
 *
 * @todo Make first offers dynamic thanks to consumption estimation.
 */
function imagify_payment_modal() {
	?>
	<div id="imagify-pricing-modal" class="imagify-modal imagify-payment-modal hide-if-no-js" aria-hidden="false" role="dialog">
		<div class="imagify-modal-content">
			<div class="imagify-modal-main">
				<div class="imagify-modal-views imagify-pre-checkout-view" id="imagify-pre-checkout-view" aria-hidden="false">

					<?php
					$attachments_number = imagify_count_attachments();
					$total_size         = get_imagify_option( 'total_size_images_library', false );
					$per_month          = get_imagify_option( 'average_size_images_per_month', false );
					?>

					<div class="imagify-modal-section section-gray imagify-estimation-block<?php echo false === $total_size ? ' imagify-analyzing' : ''; ?>">
						<p class="imagify-modal-title">
							<span class="imagify-numbers-calc"><?php esc_html_e( 'We analysed your images', 'imagify' ); ?></span>
							<span class="imagify-numbers-notcalc"><?php esc_html_e( 'We are analysing your images', 'imagify' ); ?></span>
						</p>

						<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>loader-balls.svg" width="77" height="48" alt="<?php esc_attr_e( 'Analyzing', 'imagify' ); ?>" class="imagify-loader">

						<div class="imagify-modal-cols">
							<div class="imagify-col">
								<p>
									<span class="imagify-border-styled">
										<?php
										printf(
											/* translators: %s is a formatted number (don't use %d). */
											_n( 'You have %s image', 'You have %s images', $attachments_number, 'imagify' ),
											'</span><span class="imagify-big-number">' . number_format_i18n( $attachments_number ) . '</span><span class="imagify-border-styled">'
										);
										?>
									</span>
								</p>
							</div>
							<div class="imagify-col">
								<p class="imagify-iconed">
									<i class="dashicons dashicons-images-alt2" aria-hidden="true"></i>
									<?php
									printf(
										/* translators: %s is a formatted file size. */
										esc_html__( 'You currently have %s of images in your library.', 'imagify' ),
										'<strong class="imagify-dark total-library-size">' . ( isset( $total_size['human'] ) ? $total_size['human'] : $total_size ) . '</strong>'
									);
									?>
								</p>
								<p class="imagify-iconed">
									<i class="dashicons dashicons-cloud" aria-hidden="true"></i>
									<?php
									printf(
										/* translators: %s is a formatted file size. */
										esc_html__( 'You upload around %s of images per month.', 'imagify' ),
										'<strong class="imagify-dark average-month-size">' . ( isset( $per_month['human'] ) ? $per_month['human'] : $per_month ) . '</strong>'
									);
									?>
								</p>
							</div>
						</div>
					</div><!-- .imagify-modal-section -->

					<?php imagify_print_discount_banner(); ?>

					<div class="imagify-modal-section imagify-pre-checkout-offers">
						<p class="imagify-modal-title">
							<span class="imagify-not-enough-title"><?php esc_html_e( 'We recommend you this plan', 'imagify' ); ?></span>
							<span class="imagify-enough-title"><?php esc_html_e( 'The free plan is enough to optimize your images', 'imagify' ); ?></span>

						</p>

						<div class="imagify-offer-line imagify-offer-monthly imagify-offer-selected imagify-month-selected" data-offer='{"lite":{"id":3,"name":"Lite","data":1073741824,"dataf":"1 GB","imgs":5000,"prices":{"monthly":4.99,"yearly":4.16,"add":4}}}'>
							<div class="imagify-offer-header">
								<p class="imagify-offer-title imagify-switch-my">
									<span aria-hidden="false" class="imagify-monthly"><?php esc_html_e( 'Subscribe a monthly plan', 'imagify' ); ?></span>
									<span aria-hidden="true" class="imagify-yearly"><?php esc_html_e( 'Subscribe a yearly plan', 'imagify' ); ?></span>
								</p>
								<div class="imagify-inline-options imagify-radio-line">
									<input id="imagify-subscription-monthly" type="radio" value="monthly" name="plan-subscription" checked="checked">
									<label for="imagify-subscription-monthly"><?php esc_html_e( 'Monthly' , 'imagify' ); ?></label>

									<input id="imagify-subscription-yearly" type="radio" value="yearly" name="plan-subscription">
									<label for="imagify-subscription-yearly"><?php esc_html_e( 'Yearly' , 'imagify' ); ?><span class="imagify-2-free"><?php esc_html_e( '2 months free', 'imagify' ) ?></span></label>
								</div><!-- .imagify-radio-line -->
							</div><!-- .imagify-offer-header -->

							<div class="imagify-offer-content imagify-flex-table">

								<div class="imagify-col-checkbox">
									<input type="checkbox" name="imagify-offer" id="imagify-offer-1gb" value="1Gb" checked="checked" class="imagify-checkbox medium">
									<label for="imagify-offer-1gb">
										<span class="imagify-the-offer">
											<span class="imagify-offer-size">1 GB</span>
											<span class="imagify-offer-by"><?php esc_html_e( '/month', 'imagify' ); ?></span>
										</span>
										<span class="imagify-approx">
											<?php
											printf(
												/* translators: %s is a formatted number (don't use %d). */
												esc_html__( 'approx: %s images', 'imagify' ),
												'<span class="imagify-approx-nb">' . number_format_i18n( 5000 ) . '</span>'
											);
											?>
										</span>
									</label>
								</div>
								<div class="imagify-col-price imagify-flex-table">
									<span class="imagify-price-block">
										<span class="imagify-dollars">$</span>
										<span class="imagify-number-block">
											<span class="imagify-switch-my">
												<span class="imagify-monthly" aria-hidden="false">
													<span class="imagify-price-big">3</span>
													<span class="imagify-price-mini">.99</span>
												</span>
												<span class="imagify-yearly" aria-hidden="true">
													<span class="imagify-price-big">3</span>
													<span class="imagify-price-mini">.16</span>
												</span>
											</span>
											<span class="imagify-price-by"><?php esc_html_e( '/month', 'imagify' ); ?></span>
										</span>
									</span>

									<p class="imagify-price-complement">
										<?php
										printf(
											/* translators: %s is a formatted price. */
											__( '%s per<br>additionnal Gb', 'imagify' ),
											'<span class="imagify-price-add-data"></span>'
										);
										?>
									</p>

								</div>
								<div class="imagify-col-other-actions">
									<a href="#imagify-plans-selection-view" class="imagify-choose-another-plan" data-imagify-choose="plan"><?php esc_html_e( 'Choose another plan', 'imagify' ); ?></a>
								</div>

							</div><!-- .imagify-offer-content -->

						</div><!-- .imagify-offer-line -->

						<div class="imagify-offer-line imagify-offer-onetime" data-offer='{"recommended":{"id":999,"name":"Customized","data":3000001337,"dataf":"3 GB","imgs":54634,"price":28.98}}'>
							<div class="imagify-offer-header">
								<p class="imagify-offer-title">
									<?php esc_html_e( 'Optimize the images you already have, buy a one-time plan', 'imagify' ); ?>
								</p>
							</div><!-- .imagify-offer-header -->

							<div class="imagify-offer-content imagify-flex-table">

								<div class="imagify-col-checkbox">
									<input type="checkbox" name="imagify-offer" id="imagify-offer-custom" value="1Gb" checked="checked" class="imagify-checkbox medium">
									<label for="imagify-offer-custom">
										<span class="imagify-the-offer">
											<span class="imagify-offer-size">3 GB</span>
										</span>
										<span class="imagify-approx">
											<?php
											printf(
												/* translators: %s is a formatted number (don't use %d). */
												esc_html__( 'approx: %s images', 'imagify' ),
												'<span class="imagify-approx-nb">' . number_format_i18n( 54000 ) . '</span>'
											);
											?>
										</span>
									</label>
								</div>
								<div class="imagify-col-price imagify-flex-table">
									<span class="imagify-price-block">
										<span class="imagify-dollars">$</span>
										<span class="imagify-number-block">
											<span class="imagify-price-big"></span>
											<span class="imagify-price-mini"></span>
										</span>
									</span>
								</div>
								<div class="imagify-col-other-actions">
									<a href="#imagify-plans-selection-view" class="imagify-choose-another-plan" data-imagify-choose="onetime"><?php esc_html_e( 'Choose another plan', 'imagify' ); ?></a>
								</div>

							</div><!-- .imagify-offer-content -->

						</div><!-- .imagify-offer-line -->


						<div class="imagify-submit-line">
							<div class="imagify-coupon-section">
								<p class="imagify-coupon-text">

									<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>loader-balls.svg" width="60" height="36" alt="<?php esc_attr_e( 'Checking Coupon', 'imagify' ); ?>" class="imagify-coupon-loader">

									<label for="imagify-coupon-code"><?php _e( 'If you have a <strong>coupon code</strong><br> use it there:', 'imagify' ); ?></label>
								</p>
								<p class="imagify-coupon-input">
									<input type="text" class="imagify-coupon-code" name="imagify-coupon-code" id="imagify-coupon-code" value="" placeholder="<?php _e( 'Coupon Code', 'imagify' ) ?>" autocomplete="off">
									<button type="button" class="button button-secondary imagify-button-secondary" id="imagify-coupon-validate"><?php _e( 'OK' ); ?></button>
								</p>
							</div>
							<div class="imagify-submit-section">
								<button type="button" class="button button-secondary imagify-button-secondary" id="imagify-modal-checkout-btn">
									<i class="dashicons dashicons-cart" aria-hidden="true"></i>
									<?php _e( 'Checkout', 'imagify' ); ?>
								</button>
							</div>
						</div>

						<p class="imagify-footer-lines"><?php esc_html_e( 'Monthly plans come with credits which is renewed every months. The billing happens automatically each month or year depending the billing period you choose.', 'imagify' ); ?></p>
					</div>
				</div><!-- .imagify-pre-checkout-view -->

				<?php
				/**
				 * SECOND MODAL VIEW.
				 */
				?>

				<div class="imagify-modal-views imagify-plans-selection-view" id="imagify-plans-selection-view" aria-hidden="true">
					<p class="imagify-modal-title"><?php _e( 'Choose a plan', 'imagify' ); ?></p>
					<ul class="imagify-tabs" role="tablist">
						<li class="imagify-tab imagify-current">
							<a href="#imagify-pricing-tab-monthly" role="tab" aria-controls="imagify-pricing-tab-monthly" aria-selected="true">
								<?php esc_html_e( 'Monthly Plans', 'imagify' ); ?>
							</a>
						</li>
						<li class="imagify-tab">
							<a href="#imagify-pricing-tab-onetime" role="tab" aria-controls="imagify-pricing-tab-onetime" aria-selected="false">
								<?php esc_html_e( 'One Time Plans', 'imagify' ); ?>
							</a>
						</li>
					</ul><!-- .imagify-tabs -->

					<div class="imagify-tabs-contents">

						<div class="imagify-tab-content imagify-current" id="imagify-pricing-tab-monthly" role="tabpanel">

							<div class="imagify-modal-section section-gray">
								<p><?php esc_html_e( 'Monthly plans come with credits which is renewed every months. The billing happens automatically each month or year depending the billing period you choose.', 'imagify' ); ?></p>
							</div>

							<?php imagify_print_discount_banner(); ?>

							<div class="imagify-inline-options imagify-small-options imagify-radio-line">
								<input id="imagify-pricing-montly" type="radio" value="monthly" name="plan-pricing" checked="checked">
								<label for="imagify-pricing-montly"><?php esc_html_e( 'Monthly' , 'imagify' ); ?></label>

								<input id="imagify-pricing-yearly" type="radio" value="yearly" name="plan-pricing">
								<label for="imagify-pricing-yearly"><?php esc_html_e( 'Yearly' , 'imagify' ); ?><span class="imagify-2-free imagify-b-right"><?php esc_html_e( '2 months free', 'imagify' ) ?></span></label>
							</div><!-- .imagify-radio-line -->


							<div class="imagify-pricing-table imagify-month-selected">

							<script type="text/template" id="imagify-offer-monthly-template"><div class="imagify-offer-line imagify-offer-monthlies imagify-flex-table">
									<div class="imagify-col-details">
										<p class="imagify-label">
											<span class="imagify-the-offer">
												<span class="imagify-offer-size"></span>
												<span class="imagify-offer-by"><?php esc_html_e( '/month', 'imagify' ); ?></span>
											</span>
											<span class="imagify-approx">
												<?php
												printf(
													/* translators: %s is a formatted number (don't use %d). */
													__( 'approx: %s images', 'imagify' ),
													'<span class="imagify-approx-nb"></span>'
												);
												?>
											</span>
										</p>
									</div>
									<div class="imagify-col-price imagify-flex-table">
										<span class="imagify-price-block">
											<span class="imagify-dollars">$</span>
											<span class="imagify-number-block">
												<span class="imagify-switch-my"></span>
												<span class="imagify-price-by"><?php esc_html_e( '/month', 'imagify' ); ?></span>
											</span>
										</span>

										<span class="imagify-recommend" aria-hidden="true"><?php esc_html_e( 'we recommend for you', 'imagify' ); ?></span>

										<p class="imagify-price-complement">
											<?php
											printf(
												/* translators: %s is a formatted price. */
												__( '%s per<br>additionnal Gb', 'imagify' ),
												'<span class="imagify-price-add-data"></span>'
											);
											?>
										</p>

									</div><!-- .imagify-col-price -->

									<div class="imagify-col-other-actions">
										<button type="button" class="button imagify-button-secondary mini imagify-payment-btn-select-plan"><?php esc_html_e( 'Choose plan', 'imagify' ); ?></button>
									</div>
								</div><!-- .imagify-offer-line --></script>
							</div><!-- .imagify-pricing-table -->

							<div class="imagify-cols">
								<div class="imagify-col imagify-txt-start">
									<p class="imagify-special-needs">
										<strong><?php esc_html_e( 'Need more?', 'imagify' ); ?></strong>
										<span><?php esc_html_e( 'for special needs', 'imagify' ); ?></span>
									</p>
								</div>
								<div class="imagify-col imagify-txt-end">
									<p><a class="button imagify-button-ghost imagify-button-medium imagify-mt1 imagify-mb1 imagify-mr1" href="https://imagify.io/<?php echo ( get_locale() === 'fr_FR' ? 'fr/' : '' ) ?>contact" target="_blank"><i class="dashicons dashicons-email" aria-hidden="true"></i>&nbsp;<?php esc_html_e( 'Contact Us', 'imagify' ); ?></a></p>
								</div>
							</div>

						</div><!-- .imagify-tab-content -->
						<div class="imagify-tab-content" id="imagify-pricing-tab-onetime" role="tabpanel">
							<div class="imagify-modal-section section-gray">
								<p><?php esc_html_e( 'One time plans are useful if you have a lots of existing images which need to be optimized. You can use it for bulk optimizing all your past images. You will pay only once.', 'imagify' ); ?></p>
							</div>

							<div class="imagify-pricing-table imagify-month-selected">
							<script type="text/template" id="imagify-offer-onetime-template"><div class="imagify-offer-line imagify-flex-table imagify-offer-onetimes">
									<div class="imagify-col-details">
										<p class="imagify-label">
											<span class="imagify-the-offer">
												<span class="imagify-offer-size"></span>
											</span>
											<span class="imagify-approx">
												<?php
												printf(
													/* translators: %s is a formatted number (don't use %d). */
													__( 'approx: %s images', 'imagify' ),
													'<span class="imagify-approx-nb"></span>'
												);
												?>
											</span>
										</p>
									</div>
									<div class="imagify-col-price">
										<span class="imagify-price-block">
											<span class="imagify-dollars">$</span>
											<span class="imagify-number-block"></span>
										</span>
										<span class="imagify-recommend"><?php esc_html_e( 'we recommend for you', 'imagify' ); ?></span>
									</div><!-- .imagify-col-price -->

									<div class="imagify-col-other-actions">
										<button type="button" class="button imagify-button-secondary mini imagify-payment-btn-select-plan"><?php esc_html_e( 'Choose plan', 'imagify' ); ?></button>
									</div>
								</div><!-- .imagify-offer-line --></script>
							</div><!-- .imagify-pricing-table -->

						</div><!-- .imagify-tab-content -->

					</div><!-- .imagify-tabs-contents -->
				</div><!-- .imagify-plans-selection-view -->


				<?php
				/**
				 * THIRD MODAL VIEW.
				 */
				?>

				<div class="imagify-modal-views imagify-payment-process-view" id="imagify-payment-process-view" aria-hidden="true">

					<?php $imagify_api_key = get_imagify_option( 'api_key', false ); ?>

					<iframe data-imagify-api="<?php echo $imagify_api_key; ?>" id="imagify-payment-iframe" data-src="<?php echo IMAGIFY_PAYMENT_URL; ?>" name="imagify-payment-iframe" src="" frameborder="0"></iframe>

				</div><!-- .imagify-modal-views -->

				<?php
				/**
				 * SUCCESS VIEW.
				 */
				?>

				<div class="imagify-modal-views imagify-success-view" id="imagify-success-view" aria-hidden="true">
					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>big-blue-check.png" width="113" height="109" alt="">
					<p><?php esc_html_e( 'Thank you for being awesome!', 'imagify' ); ?></p>
				</div><!-- .imagify-modal-views -->

				<button class="close-btn" type="button">
					<i aria-hidden="true" class="dashicons dashicons-no-alt"></i>
					<span class="screen-reader-text"><?php esc_html_e( 'Close', 'imagify' ); ?></span>
				</button>

			</div><!-- .imagify-modal-main -->

			<div class="imagify-modal-sidebar">
				<div class="imagify-modal-sidebar-content imagify-txt-start">
					<p class="imagify-modal-sidebar-title"><?php esc_html_e( 'What do our user think about Imagify', 'imagify' ) ?></p>

					<div class="imagify-modal-testimony">
						<div class="imagify-modal-testimony-person">
							<span class="imagify-modal-avatar">
								<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>pic-srhdesign.jpg" alt="">
							</span>
							<p class="imagify-modal-identity">
								<a href="https://twitter.com/SRHDesign" target="_blank">@SRHDesign</a>
								<a href="https://twitter.com/SRHDesign/status/686486119249260544" target="_blank"><time datetime="2016-01-11">11 jan. 2016 @ 17:40</time></a>
							</p>
						</div>
						<div class="imagify-modal-testimony-content">
							<p>@imagify is an awesome tool that is powerful &amp; easy to use. It's fast, rivals and surpasses other established plugins/software. Awesome!</p>
						</div>
					</div>

					<div class="imagify-modal-testimony">
						<div class="imagify-modal-testimony-person">
							<span class="imagify-modal-avatar">
								<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>pic-ericwaltr.jpg" alt="">
							</span>
							<p class="imagify-modal-identity">
								<a href="https://twitter.com/EricWaltr" target="_blank">@EricWaltr</a>
								<a href="https://twitter.com/EricWaltR/status/679053496382038016" target="_blank"><time datetime="2016-01-11">21 dec. 2015 @ 22:39</time></a>
							</p>
						</div>
						<div class="imagify-modal-testimony-content">
							<p>Clearly @imagify is the most awesome tool to compress images on your website! A must try</p>
						</div>
					</div>

					<div class="imagify-modal-sidebar-trust imagify-txt-center">
						<p class="imagify-secondary">
							<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>icon-lock.png" srcset="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>icon-lock.svg 2x" width="16" height="19" alt="">&nbsp;<?php esc_html_e( 'Secure Credit Card Payment', 'imagify' ); ?></p>
						<p><?php esc_html_e( 'This is secure 128-bits SSL encrypted payment', 'imagify' ); ?></p>
					</div>
				</div>
			</div><!-- .imagify-modal-sidebar -->

			<div class="imagify-modal-loader"></div>
		</div><!-- .imagify-modal-content-->
	</div><!-- .imagify-payment-modal -->
	<?php
}

/**
 * Print the discount banner used inside Payment Modal.
 *
 * @author Geoffrey Crofte
 * @since  1.6.3
 *
 * @return void
 */
function imagify_print_discount_banner() {
	?>
	<div class="imagify-modal-promotion" aria-hidden="true">
		<p class="imagify-promo-title">
			<?php
			printf(
				/* translators: %s is a formatted percentage. */
				__( '%s OFF on all the subscriptions', 'secupress' ),
				'<span class="imagify-promotion-number"></span>'
			);
			?>
		</p>
		<p class="imagify-until-date">
			<?php
			printf(
				/* translators: %s is a formatted date. */
				__( 'Special Offer<br><strong>Until %s</strong>', 'secupress' ),
				'<span class="imagify-promotion-date"></span>'
			);
			?>
		</p>
	</div>
	<?php
}
