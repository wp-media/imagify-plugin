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
						<div class="imagify-account">
							<p class="imagify-meteo-title"><?php _e( 'Account status', 'imagify' ); ?></p>
							<p class="imagify-meteo-subs"><?php _e( 'Your subscription:', 'imagify' ); ?>&nbsp;<strong class="imagify-user-plan"><?php echo $user->plan_label; ?></strong></p>
						</div>
						<div class="imagify-account-link">
							<a href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" class="button button-ghost" target="_blank">
								<span class="dashicons dashicons-admin-users"></span>
								<span class="button-text"><?php _e( 'View My Subscription', 'imagify' ); ?></span>
							</a>
						</div>
					</div>

					<?php if ( 1 === $user->plan_id ) { ?>
					<div class="imagify-col-content">
						<div class="imagify-sep-v"></div>
						<div class="imagify-credit-left">
							<?php
							$unconsumed_quota  = $user->get_percent_unconsumed_quota();
							$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="37" height="38" alt="" />';
							$bar_class         = 'positive';
							$is_display_bubble = false;

							if ( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
								$bar_class  = 'neutral';
								$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="37" height="38" alt="" />';
							} elseif ( $unconsumed_quota <= 20 ) {
								$bar_class         = 'negative';
								$is_display_bubble = true;
								$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="38" height="36" alt="" />';
							}
							?>
							<span class="imagify-meteo-icon"><?php echo $meteo_icon; ?></span>
							<div class="imagify-space-left">

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
						</div>
					</div><!-- .imagify-col-content -->

					<?php } // End if(). ?>
				<?php } // End if(). ?>
			
			</div><!-- .imagify-account-info-col -->

		</div><!-- .imagify-columns -->
	</div><!-- .imagify-settings-section -->

	<div class="imagify-section imagify-section-gray">
		<div class="imagify-bulk-submit imagify-columns imagify-count">
			<div class="col-1-2">

				<?php if ( get_imagify_option( 'backup' ) ) { ?>

					<p class="imagify-count-title"><?php esc_html_e( 'Select Your Compression Level', 'imagify' ); ?>
						<?php
						$default_level      = __( 'Aggressive', 'imagify' );
						$optimization_level = get_imagify_option( 'optimization_level' );

						switch ( $optimization_level ) {
							case 2:
								$default_level = __( 'Ultra', 'imagify' );
								break;
							case 0:
								$default_level = __( 'Normal', 'imagify' );
						}

						/* translators: %s is an optimization level. */
						echo '<em class="imagify-default-settings">(' . sprintf( esc_html__( 'Your default setting: %s', 'imagify' ), '&nbsp;<strong class="imagify-primary">' . $default_level . '</strong>' ) . ')</em>';
						?>
					</p>
					<p class="imagify-inline-options">
						<input type="radio" id="imagify-optimization_level_normal" name="optimization_level" value="0" <?php checked( $optimization_level, 0 ); ?>>
						<label for="imagify-optimization_level_normal">
							<?php esc_html_e( 'Normal', 'imagify' ); ?>
						</label>

						<input type="radio" id="imagify-optimization_level_aggro" name="optimization_level" value="1" <?php checked( $optimization_level, 1 ); ?>>
						<label for="imagify-optimization_level_aggro">
							<?php esc_html_e( 'Aggressive', 'imagify' ); ?>
						</label>

						<input type="radio" id="imagify-optimization_level_ultra" name="optimization_level" value="2" <?php checked( $optimization_level, 2 ); ?>>
						<label for="imagify-optimization_level_ultra">
							<?php esc_html_e( 'Ultra', 'imagify' ); ?>
						</label>
					</p>

				<?php } else { ?>

					<p>
						<strong>
							<?php
							printf(
								/* translators: 1 is the opening of a link, 2 is the closing of this link. */
								__( 'Don\'t forget to check %1$syour settings%2$s before bulk optimization.', 'imagify' ),
								'<a href="' . esc_url( get_imagify_admin_url() ) . '">',
								'</a>'
							);
							?>
						</strong>
					</p>

				<?php } // End if(). ?>

			</div>
			<div class="col-1-2">
				<p class="imagify-count-title"><?php esc_html_e( 'Let\'s go!', 'imagify' ); ?></p>
				<div class="imagify-table">
					<div class="imagify-cell imagify-pl0">
						<p>
							<?php wp_nonce_field( 'imagify-bulk-upload', 'imagifybulkuploadnonce' ); ?>
							<button id="imagify-bulk-action" type="button" class="button button-primary">
								<span class="dashicons dashicons-admin-generic"></span>
								<span class="button-text"><?php _e( 'Imagif\'em all', 'imagify' ); ?></span>
							</button>
						</p>
					</div>
					<?php if ( ! is_wp_error( get_imagify_max_image_size() ) ) { ?>
						<div class="imagify-cell imagify-pl0">
							<p class="imagify-info-block">
								<?php
								printf(
									/* translators: %s is a file size. */
									__( 'All images greater than %s will be optimized when using a paid plan.', 'imagify' ),
									imagify_size_format( get_imagify_max_image_size() )
								);
								?>
							</p>
						</div>
					<?php } ?>
				</div>
			</div>
		</div><!-- .imagify-bulk-submit -->
	</div>

	<?php
	$this->print_template( 'part-bulk-success' );

	$total_saving_data = imagify_count_saving_data();

	$groups = array(
		array(
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
		array(
			'icon'     => 'admin-plugins',
			'title'    => __( 'Optimize the images of your Themes and Plugins', 'imagify' ),
			'subtitle' => __( 'Choose here the bulk optimization settings for the medias stored in your themes and plugins.', 'imagify' ),
			/* translators: 1 is the opening of a link, 2 is the closing of this link. */
			'footer'   => sprintf( __( 'You can re-optimize your images more finely directly in the %1$simages management%2$s.', 'imagify' ), '<a href="' . esc_url( get_imagify_admin_url( 'files-list' ) ) . '">', '</a>' ),
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
		),
	);

	foreach ( $groups as $group ) {
		$this->print_template( 'part-bulk-optimization-group', $group );
	}

	$this->print_template( 'modal-payment' );
	?>

</div>
<?php
