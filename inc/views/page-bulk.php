<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>
<div class="wrap imagify-settings imagify-bulk">

	<?php $this->print_template( 'part-bulk-header' ); ?>

	<?php $this->print_template( 'part-new-to-imagify' ); ?>

	<?php $this->print_template( 'part-bulk-subtitle' ); ?>

	<div class="imagify-settings-section">

		<div class="imagify-columns">

			<div class="col-1-3 col-overview">
				<h3><?php esc_html_e( 'Overview', 'imagify' ); ?></h3>

				<div class="imagify-chart-container imagify-overview-chart-container">
					<canvas id="imagify-overview-chart" width="180" height="180"></canvas>
					<div id="imagify-overview-chart-percent" class="imagify-chart-percent"><?php echo esc_html( $data['optimized_attachments_percent'] ); ?><span>%</span></div>
				</div>
				<div id="imagify-overview-chart-legend"></div>

				<p class="imagify-global-optim-phrase imagify-clear">
					<?php
					printf(
						/* translators: %s is a percentage. */
						esc_html__( 'You optimized %s of your website\'s images', 'imagify' ),
						'<span class="imagify-total-percent">' . esc_html( $data['optimized_attachments_percent'] ) . '%</span>'
					);
					?>
				</p>
			</div>

			<div class="col-1-3 col-statistics">
				<h3><?php esc_html_e( 'Statistics', 'imagify' ); ?></h3>

				<div class="imagify-number-you-optimized">
					<p>
						<span id="imagify-total-optimized-attachments" class="number"><?php echo esc_html( number_format_i18n( $data['already_optimized_attachments'] ) ); ?></span>
						<span class="text">
							<?php
							printf(
								/* translators: you can use %s to include a line break. */
								esc_html__( 'that\'s the number of original images you optimized with Imagify', 'imagify' ),
								'<br>'
							);
							?>
						</span>
					</p>
				</div>

				<div class="imagify-bars">
					<p><?php esc_html_e( 'Original size', 'imagify' ); ?></p>
					<div class="imagify-bar-negative base-transparent right-outside-number">
						<div id="imagify-original-bar" class="imagify-progress" style="width: 100%"><span class="imagify-barnb"><?php echo esc_html( imagify_size_format( $data['original_human'], 1 ) ); ?></span></div>
					</div>

					<p><?php esc_html_e( 'Optimized size', 'imagify' ); ?></p>
					<div class="imagify-bar-positive base-transparent right-outside-number">
						<div id="imagify-optimized-bar" class="imagify-progress" style="width: <?php echo ( 100 - $data['optimized_percent'] ); ?>%"><span class="imagify-barnb"><?php echo esc_html( imagify_size_format( $data['optimized_human'], 1 ) ); ?></span></div>
					</div>

				</div>

				<div class="imagify-number-you-optimized">
					<p>
						<span id="imagify-total-optimized-attachments-pct" class="number"><?php echo esc_html( number_format_i18n( $data['optimized_percent'] ) ); ?>%</span>
						<span class="text">
							<?php
							printf(
								/* translators: %s is a line break. */
								esc_html__( 'that\'s the size you saved %sby using Imagify', 'imagify' ),
								'<br>'
							);
							?>
						</span>
					</p>
				</div>
			</div>

			<div class="col-1-3 col-informations">
				<h3><?php _e( 'Information', 'imagify' ); ?></h3>
				<ul class="imagify-list-infos">
					<li>
						<?php
						esc_html_e( 'Please be aware that optimizing a large number of images can take a while depending on your server and network speed.', 'imagify' );

						if ( get_transient( 'imagify_large_library' ) ) {
							printf(
								/* translators: %s is a formatted number. Don't use %d. */
								esc_html__( 'If you have more than %s images, you will need to launch the bulk optimization several times.' , 'imagify' ),
								esc_html( number_format_i18n( $data['unoptimized_attachment_limit'] ) )
							);
						}
						?>
					</li>
					<li>
						<?php esc_html_e( 'You must keep this page open while the bulk optimization is processing. If you leave you can come back to continue where it left off.', 'imagify' ); ?>
					</li>
					<li class="imagify-documentation-link-box">
						<span class="imagify-documentation-icon"><svg viewBox="0 0 15 20" xmlns="http://www.w3.org/2000/svg"><g fill="#40b1d0" fill-rule="nonzero"><g><path d="m14.583 20h-14.167c-.23 0-.417-.187-.417-.417v-14.167c0-.111.044-.217.122-.295l5-5c.078-.078.184-.122.295-.122h9.167c.23 0 .417.187.417.417v19.17c0 .23-.187.417-.417.417m-13.75-.833h13.333v-18.333h-8.578l-4.756 4.756v13.578"/><path d="m5.417 5.833h-5c-.23 0-.417-.187-.417-.417 0-.23.187-.417.417-.417h4.583v-4.583c0-.23.187-.417.417-.417.23 0 .417.187.417.417v5c0 .23-.187.417-.417.417"/></g><path d="m12.583 7h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 5h-4.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h4.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 10h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 13h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 15h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/><path d="m12.583 18h-9.167c-.23 0-.417-.224-.417-.5 0-.276.187-.5.417-.5h9.167c.23 0 .417.224.417.5 0 .276-.187.5-.417.5"/></g></svg></span>
						<span>
							<?php _e( 'Need help or have questions?', 'imagify' ); ?>
							<a class="imagify-documentation-link" href="<?php echo esc_url( imagify_get_external_url( 'documentation' ) ); ?>" target="_blank"><?php esc_html_e( 'Check our documentation.', 'imagify' ); ?></a>
						</span>
					</li>
				</ul>
			</div><!-- .col-1-2 -->
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
						echo '<em class="imagify-default-settings">(' . sprintf( esc_html__( 'Your default setting: %s', 'imagify' ), '&nbsp;<strong class="imagify-primary">' . esc_html( $default_level ) . '</strong>' ) . ')</em>';
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
								esc_html__( 'Don\'t forget to check %1$syour settings%2$s before bulk optimization.', 'imagify' ),
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
							<button id="imagify-bulk-action" type="button" class="button button-primary">
								<span class="dashicons dashicons-admin-generic"></span>
								<span class="button-text"><?php esc_html_e( 'Imagif\'em all', 'imagify' ); ?></span>
							</button>
						</p>
					</div>
					<?php if ( ! is_wp_error( get_imagify_max_image_size() ) ) { ?>
						<div class="imagify-cell imagify-pl0">
							<p class="imagify-info-block">
								<?php
								printf(
									/* translators: %s is a file size. */
									esc_html__( 'All images greater than %s will be optimized when using a paid plan.', 'imagify' ),
									esc_html( imagify_size_format( $data['max_image_size'] ) )
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

	foreach ( $data['groups'] as $group ) {
		$this->print_template( 'part-bulk-optimization-group', $group );
	}

	$this->print_template( 'modal-payment' );
	?>

</div>
<?php
