<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$settings     = Imagify_Settings::get_instance();
$options      = Imagify_Options::get_instance();
$option_name  = $options->get_option_name();
$hidden_class = imagify_valid_key() ? '' : ' hidden';
?>
<div class="wrap imagify-settings <?php echo defined( 'WP_ROCKET_VERSION' ) ? 'imagify-have-rocket' : 'imagify-dont-have-rocket'; ?>">

	<?php $this->print_template( 'part-rocket-ad' ); ?>

	<div class="imagify-col imagify-main">

		<?php $this->print_template( 'part-settings-header' ); ?>

		<form action="<?php echo esc_url( $settings->get_form_action() ); ?>" id="imagify-settings" method="post">

			<?php settings_fields( $settings->get_settings_group() ); ?>
			<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
			<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>

			<div class="imagify-col<?php echo $hidden_class; ?>">
				<div class="imagify-settings-section">
					<h2 class="imagify-options-title"><?php _e( 'Settings' ); ?></h2>

					<table class="form-table">
						<tbody>
							<tr class="imagify-middle">
								<th scope="row"><?php _e( 'Optimization Level', 'imagify' ); ?></th>
								<td>
									<p class="imagify-inline-options">
										<input type="radio" id="imagify-optimization_level_normal" name="<?php echo $option_name; ?>[optimization_level]" value="0" <?php checked( $options->get( 'optimization_level' ), 0 ); ?>>
										<label for="imagify-optimization_level_normal">
											<?php _e( 'Normal', 'imagify' ); ?>
										</label>

										<input type="radio" id="imagify-optimization_level_aggro" name="<?php echo $option_name; ?>[optimization_level]" value="1" <?php checked( $options->get( 'optimization_level' ), 1 ); ?>>
										<label for="imagify-optimization_level_aggro">
											<?php _e( 'Aggressive', 'imagify' ); ?>
										</label>

										<input type="radio" id="imagify-optimization_level_ultra" name="<?php echo $option_name; ?>[optimization_level]" value="2" <?php checked( $options->get( 'optimization_level' ), 2 ); ?>>
										<label for="imagify-optimization_level_ultra">
											<?php _e( 'Ultra', 'imagify' ); ?>
										</label>

										<span class="imagify-info">
											<span class="dashicons dashicons-info"></span>
											<a href="#imagify-more-info" class="imagify-modal-trigger"><?php _e( 'More info?', 'imagify' ); ?></a>
										</span>
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
								</td>
							</tr>
							<tr>
								<th scope="row"></th>
								<td>
									<?php
									$settings->field_checkbox( array(
										'option_name' => 'auto_optimize',
										'label'       => __( 'Auto-Optimize images on upload', 'imagify' ),
										'info'        => __( 'Automatically optimize every image you upload to WordPress.', 'imagify' ),
									) );
									?>
								</td>
							</tr>
							<tr>
								<th scope="row"></th>
								<td>
									<?php
									$settings->field_checkbox( array(
										'option_name' => 'backup',
										'label'       => __( 'Backup original images', 'imagify' ),
										'info'        => __( 'Keep your original images in a separate folder before optimization process.', 'imagify' ),
									) );

									$backup_error_class = $options->get( 'backup' ) && ! imagify_backup_dir_is_writable() ? '' : ' hidden';
									?>
									<br/><strong id="backup-dir-is-writable" class="imagify-error<?php echo $backup_error_class; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'imagify_check_backup_dir_is_writable' ) ); ?>">
										<?php
										$backup_path = imagify_make_file_path_relative( get_imagify_backup_dir_path( true ) );
										/* translators: %s is a file path. */
										printf( __( 'The backup folder %s cannot be created or is not writable by the server, original images cannot be saved!', 'imagify' ), "<code>$backup_path</code>" );
										?>
									</strong>
								</td>
							</tr>
							<tr>
								<th scope="row"></th>
								<td>
									<?php
									$info  = __( 'Keep all EXIF data from your images. EXIF are informations stored in your pictures like shutter speed, exposure compensation, ISO, etc...', 'imagify' );
									$info .= '<a href="' . esc_url( imagify_get_external_url( 'exif' ) ) . '" target="_blank">' . __( 'Learn more', 'imagify' ) . '</a><br/><br/>';
									$info .= __( 'If you are a photographer, you may be interested in this option if you are displaying on your pages some info like the model of your camera.', 'imagify' );

									$settings->field_checkbox( array(
										'option_name' => 'exif',
										'label'       => __( 'EXIF Data', 'imagify' ),
										'info'        => $info,
									) );
									?>
								</td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>

			<div class="imagify-col">
				<?php
				$this->print_template( 'part-settings-account' );
				$this->print_template( 'part-settings-documentation' );
				?>
			</div>

			<div class="imagify-settings-section clear<?php echo $hidden_class; ?>">
				<h2 class="imagify-options-title"><?php _e( 'Optimization', 'imagify' ); ?></h2>
				<?php
				$this->print_template( 'part-settings-library' );
				$this->print_template( 'part-settings-custom-folders' );
				?>
			</div>

			<div class="imagify-settings-section clear<?php echo $hidden_class; ?>">
				<h2 class="imagify-options-title"><?php _e( 'Display options', 'imagify' ); ?></h2>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><span><?php _e( 'Show Admin Bar menu', 'imagify' ); ?></span></th>
							<td>
								<?php
								$settings->field_checkbox( array(
									'option_name' => 'admin_bar_menu',
									'label'       => __( 'Show Admin Bar menu', 'imagify' ) . '</span><span class="imagify-visual-label">' . __( 'I want this awesome quick access menu on my admin bar.', 'imagify' ),
								) );
								?>
								<p>
									<img class="imagify-menu-bar-img" src="<?php echo IMAGIFY_ASSETS_IMG_URL . 'imagify-menu-bar.jpg'; ?>" width="300" height="225" alt="">
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<?php $this->print_template( 'part-settings-footer' ); ?>

		</form>
	</div>

	<?php
	$this->print_template( 'modal-settings-infos' );
	$this->print_template( 'modal-settings-visual-comparison' );
	$this->print_template( 'modal-payment' );
	?>

</div>
<?php
