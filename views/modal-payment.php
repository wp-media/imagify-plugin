<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<div id="imagify-pricing-modal" class="imagify-modal imagify-payment-modal hide-if-no-js" aria-hidden="false" role="dialog">
	<div class="imagify-modal-content">
		<div class="imagify-modal-main">

			<?php
			/**
			 * FIRST MODAL VIEW.
			 */
			?>

			<div class="imagify-modal-views imagify-pre-checkout-view" id="imagify-pre-checkout-view" aria-hidden="false">

				<div class="imagify-modal-section section-gray imagify-estimation-block imagify-analyzing">
					<p class="imagify-modal-title">
						<span class="imagify-numbers-calc"><?php esc_html_e( 'We analyzed your images', 'imagify' ); ?></span>
						<span class="imagify-numbers-notcalc"><?php esc_html_e( 'We are analyzing your images', 'imagify' ); ?></span>
					</p>

					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>loader-balls.svg" width="77" height="48" alt="<?php esc_attr_e( 'Analyzing', 'imagify' ); ?>" class="imagify-loader">

					<div class="imagify-modal-cols">
						<div class="imagify-col">
							<p>
								<span class="imagify-border-styled">
									<?php
									$attachments_number = imagify_count_attachments() + Imagify_Files_Stats::count_all_files();

									printf(
										/* translators: %s is a formatted number (don't use %d). */
										_n( 'You have %s original image', 'You have %s original images', $attachments_number, 'imagify' ),
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
									esc_html__( 'You currently have %s of images in your library and folders.', 'imagify' ),
									'<strong class="imagify-dark total-library-size">0</strong>'
								);
								?>
							</p>
							<p class="imagify-iconed">
								<i class="dashicons dashicons-cloud" aria-hidden="true"></i>
								<?php
								printf(
									/* translators: %s is a formatted file size. */
									esc_html__( 'You upload around %s of images per month.', 'imagify' ),
									'<strong class="imagify-dark average-month-size">0</strong>'
								);
								?>
							</p>
						</div>
					</div>
				</div><!-- .imagify-modal-section -->

				<?php $this->print_template( 'part-discount-banner' ); ?>

				<div class="imagify-modal-section imagify-pre-checkout-offers">
					<p class="imagify-modal-title">
						<span class="imagify-not-enough-title"><?php esc_html_e( 'Our recommendation for you', 'imagify' ); ?></span>
						<span class="imagify-enough-title"><?php esc_html_e( 'The free plan is enough to optimize your images', 'imagify' ); ?></span>
						<br/><span class="imagify-inner-sub-title"><?php esc_html_e( 'Based on your recent upload usage.', 'imagify' ); ?></span>
					</p>

					<div class="imagify-offer-line imagify-offer-monthly imagify-offer-selected imagify-month-selected" data-offer='{"lite":{"id":3,"name":"Lite","data":1073741824,"dataf":"1 GB","imgs":5000,"prices":{"monthly":4.99,"yearly":4.16,"add":4}}}'>
						<div class="imagify-offer-header">
							<p class="imagify-offer-title imagify-switch-my">
								<span aria-hidden="false" class="imagify-monthly"><?php esc_html_e( 'Subscribe to a monthly plan', 'imagify' ); ?></span>
								<span aria-hidden="true" class="imagify-yearly"><?php esc_html_e( 'Subscribe to a yearly plan', 'imagify' ); ?></span>
							</p>
							<div class="imagify-inline-options imagify-radio-line">
								<input id="imagify-subscription-monthly" type="radio" value="monthly" name="plan-subscription" checked="checked">
								<label for="imagify-subscription-monthly"><?php esc_html_e( 'Monthly', 'imagify' ); ?></label>

								<input id="imagify-subscription-yearly" type="radio" value="yearly" name="plan-subscription">
								<label for="imagify-subscription-yearly"><?php esc_html_e( 'Yearly', 'imagify' ); ?><span class="imagify-2-free"><?php esc_html_e( '2 months free', 'imagify' ); ?></span></label>
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
										__( '%s per<br>additional Gb', 'imagify' ),
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
								<?php esc_html_e( 'Optimize the images you already have with a One Time plan', 'imagify' ); ?>
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

								<label for="imagify-coupon-code"><?php _e( 'If you have a <strong>coupon code</strong><br> use it here:', 'imagify' ); ?></label>
							</p>
							<p class="imagify-coupon-input">
								<input type="text" class="imagify-coupon-code" name="imagify-coupon-code" id="imagify-coupon-code" value="" placeholder="<?php _e( 'Coupon Code', 'imagify' ); ?>" autocomplete="off">
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

					<p class="imagify-footer-lines"><?php esc_html_e( 'Monthly plans come with credits which are renewed every month. The billing happens automatically each month or year, depending on which billing period you choose.', 'imagify' ); ?></p>
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

						<?php $this->print_template( 'part-settings-discount-banner' ); ?>

						<div class="imagify-inline-options imagify-small-options imagify-radio-line">
							<input id="imagify-pricing-montly" type="radio" value="monthly" name="plan-pricing" checked="checked">
							<label for="imagify-pricing-montly"><?php esc_html_e( 'Monthly', 'imagify' ); ?></label>

							<input id="imagify-pricing-yearly" type="radio" value="yearly" name="plan-pricing">
							<label for="imagify-pricing-yearly"><?php esc_html_e( 'Yearly', 'imagify' ); ?><span class="imagify-2-free imagify-b-right"><?php esc_html_e( '2 months free', 'imagify' ); ?></span></label>
						</div><!-- .imagify-radio-line -->


						<div class="imagify-pricing-table imagify-month-selected">

						<script type="text/html" id="imagify-offer-monthly-template"><div class="imagify-offer-line imagify-offer-monthlies imagify-flex-table">
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

									<span class="imagify-recommend" aria-hidden="true"><?php esc_html_e( 'We recommend for you', 'imagify' ); ?></span>

									<p class="imagify-price-complement">
										<?php
										printf(
											/* translators: %s is a formatted price. */
											__( '%s per<br>additional Gb', 'imagify' ),
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
								<p><a class="button imagify-button-ghost imagify-button-medium imagify-mt1 imagify-mb1 imagify-mr1" href="<?php echo esc_html( imagify_get_external_url( 'contact' ) ); ?>" target="_blank"><i class="dashicons dashicons-email" aria-hidden="true"></i>&nbsp;<?php esc_html_e( 'Contact Us', 'imagify' ); ?></a></p>
							</div>
						</div>

					</div><!-- .imagify-tab-content -->
					<div class="imagify-tab-content" id="imagify-pricing-tab-onetime" role="tabpanel">
						<div class="imagify-modal-section section-gray">
							<p><?php esc_html_e( 'One Time plans are useful if you have a lots of existing images which need to be optimized. You can use it for bulk optimizing all your past images. You will pay only once.', 'imagify' ); ?></p>
						</div>

						<div class="imagify-pricing-table imagify-month-selected">
						<script type="text/html" id="imagify-offer-onetime-template"><div class="imagify-offer-line imagify-flex-table imagify-offer-onetimes">
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
									<span class="imagify-recommend"><?php esc_html_e( 'We recommend for you', 'imagify' ); ?></span>
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

				<iframe data-imagify-api="<?php echo esc_attr( get_imagify_option( 'api_key' ) ); ?>" id="imagify-payment-iframe" data-src="<?php echo esc_url( imagify_get_external_url( 'payment' ) ); ?>" name="imagify-payment-iframe"></iframe>

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
				<p class="imagify-modal-sidebar-title"><?php esc_html_e( 'What do our users think about Imagify', 'imagify' ); ?></p>

				<div class="imagify-modal-testimony">
					<div class="imagify-modal-testimony-person">
						<span class="imagify-modal-avatar">
							<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>pic-srhdesign.jpg" alt="">
						</span>
						<p class="imagify-modal-identity">
							<a href="https://twitter.com/SRHDesign/status/686486119249260544" target="_blank">@SRHDesign</a>
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
							<a href="https://twitter.com/EricWaltR/status/679053496382038016" target="_blank">@EricWaltr</a>
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
