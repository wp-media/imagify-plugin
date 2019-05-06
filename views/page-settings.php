<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$settings     = Imagify_Settings::get_instance();
$options      = Imagify_Options::get_instance();
$option_name  = $options->get_option_name();
$hidden_class = Imagify_Requirements::is_api_key_valid() ? '' : ' hidden';
$lang         = imagify_get_current_lang_in( array( 'de', 'es', 'fr', 'it' ) );

/* Ads notice */
$notice  = 'wp-rocket';
$user_id = get_current_user_id();
$notices = get_user_meta( $user_id, '_imagify_ignore_ads', true );
$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();
$wrapper_class = isset( $notices[ $notice ] ) || defined( 'WP_ROCKET_VERSION' ) ? 'imagify-have-rocket' : 'imagify-dont-have-rocket';
?>
<div class="wrap imagify-settings <?php echo $wrapper_class; ?> imagify-clearfix">

	<div class="imagify-col imagify-main">

		<?php $this->print_template( 'part-settings-header' ); ?>

		<form action="<?php echo esc_url( $settings->get_form_action() ); ?>" id="imagify-settings" method="post">

			<div class="imagify-settings-main-content<?php echo Imagify_Requirements::is_api_key_valid() ? '' : ' imagify-no-api-key'; ?>">

				<?php settings_fields( $settings->get_settings_group() ); ?>
				<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
				<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>

				<?php
				if ( ! Imagify_Requirements::is_api_key_valid() ) {
					$this->print_template( 'part-settings-account' );
					$this->print_template( 'part-settings-footer' );
				}
				?>

				<div class="imagify-col imagify-shared-with-account-col<?php echo $hidden_class; ?>">
					<div class="imagify-settings-section">

						<h2 class="imagify-options-title"><?php _e( 'General Settings', 'imagify' ); ?></h2>

						<p class="imagify-options-subtitle" id="imagify-optimization-level-label">
							<?php _e( 'Optimization Level', 'imagify' ); ?>

							<span class="imagify-info">
								<span class="dashicons dashicons-info"></span>
								<a href="#imagify-more-info" class="imagify-modal-trigger"><?php _e( 'More info?', 'imagify' ); ?></a>
							</span>
						</p>

						<div class="imagify-setting-optim-level">
							<p class="imagify-inline-options">
								<input type="radio" id="imagify-optimization_level_normal" name="<?php echo $option_name; ?>[optimization_level]" value="0" <?php checked( $options->get( 'optimization_level' ), 0 ); ?> aria-describedby="imagify-optimization-level-label">
								<label for="imagify-optimization_level_normal">
									<?php _e( 'Normal', 'imagify' ); ?>
								</label>

								<input type="radio" id="imagify-optimization_level_aggro" name="<?php echo $option_name; ?>[optimization_level]" value="1" <?php checked( $options->get( 'optimization_level' ), 1 ); ?>  aria-describedby="imagify-optimization-level-label">
								<label for="imagify-optimization_level_aggro">
									<?php _e( 'Aggressive', 'imagify' ); ?>
								</label>

								<input type="radio" id="imagify-optimization_level_ultra" name="<?php echo $option_name; ?>[optimization_level]" value="2" <?php checked( $options->get( 'optimization_level' ), 2 ); ?> aria-describedby="imagify-optimization-level-label">
								<label for="imagify-optimization_level_ultra">
									<?php _e( 'Ultra', 'imagify' ); ?>
								</label>
							</p>

							<p class="imagify-visual-comparison-text">
								<?php
								printf(
									/* translators: 1 is a button tag start, 2 is the button tag end. */
									__( 'Need help to choose? %1$sTry the Visual Comparison%2$s', 'imagify' ),
									'<button type="button" class="button button-primary button-mini-flat imagify-visual-comparison-btn imagify-modal-trigger" data-target="#imagify-visual-comparison">',
									'</button>'
								);
								?>
							</p>
						</div>

						<p class="imagify-setting-line">
						<?php
						$settings->field_checkbox( array(
							'option_name' => 'auto_optimize',
							'label'       => __( 'Auto-Optimize images on upload', 'imagify' ),
							'info'        => __( 'Automatically optimize every image you upload to WordPress.', 'imagify' ),
						) );
						?>
						</p>

						<p class="imagify-setting-line">
							<?php
							$settings->field_checkbox( array(
								'option_name' => 'backup',
								'label'       => __( 'Backup original images', 'imagify' ),
								'info'        => __( 'Keep your original images in a separate folder before optimization process.', 'imagify' ),
							) );

							$backup_error_class = $options->get( 'backup' ) && ! Imagify_Requirements::attachments_backup_dir_is_writable() ? '' : ' hidden';
							?>
							<br/><strong id="backup-dir-is-writable" class="imagify-error<?php echo $backup_error_class; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'imagify_check_backup_dir_is_writable' ) ); ?>">
								<?php
								$backup_path = $this->filesystem->make_path_relative( get_imagify_backup_dir_path( true ) );
								/* translators: %s is a file path. */
								printf( __( 'The backup folder %s cannot be created or is not writable by the server, original images cannot be saved!', 'imagify' ), "<code>$backup_path</code>" );
								?>
							</strong>
						</p>

						<p class="imagify-setting-line">
							<?php
							$info  = __( 'EXIF data is information stored in your pictures like shutter speed, exposure compensation, ISO, etc...', 'imagify' );
							$info .= ' <a href="' . esc_url( imagify_get_external_url( 'exif' ) ) . '" target="_blank">' . __( 'Learn more', 'imagify' ) . '</a><br/><br/>';
							$info .= __( 'If you are a photographer, you may be interested in this option if you are displaying info like the model of your camera on your pages. Also, keeping EXIF data can fix some colorimetric problems.', 'imagify' );

							$settings->field_checkbox( array(
								'option_name' => 'exif',
								'label'       => __( 'Keep all EXIF data from your images', 'imagify' ),
								'info'        => $info,
							) );
							?>
						</p>
					</div>
				</div>

				<?php if ( Imagify_Requirements::is_api_key_valid() ) { ?>
					<div class="imagify-col imagify-account-info-col">
						<?php $this->print_template( 'part-settings-account' ); ?>
					</div>
				<?php } ?>
			</div>

			<div class="imagify-settings-main-content<?php echo $hidden_class; ?>">

				<div class="imagify-settings-section imagify-clear">
					<h2 class="imagify-options-title"><?php _e( 'Optimization', 'imagify' ); ?></h2>
					<?php
					$this->print_template( 'part-settings-webp' );
					$this->print_template( 'part-settings-library' );
					$this->print_template( 'part-settings-custom-folders' );
					?>
				</div>
			</div>

			<div class="imagify-settings-main-content imagify-pb0<?php echo $hidden_class; ?>">
				<div class="imagify-settings-section imagify-clear">
					<div class="imagify-col">
						<h2 class="imagify-options-title"><?php _e( 'Display Options', 'imagify' ); ?></h2>

						<p class="imagify-options-subtitle"><?php _e( 'Show Toolbar Menu', 'imagify' ); ?></p>

						<div class="imagify-col">
							<p>
							<?php
							$settings->field_checkbox( array(
								'option_name' => 'admin_bar_menu',
								'label'       => __( 'I want this awesome quick access menu on my Toolbar.', 'imagify' ),
							) );
							?>
							</p>
						</div>
						<div class="imagify-col">
							<p>
								<img class="imagify-menu-bar-img" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'imagify-menu-bar-' . $lang . '.jpg' ); ?>" width="273" height="239" alt="">
							</p>
						</div>

						<?php
						/**
						 * List of partners affected by this option.
						 * For internal use only.
						 *
						 * @since  1.8.2
						 * @author Grégory Viguier
						 *
						 * @param  array $partners An array of partner names.
						 * @return array
						 */
						$partners = apply_filters( 'imagify_deactivatable_partners', array() );

						if ( $partners ) {
							?>
							<p class="imagify-options-subtitle" id="imagify-partners-label">
								<?php esc_html_e( 'Partners', 'imagify' ); ?>

								<span class="imagify-info">
									<span class="dashicons dashicons-info"></span>
									<a href="#imagify-partners-info" class="imagify-modal-trigger"><?php _e( 'More info?', 'imagify' ); ?></a>
								</span>
							</p>

							<p>
								<?php
								$settings->field_checkbox( array(
									'option_name' => 'partner_links',
									'label'       => __( 'Display Partner Links', 'imagify' ),
								) );
								?>
							</p>
							<?php
						}
						?>
					</div>
				</div>

				<?php
				if ( Imagify_Requirements::is_api_key_valid() ) {
					$this->print_template( 'part-settings-footer' );
				}
				?>
			</div>
		</form>
	</div>

	<?php
	$this->print_template( 'part-rocket-ad' );
	$this->print_template( 'modal-settings-infos' );
	$this->print_template( 'modal-settings-partners-infos' );
	$this->print_template( 'modal-settings-visual-comparison' );
	$this->print_template( 'modal-payment' );
	?>

</div>
<?php
