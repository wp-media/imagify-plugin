<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * The main settings page construtor using the required functions from WP
 *
 * @since 1.0
 */
function _imagify_display_bulk_page() { 
	$user = new Imagify_User();
	?>
	<div class="wrap imagify-settings imagify-bulk">
		<div class="imagify-title">
			<?php if ( ! defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) || false === IMAGIFY_HIDDEN_ACCOUNT ) { ?>	
			<div class="imagify-title-right">
				<div class="imagify-account">
					<p class="imagify-meteo-title"><?php _e( 'Account status', 'imagify' ); ?></p>
					<p class="imagify-meteo-subs"><?php _e( 'Your subscription:', 'imagify' ); ?>&nbsp;<strong class="imagify-user-plan"><?php echo $user->plan_label; ?></strong></p>
				</div>
				<div class="imagify-account-link">
					<a href="<?php echo IMAGIFY_APP_MAIN; ?>/#/subscription" class="button button-ghost" target="_blank">
						<span class="dashicons dashicons-admin-users"></span>
						<span class="button-text"><?php _e( 'View My Subscription', 'imagify' ); ?></span>
					</a>
				</div>
				
				<?php if ( 1 === $user->plan_id ) { ?>
				<div class="imagify-sep-v"></div>
				<div class="imagify-credit-left">
					<?php 
					$unconsumed_quota  = $user->get_percent_unconsumed_quota();
					$meteo_icon 	   =  '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="37" height="38" alt="" />'; 
					$bar_class         = 'positive';
					$is_display_bubble = false;
					
					if( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
						$bar_class  = 'neutral';
						$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="37" height="38" alt="" />';
					} elseif( $unconsumed_quota <= 20 ) {
						$bar_class         = 'negative';
						$is_display_bubble = true;
						$meteo_icon 	   = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="38" height="36" alt="" />';
					}
					?>
					<span class="imagify-meteo-icon"><?php echo $meteo_icon; ?></span>
					<div class="imagify-space-left">
						
						<p><?php printf( __( 'You have %s space credit left' , 'imagify' ), '<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>' ); ?></p>
						
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
								<a href="<?php echo IMAGIFY_APP_MAIN; ?>/#/subscription" target="_blank"><?php _e( 'More info', 'imagify' ); ?></a>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<?php } ?>
			
			<img width="225" height="26" alt="Imagify" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" class="imagify-logo" />
		</div>

		<?php //echo get_imagify_new_to_imagify(); ?>
		
		<div class="imagify-sub-title">
			<svg class="icon icon-bulk" viewBox="0 0 38 36" width="38" height="36" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="m233.09 177.21l-5.52 10.248c-.08.145-.018.272-.023.388-.074.193-.033.4-.033.619v21.615c0 .952.601 1.429 1.552 1.429h33.897c.952 0 1.962-.478 1.962-1.429v-21.615c0-.487-.323-.925-.649-1.24l-5.623-9.976c-.405-.726-1.202-1.179-2.034-1.182l-21.486-.068c-.849 0-1.64.464-2.043 1.211m30.424 32.869c0 .173-.378.018-.551.018h-33.897c-.172 0-.14.155-.14-.018v-21.576l33.961-.281c.066.008.186.09.263.128.054.027.205.049.258.073.002.014.106.027.106.041v21.615m-6.153-32.11l4.91 8.835h-14.992v-9.354l9.306.045c.322.001.619.192.776.474m-11.494-.523v9.358h-16.306l4.773-8.892c.155-.289.456-.484.787-.484l10.746.018m7.06 17.12c0 .39-.316.706-.706.706h-12.706c-.39 0-.706-.316-.706-.706 0-.39.316-.706.706-.706h12.706c.39 0 .706.316.706.706" transform="translate(-227-176)" fill="#7a8996"/></g></svg>
			<span class="title-text">
				<?php _e( 'Bulk Optimization', 'imagify' ); ?>
				<small><sup><?php echo IMAGIFY_VERSION; ?></sup></small>
			</span>
		</div>

		<div class="imagify-settings-section">

			<div class="imagify-columns">

				<div class="col-1-3 col-overview">
					<h3><?php _e( 'Overview', 'imagify' ); ?></h3>
				
					<div class="imagify-chart-container">
						<canvas id="imagify-overview-chart" width="180" height="180"></canvas>
						<div id="imagify-overview-chart-percent" class="imagify-chart-percent"><?php echo imagify_percent_optimized_attachments(); ?><span>%</span></div>
					</div>
					<div id="imagify-overview-chart-legend"></div>

					<p class="imagify-global-optim-phrase imagify-clear"><?php printf( esc_html__( 'You optimized %s images of your website', 'imagify' ), '<span class="imagify-total-percent">' . imagify_percent_optimized_attachments() . '%</span>' ); ?></p>
				</div>

				<div class="col-1-3 col-statistics">
					<h3><?php _e( 'Statistics', 'imagify' ); ?></h3>
					
					<?php
					$total_saving_data  = imagify_count_saving_data();
					$optimized_percent 	= $total_saving_data['percent'];
					$optimized_nb 		= $total_saving_data['optimized_size'];
					$original_nb 		= $total_saving_data['original_size'];
					?>
					
					<div class="imagify-number-you-optimized">
						<p>
							<span id="imagify-total-optimized-attachments" class="number"><?php echo number_format_i18n( $total_saving_data['count'] ); ?></span>
							<span class="text"><?php printf( __( 'that\'s the number of images you optimized with Imagify', 'imagify' ), '<br>' ); ?></span>
						</p>
					</div>

					<div class="imagify-bars">
						<p><?php _e( 'Original size', 'imagify' ); ?></p>
						<div class="imagify-bar-negative base-transparent right-outside-number">
							<div id="imagify-original-bar" class="imagify-progress" style="width: 100%"><span class="imagify-barnb"><?php echo size_format( $original_nb, 1 ); ?></span></div>
						</div>

						<p><?php _e( 'Optimized size', 'imagify' ); ?></p>
						<div class="imagify-bar-positive base-transparent right-outside-number">
							
							<div id="imagify-optimized-bar" class="imagify-progress" style="width: <?php echo $optimized_percent; ?>%"><span class="imagify-barnb"><?php echo size_format( $optimized_nb, 1 ); ?></span></div>
						</div>

					</div>

					<div class="imagify-number-you-optimized">
						<p>
							<span id="imagify-total-optimized-attachments-pct" class="number"><?php echo number_format_i18n( $optimized_percent ); ?>%</span>
							<span class="text"><?php printf( __( 'that\'s the size you saved %sby using Imagify', 'imagify' ), '<br>' ); ?></span>
						</p>
					</div>
				</div>

				<div class="col-1-3 col-informations">
					<h3><?php _e( 'Information', 'imagify' ); ?></h3>
					<ul class="imagify-list-infos">
						<li>
						<?php
						esc_html_e( 'Please be aware that optimizing a large number of images can take a while depending on your server and network speed.', 'imagify' );

						if ( get_transient( IMAGIFY_SLUG . '_large_library' ) ) {
							printf( __( 'If you have more than %s images, you will need to launch the bulk optimization several times.' , 'imagify' ), number_format_i18n( apply_filters( 'imagify_unoptimized_attachment_limit', 10000 ) ) );
						}
						?>
						</li>
						<li><?php esc_html_e( 'You must keep this page open while the bulk optimizaton is processing. If you leave you can come back to continue where it left off.', 'imagify' ); ?></li>
					</ul>
				</div><!-- .col-1-2 -->
			</div><!-- .imagify-columns -->
		</div><!-- .imagify-settings-section -->

		<div class="imagify-section imagify-section-gray">
			<div class="imagify-bulk-submit imagify-columns imagify-count">
				<div class="col-1-2">
				<?php if ( get_imagify_option( 'backup', 0 ) == "1" ) { ?>

					<p class="imagify-count-title"><?php esc_html_e( 'Select Your Compression Level', 'imagify' ); ?>
						<?php 
							$default_set = esc_html__( 'Ultra', 'imagify' );
							switch( get_imagify_option( 'optimization_level' ) ) {
								case '1':
									$default_set = esc_html__( 'Aggressive', 'imagify' );
									break;
								case '0':
									$default_set = esc_html__( 'Normal', 'imagify' );
									break;
							}

							echo '<em class="imagify-default-settings">(' . sprintf( esc_html__( 'Your default setting: %s', 'imagify' ), '&nbsp;<strong class="imagify-primary">' . $default_set . '</strong>' ) . ')</em>';
						?>
					</p>
					<p class="imagify-inline-options">
						<input type="radio" id="imagify-optimization_level_normal" name="optimization_level" value="0" <?php checked( get_imagify_option( 'optimization_level' ), 0 ); ?>>
						<label for="imagify-optimization_level_normal">
							<?php esc_html_e( 'Normal', 'imagify' ); ?>
						</label>

						<input type="radio" id="imagify-optimization_level_aggro" name="optimization_level" value="1" <?php checked( get_imagify_option( 'optimization_level' ), 1 ); ?>>
						<label for="imagify-optimization_level_aggro">
							<?php esc_html_e( 'Aggressive', 'imagify' ); ?>
						</label>

						<input type="radio" id="imagify-optimization_level_ultra" name="optimization_level" value="2" <?php checked( get_imagify_option( 'optimization_level' ), 2 ); ?>>
						<label for="imagify-optimization_level_ultra">
							<?php esc_html_e( 'Ultra', 'imagify' ); ?>
						</label>
					</p>

				<?php 
				}
				else {
				?>
					<p>
						<strong><?php printf( __( 'Don\'t forget to check %syour settings%s before bulk optimization.', 'imagify' ), '<a href="' . get_imagify_admin_url() . '">', '</a>' ); ?></strong>
					</p>
				<?php
				}
				?>
				</div>
				<div class="col-1-2">
					<p class="imagify-count-title"><?php esc_html_e( 'Let\'s go!', 'imagify' ); ?></p>
					<div class="imagify-table">
						<div class="imagify-cell imagify-pl0">
							<p>
								<?php wp_nonce_field( 'imagify-bulk-upload', 'imagifybulkuploadnonce' ); ?>
								<button id="imagify-bulk-action" type="button" class="button button-primary">
									<span class="dashicons dashicons-admin-generic"></span>
									<span class="button-text"><?php _e( 'Imagif\'em all', 'imagify'); ?></span>
								</button>
							</p>
						</div>
						<div class="imagify-cell imagify-pl0">
							<p class="imagify-info-block"><?php printf( __( 'All images greater than %s will be optimized when using a paying monthly plan.', 'imagify' ), size_format( 5000000 ) ); ?></p>
						</div>
					</div>
				</div>
			</div><!-- .imagify-bulk-submit -->
		</div>
		
		<!-- The Success/Complete bar -->
		<div class="imagify-row-complete hidden" aria-hidden="true">
			<div class="imagify-all-complete">
				<div class="imagify-ac-report">
					<div class="imagify-ac-chart" data-percent="0">
						<span class="imagify-chart">
							<span class="imagify-chart-container">
								<canvas width="46" height="46"></canvas>
							</span>
						</span>
					</div>
					<div class="imagify-ac-report-text">
						<p class="imagify-ac-rt-big"><?php _e( 'Well done!', 'imagify' ); ?></p>
						<p><?php printf( __( 'you saved %1$s out of %2$s', 'imagify' ), '<strong class="imagify-ac-rt-total-gain"></strong>', '<strong class="imagify-ac-rt-total-original"></strong>' ); ?></p>
					</div>
				</div>
				<div class="imagify-ac-share">
					<div class="imagify-ac-share-content">
						<p><?php _e( 'Share your awesome result', 'imagify' ); ?></p>
						<ul class="imagify-share-networks">
							<li>
								<a target="_blank" class="imagify-sn-twitter" href=""><svg viewBox="0 0 23 18" width="23" height="18" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><title>Twitter</title><path d="m244.15 12.13c-.815.361-1.691.606-2.61.716.939-.563 1.659-1.453 1.998-2.514-.878.521-1.851.898-2.886 1.103-.829-.883-2.01-1.435-3.317-1.435-2.51 0-4.544 2.034-4.544 4.544 0 .356.04.703.118 1.035-3.777-.19-7.125-1.999-9.367-4.748-.391.671-.615 1.452-.615 2.285 0 1.576.802 2.967 2.02 3.782-.745-.024-1.445-.228-2.058-.568-.001.019-.001.038-.001.057 0 2.202 1.566 4.04 3.646 4.456-.381.104-.783.159-1.197.159-.293 0-.577-.028-.855-.081.578 1.805 2.256 3.119 4.245 3.156-1.555 1.219-3.515 1.945-5.644 1.945-.367 0-.728-.021-1.084-.063 2.01 1.289 4.399 2.041 6.966 2.041 8.359 0 12.929-6.925 12.929-12.929 0-.197-.004-.393-.013-.588.888-.64 1.658-1.44 2.268-2.352" transform="translate(-222-10)" fill="#fff"/></g></svg></a>
							</li>
							<li>
								<a target="_blank" class="imagify-sn-twitter-facebook" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=https://wordpress.org/plugins/imagify' ); ?>"><svg viewBox="0 0 18 18" width="18" height="18" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><title>Facebook</title><path d="m203.25 10h-16.5c-.415 0-.75.336-.75.75v16.5c0 .414.336.75.75.75h8.812v-6.75h-2.25v-2.813h2.25v-2.25c0-2.325 1.472-3.469 3.546-3.469.993 0 1.847.074 2.096.107v2.43h-1.438c-1.128 0-1.391.536-1.391 1.322v1.859h2.813l-.563 2.813h-2.25l.045 6.75h4.83c.414 0 .75-.336.75-.75v-16.5c0-.414-.336-.75-.75-.75" transform="translate(-186-10)" fill="#fff"/></g></svg></a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="imagify-bulk-table">
			<table summary="<?php _e( 'Compression process results', 'imagify' ); ?>">
				<thead>
					<tr>
						<th class="imagify-cell-filename"><?php _e( 'Filename', 'imagify' ); ?></th>
						<th class="imagify-cell-status"><?php _e( 'Status', 'imagify' ); ?></th>
						<th class="imagify-cell-original"><?php _e( 'Original', 'imagify' ); ?></th>
						<th class="imagify-cell-optimized"><?php _e( 'Optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-percentage"><?php _e( 'Percentage', 'imagify' ); ?></th>
						<th class="imagify-cell-thumbnails"><?php _e( 'Thumbnails optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-savings"><?php _e( 'Overall saving', 'imagify' ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<td class="imagify-cell-nb-files"><span class="imagify-nb-files">0</span> <?php _e( 'file', 'imagify' ); ?></td>
						<td class="imagify-cell-errors"><span class="imagify-nb-errors">0</span> <?php _e( 'error', 'imagify' ); ?></td>
						<td class="imagify-cell-totaloriginal" colspan="4"><?php _e( 'Total:', 'imagify' ); ?> <strong><span class="imagify-total-original">0Mb</span></strong></td>
						<td class="imagify-cell-totalgain"><?php _e( 'Gain:', 'imagify' ); ?> <strong><span class="imagify-total-gain">0Mb</span></strong></td>
					</tr>
				</tfoot>
				<tbody>
					<!-- The progress bar -->
					<tr aria-hidden="true" class="imagify-row-progress hidden">
						<td colspan="7">
							<div class="media-item">
								<div class="progress">
									<div id="imagify-progress-bar" class="bar"><div class="percent">0%</div></div>
								</div>
							</div>
						</td>
					</tr>
					<!-- No image uploaded yet -->
					<tr class="imagify-no-uploaded-yet">
						<td colspan="7">
							<p><?php printf( __( '%sStart the bulk optimization%s', 'imagify' ), '<a id="imagify-simulate-bulk-action" href="#">', '</a>' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php //imagify_payment_modal(); ?>
		
	</div>
	<?php
}