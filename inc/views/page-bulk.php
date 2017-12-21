<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
$user = new Imagify_User();
?>
<div class="wrap imagify-settings imagify-bulk">

	<?php $this->print_template( 'part-bulk-header' ); ?>

	<div class="imagify-settings-section">

		<div class="imagify-columns">

			<div class="imagify-col col-overview">
				<h2 class="imagify-h2-like">
					<span class="dashicons dashicons-chart-line"></span>
					<?php _e( 'Overview', 'imagify' ); ?>
				</h2>
				
				<div class="imagify-columns">
					<div class="imagify-col col-statistics">
						<h3 class="screen-reader-text"><?php _e( 'Statistics', 'imagify' ); ?></h3>

						<?php
						$total_saving_data = imagify_count_saving_data();
						$optimized_percent = $total_saving_data['percent'];
						$optimized_nb      = $total_saving_data['optimized_size'];
						$original_nb       = $total_saving_data['original_size'];
						?>

						<div class="imagify-number-you-optimized">
							<p>
								<span id="imagify-total-optimized-attachments" class="number"><?php echo number_format_i18n( $total_saving_data['count'] ); ?></span>
								<span class="text">
									<?php
									printf(
										/* translators: you can use %s to include a line break. */
										__( 'that\'s the number of original images%s you optimized with Imagify', 'imagify' ),
										'<br>'
									);
									?>
								</span>
							</p>
						</div>

						<div class="imagify-bars">
							<p><?php _e( 'Original size', 'imagify' ); ?></p>
							<div class="imagify-bar-negative base-transparent right-outside-number">
								<div id="imagify-original-bar" class="imagify-progress" style="width: 100%"><span class="imagify-barnb"><?php echo imagify_size_format( $original_nb, 1 ); ?></span></div>
							</div>

							<p><?php _e( 'Optimized size', 'imagify' ); ?></p>
							<div class="imagify-bar-primary base-transparent right-outside-number">
								<div id="imagify-optimized-bar" class="imagify-progress" style="width: <?php echo ( 100 - $optimized_percent ); ?>%"><span class="imagify-barnb"><?php echo imagify_size_format( $optimized_nb, 1 ); ?></span></div>
							</div>

						</div>

						<div class="imagify-number-you-optimized">
							<p>
								<span id="imagify-total-optimized-attachments-pct" class="number"><?php echo number_format_i18n( $optimized_percent ); ?>%</span>
								<span class="text">
									<?php
									printf(
										/* translators: %s is a line break. */
										__( 'that\'s the size you saved %sby using Imagify', 'imagify' ),
										'<br>'
									);
									?>
								</span>
							</p>
						</div>
					</div><!-- .imagify-col.col-statistics -->

					<div class="imagify-col col-chart">
						<div class="imagify-chart-container imagify-overview-chart-container">
							<canvas id="imagify-overview-chart" width="180" height="180"></canvas>
							<div id="imagify-overview-chart-percent" class="imagify-chart-percent"><?php echo imagify_percent_optimized_attachments(); ?><span>%</span></div>
						</div>
						<div id="imagify-overview-chart-legend"></div>

						<p class="imagify-global-optim-phrase imagify-clear">
							<?php
							printf(
								/* translators: %s is a percentage. */
								esc_html__( 'You optimized %s of your website\'s images', 'imagify' ),
								'<span class="imagify-total-percent">' . imagify_percent_optimized_attachments() . '%</span>'
							);
							?>
						</p>
					</div><!-- .imagify-col -->
				</div>
			</div><!-- .imagify-col.col-overview -->

			<div class="imagify-col imagify-account-info-col">

				<?php if ( ! defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) || false === IMAGIFY_HIDDEN_ACCOUNT ) { ?>
					<div class="imagify-options-title">
						<p class="imagify-meteo-title">
							<span class="dashicons dashicons-admin-users"></span>
							<?php _e( 'Account status', 'imagify' ); ?>
						</p>

						<a href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php _e( 'View My Subscription', 'imagify' ); ?></a>

						<p class="imagify-meteo-subs">
							<span class="screen-reader-text"><?php _e( 'Your subscription:', 'imagify' ); ?></span>
							<strong class="imagify-user-plan imagify-user-plan-label"><?php echo $user->plan_label; ?></strong>
						</p>
					</div>

					<?php if ( 1 === $user->plan_id ) { ?>
					<div class="imagify-col-content">
						<?php
						$unconsumed_quota  = $user->get_percent_unconsumed_quota();
						$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="63" height="64" alt="" />';
						$bar_class         = 'positive';
						$is_display_bubble = false;

						if ( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
							$bar_class  = 'neutral';
							$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="63" height="64" alt="" />';
						} elseif ( $unconsumed_quota <= 20 ) {
							$bar_class         = 'negative';
							$is_display_bubble = true;
							$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="64" height="63" alt="" />';
						}
						?>
						<div class="imagify-flex imagify-vcenter">
							<span class="imagify-meteo-icon imagify-noshrink"><?php echo $meteo_icon; ?></span>
							<div class="imagify-space-left imagify-full-width">

								<p>
									<?php
									printf(
										/* translators: %s is a data quota. */
										__( 'You have %s space credit left' , 'imagify' ),
										'<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>'
									);
									?>
								</p>

								<div class="imagify-bar-<?php echo $bar_class; ?>">
									<div class="imagify-unconsumed-bar imagify-progress" style="width: <?php echo $unconsumed_quota . '%'; ?>;"></div>
								</div>
							</div>
						</div>
						<div class="imagify-space-tooltips imagify-tooltips <?php echo ( ! $is_display_bubble ) ? 'hidden' : ''; ?>">
							<div class="tooltip-content tooltip-table">
								<div class="cell-icon">
									<span aria-hidden="true" class="icon icon-round">i</span>
								</div>
								<div class="cell-text">
									<?php _e( 'Upgrade your account to continue optimizing your images', 'imagify' ); ?>
								</div>
								<div class="cell-sep"></div>
								<div class="cell-cta">
									<a href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php _e( 'More info', 'imagify' ); ?></a>
								</div>
							</div>
						</div>

						<div class="imagify-divider"></div>
						
						<?php 
						$show_new = apply_filters( 'imagify_show_new_to_imagify', true );
						if ( $show_new ) { ?>

						<p class="imagify-section-title imagify-h3-like"><?php _e( 'You\'re new to Imagify?', 'imagify' ); ?></p>
						<p><?php _e( 'Let us help you by analyzing your existing images and determine the best plan for you.', 'imagify' ); ?></p>

						<button id="imagify-get-pricing-modal" data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-secondary imagify-button-big">
							<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
							<span class="button-text"><?php _e( 'What plan do I need?', 'imagify' ); ?></span>
						</button>

						<?php } ?>

					</div><!-- .imagify-col-content -->

					<?php } // End if(). ?>
				<?php } // End if(). ?>
			
			</div><!-- .imagify-account-info-col -->

		</div><!-- .imagify-columns -->

	<?php
	$this->print_template( 'part-bulk-success' );

	$total_saving_data = imagify_count_saving_data();

	$groups = array(
		array(
			'id'      => 'media-library',
			'icon'     => 'images-alt2',
			'title'    => __( 'Optimize the images of your Media Library', 'imagify' ),
			'subtitle' => __( 'Choose here the bulk optimization settings for the medias stored in the WordPress\' Library.', 'imagify' ),
			/* translators: 1 is the opening of a link, 2 is the closing of this link. */
			'footer'   => sprintf( __( 'You can re-optimize your images more finely directly in your %1$sMedia Library%2$s.', 'imagify' ), '<a href="' . esc_url( admin_url( 'upload.php' ) ) . '">', '</a>' ),
			'rows'     => array(
				'library' => array(
					'title'            => __( 'Media Library', 'imagify' ),
					'optimized_images' => imagify_count_optimized_attachments(),
					'errors'           => imagify_count_error_attachments(),
					'errors_url'       => add_query_arg( array(
							'mode'           => 'list',
							'imagify-status' => 'errors',
						), admin_url( 'upload.php' ) ),
					'optimized_size'   => $total_saving_data['optimized_size'],
					'original_size'    => $total_saving_data['original_size'],
				),
			),
		),
		/*array(
			'id'      => 'custom-folders',
			'icon'     => 'admin-plugins',
			'title'    => __( 'Optimize the images of your Themes and Plugins', 'imagify' ),
			'subtitle' => __( 'Choose here the bulk optimization settings for the medias stored in your themes and plugins.', 'imagify' ),
			/* translators: 1 is the opening of a link, 2 is the closing of this link. */
			/*'footer'   => sprintf( __( 'You can re-optimize your images more finely directly in the %1$simages management%2$s.', 'imagify' ), '<a href="' . esc_url( get_imagify_admin_url( 'files-list' ) ) . '">', '</a>' ),
			'rows'     => array(
				'themes'         => array(
					'title'            => __( 'Themes', 'imagify' ),
					'optimized_images' => 54634,
					'errors'           => 1,
					'errors_url'       => add_query_arg( array(
							'folder-type-filter' => 'themes',
							'status'             => 'error',
						), get_imagify_admin_url( 'files-list' ) ),
					'optimized_size'   => 12453000,
					'original_size'    => 12453000,
				),
				'plugins'        => array(
					'title'            => __( 'Plugins', 'imagify' ),
					'optimized_images' => 54634,
					'errors'           => 4,
					'errors_url'       => add_query_arg( array(
							'folder-type-filter' => 'plugins',
							'status'             => 'error',
						), get_imagify_admin_url( 'files-list' ) ),
					'optimized_size'   => 12453000,
					'original_size'    => 12453000,
				),
				'custom-folders' => array(
					'title'            => __( 'Custom Folders', 'imagify' ),
					'optimized_images' => 54634,
					'errors'           => 0,
					'errors_url'       => add_query_arg( array(
							'folder-type-filter' => 'custom-folders',
							'status'             => 'error',
						), get_imagify_admin_url( 'files-list' ) ),
					'optimized_size'   => 12453000,
					'original_size'    => 12453000,
				),
			),
		),*/
	);



	foreach ( $groups as $group ) {
		$this->print_template( 'part-bulk-optimization-group', $group );
	}

	//TODO: find a way to know when user didn't select Custom Folders
	if ( count( $groups ) === 1 ) {
		$this->print_template( 'part-bulk-optimization-newbie' );
	}
	?>

		<div class="imagify-bulk-submit imagify-flex imagify-vcenter">
				<div class="imagify-pr2">
					<p>
						<?php wp_nonce_field( 'imagify-bulk-upload', 'imagifybulkuploadnonce' ); ?>
						<button id="imagify-bulk-action" type="button" class="button button-primary">
							<span class="dashicons dashicons-admin-generic"></span>
							<span class="button-text"><?php _e( 'Imagif\'em all', 'imagify' ); ?></span>
						</button>
					</p>
				</div>
				<?php if ( ! is_wp_error( get_imagify_max_image_size() ) ) { ?>
					<p>
						<?php
						printf(
							/* translators: %s is a file size. */
							__( 'All images greater than %s will be optimized when using a paid plan.', 'imagify' ),
							imagify_size_format( get_imagify_max_image_size() )
						);
						?>
					</p>
				<?php } ?>
			</div><!-- .imagify-bulk-submit -->
	</div><!-- .imagify-settings-section -->

	<?php $this->print_template( 'modal-payment' ); ?>

</div>
<?php
