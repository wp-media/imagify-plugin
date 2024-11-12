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
									printf(
										/* translators: %s is a formatted number (don't use %d). */
										_n( 'You have %s original image', 'You have %s original images', $data['attachments_number'], 'imagify' ),
										'</span><span class="imagify-big-number">' . number_format_i18n( $data['attachments_number'] ) . '</span><span class="imagify-border-styled">'
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
									<span class="imagify-price-add-data"></span>
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
				<p class="imagify-modal-title"><?php _e( 'Choose the Perfect Plan for Your Needs', 'imagify' ); ?></p>
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

						<div class="imagify-modal-section">
							<p><?php esc_html_e( 'Pick the plan that fits your image optimization goals and unlock the full potential of Imagify!', 'imagify' ); ?></p>
						</div>

						<?php $this->print_template( 'part-settings-discount-banner' ); ?>

						<div class="imagify-toggle-container">
							<span class="imagify-toggle-label">Monthly</span>
							<label class="imagify-switch">
								<input type="checkbox" id="imagify-toggle-plan">
								<span class="imagify-slider"></span>
							</label>
							<span class="imagify-toggle-label">Yearly</span>
							<div class="imagify-arrow-container">
								<img src="data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2765%27 height=%2729%27 fill=%27none%27%3E%3Cpath fill=%27%23B8BFC5%27 d=%27M1.566 14.504a.5.5 0 0 0-.562.43l-.594 4.46a.5.5 0 1 0 .99.133l.53-3.965 3.964.528a.5.5 0 0 0 .133-.99l-4.46-.596Zm40.077-1.475.495-.07-.495.07ZM64.5 1a.5.5 0 0 0-1 0h1ZM32.616 13.054l-.47.17.47-.17Zm-31.513 2.25C7.708 23.94 18.576 28.252 27.473 28c4.45-.125 8.465-1.395 11.202-3.904 2.758-2.527 4.158-6.255 3.463-11.138l-.99.141c.654 4.599-.664 7.983-3.148 10.26-2.505 2.295-6.258 3.52-10.555 3.642-8.6.243-19.153-3.942-25.548-12.305l-.794.608Zm41.035-2.346c-.413-2.903-1.816-4.72-3.496-5.83l-.551.833c1.448.957 2.685 2.526 3.057 5.138l.99-.14Zm-9.992.267c1.477 4.073 4.263 6.62 7.615 7.806 3.338 1.18 7.203 1 10.855-.299C57.919 18.135 64.5 11.018 64.5 1h-1c0 9.537-6.257 16.314-13.219 18.79-3.48 1.238-7.105 1.388-10.187.298-3.067-1.085-5.632-3.41-7.008-7.204l-.94.34Zm6.496-6.098c-1.925-1.272-3.97-.785-5.3.538-1.316 1.308-1.962 3.45-1.197 5.56l.94-.341c-.624-1.723-.095-3.46.962-4.51 1.042-1.036 2.568-1.388 4.044-.413l.551-.834Z%27/%3E%3C/svg%3E" />
								<img src="data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2766%27 height=%2729%27 fill=%27none%27%3E%3Cpath fill=%27%238BC34A%27 d=%27M2.632 14.009a1 1 0 0 0-1.123.859l-1.19 8.92a1 1 0 0 0 1.983.265l1.057-7.93 7.93 1.058a1 1 0 1 0 .264-1.983l-8.92-1.19Zm40.01-.98.99-.141-.99.14ZM66 1a1 1 0 1 0-2 0h2ZM33.616 13.054l-.94.341.94-.34Zm-31.91 2.553c6.71 8.775 17.736 13.149 26.78 12.893 4.528-.128 8.673-1.42 11.527-4.035 2.894-2.652 4.335-6.552 3.62-11.577l-1.98.282c.634 4.456-.643 7.669-2.991 9.82-2.389 2.189-6.01 3.392-10.232 3.511-8.451.239-18.846-3.883-25.136-12.109l-1.588 1.216Zm41.927-2.719c-.434-3.05-1.92-4.991-3.715-6.178l-1.103 1.668c1.332.88 2.487 2.325 2.838 4.792l1.98-.282Zm-10.958.507c1.529 4.213 4.425 6.871 7.92 8.107 3.466 1.226 7.45 1.03 11.188-.299C59.257 18.546 66 11.258 66 1h-2c0 9.296-6.095 15.904-12.887 18.319-3.394 1.207-6.899 1.342-9.852.297-2.925-1.034-5.38-3.248-6.705-6.903l-1.88.682Zm7.243-6.685c-2.15-1.421-4.454-.866-5.929.6-1.446 1.438-2.149 3.782-1.313 6.085l1.88-.682c-.555-1.529-.083-3.063.844-3.985.898-.893 2.164-1.176 3.415-.35l1.103-1.668Z%27/%3E%3C/svg%3E" />
							</div>
							<div class="imagify-badge-container">
								<span class="imagify-badge">2 MONTHS FREE</span>
							</div>
						</div>

						<div class="imagify-pricing-table imagify-month-selected" id="imagify_all_plan_view">

						<script type="text/html" id="imagify-offer-monthly-template"><div class="imagify-offer-line imagify-offer-monthlies imagify-flex-table">
								<div class="imagify-col-details imagify-col-label">
									<p class="imagify-label-plans"></p>
								</div>
								<div class="imagify-col-details">
									<p>
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
										</span>
										<span class="imagify-price-by"><?php esc_html_e( '/month', 'imagify' ); ?></span>
									</span>

									<span class="imagify-recommend" aria-hidden="true"><?php esc_html_e( 'We recommend for you', 'imagify' ); ?></span>

									<p class="imagify-price-complement">
										<?php
										printf(
											/* translators: %s is a formatted price. */
											__( 'Unlimited upload size<br />Unlimited websites<br />%s', 'imagify' ),
											'<span class="imagify-price-add-data"></span>'
										);
										?>
									</p>

								</div><!-- .imagify-col-price -->

								<div class="imagify-col-other-actions imagify-choose-plan-col">
									<button type="button" class="button imagify-button-secondary mini imagify-payment-btn-select-plan"><?php esc_html_e( 'Choose plan', 'imagify' ); ?></button>
								</div>
							</div><!-- .imagify-offer-line --></script>
						</div><!-- .imagify-pricing-table -->

						<p class="imagify-footer-lines">You can upgrade, downgrade or cancel your plan at any time!</p>

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

		<div class="imagify-modal-loader"></div>
	</div><!-- .imagify-modal-content-->
</div><!-- .imagify-payment-modal -->
<?php
