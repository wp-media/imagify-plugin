<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

global $wp_version;

$settings    = Imagify_Settings::get_instance();
$options     = Imagify_Options::get_instance();
$option_name = $options->get_option_name();
?>
<div class="wrap imagify-settings <?php echo defined( 'WP_ROCKET_VERSION' ) ? 'imagify-have-rocket' : 'imagify-dont-have-rocket'; ?>">

	<?php imagify_print_template( 'part-rocket-ad' ); ?>

	<div class="imagify-col imagify-main">

		<?php imagify_print_template( 'part-settings-header' ); ?>

		<form action="<?php echo esc_url( $settings->get_form_action() ); ?>" id="imagify-settings" method="post">

			<?php settings_fields( $settings->get_settings_group() ); ?>
			<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
			<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>
			<input id="check_api_key" type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="check_api_key">

			<h3 class="screen-reader-text"><?php _e( 'Settings' ); ?></h3>

			<?php imagify_print_template( 'part-new-to-imagify' ); ?>

			<?php if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) { ?>

				<div class="imagify-sub-header">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="api_key"><?php _e( 'API Key', 'imagify' ); ?></label></th>
								<td>
									<input type="text" size="35" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $option_name; ?>[api_key]" id="api_key">
									<?php
									if ( imagify_valid_key() ) {
										?>

										<span id="imagify-check-api-container" class="imagify-valid">
											<span class="dashicons dashicons-yes"></span> <?php _e( 'Your API key is valid.', 'imagify' ); ?>
										</span>

										<?php
									} elseif ( ! imagify_valid_key() && $options->get( 'api_key' ) ) {
										?>

										<span id="imagify-check-api-container">
											<span class="dashicons dashicons-no"></span> <?php _e( 'Your API key isn\'t valid!', 'imagify' ); ?>
										</span>

										<?php
									}

									if ( ! $options->get( 'api_key' ) ) {
										echo '<p class="description desc api_key">';
										printf(
											/* translators: 1 is a link tag start, 2 is the link tag end. */
											__( 'Don\'t have an API Key yet? %1$sCreate one, it\'s FREE%2$s.', 'imagify' ),
											'<a id="imagify-signup" href="' . esc_url( imagify_get_external_url( 'register' ) ) . '" target="_blank">',
											'</a>'
										);
										echo '</p>';
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

			<?php } // End if(). ?>

			<div class="imagify-settings-section <?php echo ! imagify_valid_key() ? 'hidden' : ''; ?>">

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
							<th scope="row"><span><?php _e( 'Auto-Optimize images on upload', 'imagify' ); ?></span></th>
							<td>
								<input type="checkbox" value="1" name="<?php echo $option_name; ?>[auto_optimize]" id="auto_optimize" <?php checked( $options->get( 'auto_optimize' ), 1 ); ?> aria-describedby="describe-auto-optimize" />
								<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
								<label for="auto_optimize" onclick=""><span class="screen-reader-text"><?php _e( 'Auto-Optimize images on upload', 'imagify' ); ?></span></label>

								<span id="describe-auto-optimize" class="imagify-info">
									<span class="dashicons dashicons-info"></span>
									<?php _e( 'Automatically optimize every image you upload to WordPress.', 'imagify' ); ?>
								</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><span><?php _e( 'Backup original images', 'imagify' ); ?></span></th>
							<td>
								<?php
								$backup_enabled     = $options->get( 'backup' );
								$backup_error_class = ' hidden';

								if ( $backup_enabled && ! imagify_backup_dir_is_writable() ) {
									$backup_error_class = '';
								}
								?>
								<input type="checkbox" value="1" name="<?php echo $option_name; ?>[backup]" id="backup" <?php checked( $backup_enabled, 1 ); ?> aria-describedby="describe-backup" />
								<label for="backup" onclick=""><span class="screen-reader-text"><?php _e( 'Backup original images', 'imagify' ); ?></span></label>

								<span id="describe-backup" class="imagify-info">
									<span class="dashicons dashicons-info"></span>
									<?php _e( 'Keep your original images in a separate folder before optimization process.', 'imagify' ); ?>
								</span>

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
							<th scope="row"><span><?php _e( 'Resize larger images', 'imagify' ); ?></span></th>
							<td>
								<input type="checkbox" value="1" name="<?php echo $option_name; ?>[resize_larger]" id="resize_larger" <?php checked( $options->get( 'resize_larger' ), 1 ); ?> aria-describedby="describe-resize-larger" />
								<label for="resize_larger" onclick=""><span class="screen-reader-text"><?php _e( 'Resize larger images', 'imagify' ); ?></span></label>

								<p id="describe-resize-larger" class="imagify-options-line">
									<?php
									$max_sizes       = get_imagify_max_intermediate_image_size();
									$resize_larger_w = $options->get( 'resize_larger_w' );
									printf(
										/* translators: 1 is a text input for a number of pixels (don't use %d). */
										__( 'to maximum %s pixels width', 'imagify' ),
										'<input type="number" min="' . $max_sizes['width'] . '" name="' . $option_name . '[resize_larger_w]" value="' . ( $resize_larger_w ? $resize_larger_w : '' ) . '" size="5">'
									);
									?>
								</p>

								<p class="imagify-checkbox-marged">
									<span class="imagify-info">
										<span class="dashicons dashicons-info"></span>
										<?php
										printf(
											/* translators: 1 is a number of pixels. */
											__( 'This option is recommended to reduce larger images. You can save up to 80%% after resizing. The new width should not be less than your largest thumbnail width, which is actually %dpx.', 'imagify' ),
											$max_sizes['width']
										);
										?>
									</span>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><span><?php _e( 'EXIF Data', 'imagify' ); ?></span></th>
							<td>
								<input type="checkbox" value="1" name="<?php echo $option_name; ?>[exif]" id="exif" <?php checked( $options->get( 'exif' ), 1 ); ?> aria-describedby="describe-exif" />
								<label for="exif" onclick=""><span class="screen-reader-text"><?php _e( 'EXIF Data', 'imagify' ); ?></span></label>

								<span id="describe-exif" class="imagify-info">
									<span class="dashicons dashicons-info"></span>
									<?php _e( 'Keep all EXIF data from your images. EXIF are informations stored in your pictures like shutter speed, exposure compensation, ISO, etc...', 'imagify' ); ?>
									<a href="<?php echo esc_url( imagify_get_external_url( 'exif' ) ); ?>" target="_blank"><?php _e( 'Learn more', 'imagify' ); ?></a>
									<br/><br/>
									<?php _e( 'If you are a photographer, you may be interested in this option if you are displaying on your pages some info like the model of your camera.', 'imagify' ); ?>

								</span>
							</td>
						</tr>

						<?php if ( ! imagify_is_active_for_network() ) { ?>

							<tr>
								<th scope="row"><?php _e( 'Files optimization', 'imagify' ); ?></th>
								<td>
									<p>
										<?php _e( 'You can choose to compress different image sizes created by WordPress here.', 'imagify' ); ?>
										<br/>
										<?php
										printf(
											/* translators: 1 is a "bold" tag start, 2 is the "bold" tag end. */
											__( 'The %1$soriginal size%2$s is %1$sautomatically optimized%2$s by Imagify.', 'imagify' ),
											'<strong>', '</strong>'
										);
										?>
										<br>
										<span class="imagify-important">
											<?php _e( 'Remember each additional image size will affect your Imagify monthly usage!', 'imagify' ); ?>
										</span>
									</p>

									<br>

									<fieldset class="imagify-check-group">
										<legend class="screen-reader-text"><?php _e( 'Choose the sizes to optimize', 'imagify' ); ?></legend>
										<?php
										$sizes      = get_imagify_thumbnail_sizes();
										$select_all = count( $sizes ) > 3;
										$disallowed = $options->get( 'disallowed-sizes' );

										if ( $select_all ) {
											$has_disallowed = array_intersect_key( $disallowed, $sizes );
											$has_disallowed = ! empty( $has_disallowed );
											?>
											<em class="hide-if-no-js">
												<input id="imagify-toggle-check-thumbnail-sizes-1" type="checkbox" class="mini imagify-toggle-check" <?php checked( ! $has_disallowed ); ?>>
												<label for="imagify-toggle-check-thumbnail-sizes-1" onclick=""><?php _e( '(Un)Select All', 'imagify' ); ?></label>
											</em>
											<br class="imagify-br">
											<?php
										}

										foreach ( $sizes as $size_key => $size_data ) {
											$label   = esc_html( stripslashes( $size_data['name'] ) );
											$label   = sprintf( '%s - %d &times; %d', $label, $size_data['width'], $size_data['height'] );
											$checked = ! isset( $disallowed[ $size_key ] );
											?>
											<input type="hidden" name="<?php echo $option_name; ?>[sizes][<?php echo $size_key; ?>-hidden]" value="1" />
											<input type="checkbox" id="imagify_sizes_<?php echo $size_key; ?>" class="mini imagify-row-check" name="<?php echo $option_name; ?>[sizes][<?php echo $size_key; ?>]" value="1" <?php checked( $checked ); ?>/>
											<label for="imagify_sizes_<?php echo $size_key; ?>" onclick=""><?php echo $label; ?></label>
											<br class="imagify-br">
											<?php
										}

										if ( $select_all ) {
											?>
											<em class="hide-if-no-js">
												<input id="imagify-toggle-check-thumbnail-sizes-2" type="checkbox" class="mini imagify-toggle-check" <?php checked( ! $has_disallowed ); ?>>
												<label for="imagify-toggle-check-thumbnail-sizes-2" onclick=""><?php _e( '(Un)Select All', 'imagify' ); ?></label>
											</em>
											<?php
										}
										?>
									</fieldset>
								</td>
							</tr>

						<?php } // End if(). ?>

					</tbody>
				</table>

				<h3 class="imagify-options-title"><?php _e( 'Display options', 'imagify' ); ?></h3>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><span><?php _e( 'Show Admin Bar menu', 'imagify' ); ?></span></th>
							<td>
								<input type="checkbox" value="1" name="<?php echo $option_name; ?>[admin_bar_menu]" id="admin_bar_menu" <?php checked( $options->get( 'admin_bar_menu' ), 1 ); ?> aria-describedby="describe-admin-bar-menu" />
								<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
								<label for="admin_bar_menu" onclick="">
									<span class="screen-reader-text"><?php _e( 'Show Admin Bar menu', 'imagify' ); ?></span>
									<span class="imagify-visual-label"><?php _e( 'I want this awesome quick access menu on my admin bar.', 'imagify' ); ?></span>
								</label>

								<p>
									<img class="imagify-menu-bar-img" src="<?php echo IMAGIFY_ASSETS_IMG_URL . 'imagify-menu-bar.jpg'; ?>" width="300" height="225" alt="">
								</p>
							</td>
						</tr>
					</tbody>
				</table>

			</div>

			<?php imagify_print_template( 'part-settings-footer' ); ?>

		</form>
	</div>

	<?php
	imagify_print_template( 'modal-settings-infos' );
	imagify_print_template( 'modal-settings-visual-comparison' );
	imagify_print_template( 'modal-payment' );
	?>

</div>
<?php
