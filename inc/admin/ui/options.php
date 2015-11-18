<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * The main settings page construtor using the required functions from WP
 *
 * @since 1.0
 */
function _imagify_display_options_page() {
	global $_wp_additional_image_sizes, $wp_version;
	?>
	<div class="wrap imagify-settings">
		<?php
		if ( ! defined( 'WP_ROCKET_VERSION' ) ) { ?>
			<div class="imagify-col imagify-sidebar">
				<div class="imagify-sidebar-section">
					<span class="imagify-sidebar-title">
						<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>phone.svg" width="19" height="16" alt=""> <?php _e( 'Discover our other products', 'imagify' ); ?>
					</span>
					<ul class="wp-media-products">
						<li>
							<a tabindex="1" class="wprocket-link" href="http://wp-rocket.me?utm_source=imagify-plugin">
								<?php _e( 'Is your WordPress website too slow?', 'imagify' ); ?>
								<br/>
								<?php _e( 'Discover the best caching plugin to speed up your website.', 'imagify' ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>
		<?php
		}
		?>

		<div class="imagify-col">
			<?php $heading_tag = version_compare( $GLOBALS['wp_version'], '4.3' ) >= 0 ? 'h1' : 'h2'; ?>
			<div class="imagify-title">
				<img width="225" height="26" alt="Imagify" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" class="imagify-logo" /> <small><sup><?php echo IMAGIFY_VERSION; ?></sup></small>

				<?php $imagify_rate_url =  'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform'; ?>
				<p class="imagify-rate-us">
					<?php printf( __( '%sDo you like this plugin?%s Please take a few seconds to %srate it on WordPress.org%s!', 'imagify' ), '<strong>', '</strong><br />', '<a href="' . $imagify_rate_url . '">', '</a>' ); ?>
					<br>
					<a class="stars" href="<?php echo $imagify_rate_url; ?>"><?php echo str_repeat( '<span class="dashicons dashicons-star-filled"></span>', 5 ); ?></a>
				</p>
			</div>
			<?php $form_action = ( imagify_is_active_for_network() ) ? admin_url( 'admin-post.php' ) : admin_url( 'options.php' ); ?>
			<form action="<?php echo $form_action; ?>" id="imagify-settings" method="post">

				<?php settings_fields( IMAGIFY_SLUG ); ?>
				<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
				<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>

				<input id="check_api_key" type="hidden" value="<?php echo esc_attr( get_imagify_option( 'api_key' ) ); ?>" name="check_api_key">
				<input id="version" type="hidden" value="<?php echo esc_attr( get_imagify_option( 'version' ) ); ?>" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[version]">

				<h3 class="screen-reader-text"><?php _e( 'Settings' ); ?></h3>

				<?php
				if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) { ?>
				<div class="imagify-sub-header">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="api_key"><?php _e( 'API Key', 'imagify' ); ?></label></th>
								<td>
									<input type="text" size="35" value="<?php echo esc_attr( get_imagify_option( 'api_key' ) ); ?>" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[api_key]" id="api_key">
									<?php
									if ( imagify_valid_key() ) {
									?>
										<span id="imagify-check-api-container" class="imagify-valid">
											<span class="dashicons dashicons-yes"></span> <?php _e( 'Your API key is valid.', 'imagify' ); ?>
										</span>
									<?php
									} elseif ( ! imagify_valid_key() && get_imagify_option( 'api_key', false ) ) { ?>

										<span id="imagify-check-api-container">
											<span class="dashicons dashicons-no"></span> <?php _e( 'Your API key isn\'t valid!', 'imagify' ); ?>
										</span>

									<?php
									}

									if ( ! get_imagify_option( 'api_key', false ) ) {
										echo '<p class="description desc api_key">' . sprintf( __( 'Don\'t have an API Key yet? %sCreate one, it\'s FREE%s.', 'imagify' ), '<a id="imagify-signup" href="' . IMAGIFY_APP_MAIN . '/#/register">', '</a>' ) . '</p>';
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<?php
				}
				?>

				<div class="imagify-settings-section <?php echo ( ! imagify_valid_key() ) ? 'hidden' : ''; ?>">
					<table class="form-table">
						<tbody>
							<tr class="imagify-middle">
								<th scope="row"><?php _e( 'Optimization Level', 'imagify' ); ?></th>
								<td>
									<p class="imagify-inline-options">
										<input type="radio" id="imagify-optimization_level_ultra" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[optimization_level]" value="2" <?php checked( get_imagify_option( 'optimization_level' ), 2 ); ?>>
										<label for="imagify-optimization_level_ultra">
											<?php _e( 'Ultra', 'imagify' ); ?>
										</label>

										<input type="radio" id="imagify-optimization_level_aggro" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[optimization_level]" value="1" <?php checked( get_imagify_option( 'optimization_level' ), 1 ); ?>>
										<label for="imagify-optimization_level_aggro">
											<?php _e( 'Aggressive', 'imagify' ); ?>
										</label>

										<input type="radio" id="imagify-optimization_level_normal" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[optimization_level]" value="0" <?php checked( get_imagify_option( 'optimization_level' ), 0 ); ?>>
										<label for="imagify-optimization_level_normal">
											<?php _e( 'Normal', 'imagify' ); ?>
										</label>

										<span class="imagify-info">
											<span class="dashicons dashicons-info"></span>
											<a href="#imagify-more-info" class="imagify-modal-trigger"><?php _e( 'More info?', 'imagify' ); ?></a>
										</span>
									</p>

									<p class="imagify-visual-comparison-text">
										<?php
										printf(
											__( 'To see the differences, %sopen a visual comparison%s', 'imagify' ),
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
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[auto_optimize]" id="auto_optimize" <?php checked( get_imagify_option( 'auto_optimize', 0 ), 1 ); ?> aria-describedby="describe-auto-optimize" />
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
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[backup]" id="backup" <?php checked( get_imagify_option( 'backup', 0 ), 1 ); ?> aria-describedby="describe-backup" />
									<label for="backup" onclick=""><span class="screen-reader-text"><?php _e( 'Backup original images', 'imagify' ); ?></span></label>

									<span id="describe-backup" class="imagify-info">
										<span class="dashicons dashicons-info"></span>
										<?php _e( 'Keep your original images in a separate folder before optimization process.', 'imagify' ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><span><?php _e( 'Resize larger images', 'imagify' ); ?></span></th>
								<td>
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[resize_larger]" id="resize_larger" <?php checked( get_imagify_option( 'resize_larger', 0 ), 1 ); ?> aria-describedby="describe-resize-larger" />
									<label for="resize_larger" onclick=""><span class="screen-reader-text"><?php _e( 'Resize larger images', 'imagify' ); ?></span></label>

									<p id="describe-resize-larger" class="imagify-options-line">
										<?php
											$max_sizes = get_imagify_max_intermediate_image_size();
											printf(
												__( 'to maximum %s pixels width', 'imagify' ),
												'<input type="number" min="' . $max_sizes['width'] . '" name="' . IMAGIFY_SETTINGS_SLUG . '[resize_larger_w]" value="' . get_imagify_option( 'resize_larger_w', false ). '" size="5">'
											);
										?>
									</p>

									<p class="imagify-checkbox-marged">
										<span class="imagify-info">
											<span class="dashicons dashicons-info"></span>

											<?php
												printf( __( 'This option is recommended to reduce larger images. You can save up to 80%% after resizing. The new width should not be less than your largest thumbnail width, which is actually %spx.', 'imagify' ), $max_sizes['width'] );
											?>
										</span>
									</p>
								</td>
							</tr>

							<?php
							if ( ! imagify_is_active_for_network() ) { ?>

							<tr>
								<th scope="row"><?php _e( 'Files optimization', 'imagify' ); ?></th>
								<td>
									<p>
										<?php _e( 'You can choose to compress different image sizes created by WordPress here.', 'imagify' ); ?>
										<br/>
										<?php printf( __( 'The %soriginal size%s is %sautomatically optimized%s by Imagify.', 'imagify' ), '<strong>', '</strong>', '<strong>', '</strong>' ); ?>
										<br>
										<span class="imagify-important">
											<?php _e( 'Remember each additional image size will affect your Imagify monthly usage!', 'imagify' ); ?>
										</span>
									</p>

									<br>

									<?php
									$sizes   = array();
									$is_wp44 = version_compare( $wp_version, '4.4-beta3' ) >= 0;
									$all_intermediate_image_sizes = get_intermediate_image_sizes();
									$intermediate_image_sizes     = apply_filters( 'image_size_names_choose', $all_intermediate_image_sizes );
									$all_intermediate_image_sizes = array_combine( $all_intermediate_image_sizes, $all_intermediate_image_sizes );
									$intermediate_image_sizes     = array_merge( $all_intermediate_image_sizes, $intermediate_image_sizes );
									$wp_image_sizes               = $is_wp44 ? array( 'thumbnail', 'medium', 'medium_large', 'large' ) : array( 'thumbnail', 'medium', 'large' );

									// Create the full array with sizes and crop info
									foreach ( $intermediate_image_sizes as $size => $size_name ) {
										if ( in_array( $size, $wp_image_sizes ) && ! is_int( $size ) ) {
											$sizes[ $size ] = array(
												'width'  => get_option( $size . '_size_w' ),
												'height' => get_option( $size . '_size_h' ),
												'name'   => $size_name,
											);
										} elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
											$sizes[ $size ] = array(
												'width'  => $_wp_additional_image_sizes[ $size ]['width'],
												'height' => $_wp_additional_image_sizes[ $size ]['height'],
												'name'   => $size_name,
											);
										}
									}

									foreach( $sizes as $size_key => $size_data ) {
										$label = esc_html( stripslashes( $size_data['name'] ) );
										$label = sprintf( '%s - %d &times; %d', $label, $size_data['width'], $size_data['height'] );
										?>
										<input type="hidden" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[sizes][<?php echo $size_key; ?>-hidden]" value="1" />
										<input type="checkbox" id="imagify_sizes_<?php echo $size_key; ?>" class="mini" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[sizes][<?php echo $size_key; ?>]" value="1" <?php echo ( ! array_key_exists( $size_key, get_imagify_option( 'disallowed-sizes', array() ) ) ) ? 'checked="checked"' : '' ?> />
										<label for="imagify_sizes_<?php echo $size_key; ?>" onclick=""><?php echo $label; ?></label>
										<br class="imagify-br">

										<?php
									}
									?>
								</td>
							</tr>

							<?php
							}
							?>
						</tbody>
					</table>

					<h3 class="imagify-options-title"><?php _e( 'Display options', 'Imagify' ); ?></h3>

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><span><?php _e( 'Show Admin Bar menu', 'imagify' ); ?></span></th>
								<td>
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[admin_bar_menu]" id="admin_bar_menu" <?php checked( get_imagify_option( 'admin_bar_menu', 0 ), 1 ); ?> aria-describedby="describe-admin-bar-menu" />
									<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
									<label for="admin_bar_menu" onclick="">
										<span class="screen-reader-text"><?php _e( 'Show Admin Bar menu', 'imagify' ); ?></span>
										<span class="imagify-visual-label"><?php _e( 'I want this awesome quick access menu on my menu bar.', 'imagify' ); ?></span>
									</label>

									<p>
										<img class="imagify-menu-bar-img" src="<?php echo IMAGIFY_ASSETS_IMG_URL . 'imagify-menu-bar.jpg'; ?>" width="300" height="225" alt="">
									</p>
								</td>
							</tr>
						</tbody>
					</table>

				</div>
				<div class="submit">
					<?php submit_button(); ?>
					<div class="imagify-bulk-info">
						<p><?php printf( __( 'When you have saved your settings, you can optimize all your images using %sImagify Bulk Optimization%s feature.', 'imagify' ), '<a href="' . get_admin_url() . 'upload.php?page=' . IMAGIFY_SLUG . '-bulk-optimization">', '</a>' ); ?></p>
					</div>
				</div>
			</form>
		</div>

		<div id="imagify-more-info" class="imagify-modal">
			<div class="imagify-modal-content">
				<p class="h2"><?php _e('You can choose three levels of compression', 'imagify'); ?></p>
				<div class="imagify-columns">
					<div class="col-1-3">
						<p class="h3"><?php _e( 'Ultra', 'imagify' ); ?></p>
						<p>
							<?php _e( 'This mode will apply all available optimizations for maximum image compression. ', 'imagify' ); ?>
						</p>
						<p>
							<?php _e( 'This will provide a drastic savings on the initial weight, with a small reduction in image quality. Most of the time it\'s not even noticeable.', 'imagify' ); ?>
						</p>
						<p>
							<?php _e( 'If you want the maximum weight reduction, we recommend using this mode.' , 'imagify' ); ?>
						</p>
					</div>
					<div class="col-1-3">
						<p class="h3"><?php _e( 'Aggressive', 'imagify' ); ?></p>
						<p>
							<?php _e( 'This mode will apply all available optimizations for maximum image compression. ', 'imagify' ); ?>
						</p>
						<p>
							<?php _e( 'This will provide a drastic savings on the initial weight, with a small reduction in image quality. Most of the time it\'s not even noticeable.', 'imagify' ); ?>
						</p>
						<p>
							<?php _e( 'If you want the maximum weight reduction, we recommend using this mode.' , 'imagify' ); ?>
						</p>
					</div>
					<div class="col-1-3">
						<p class="h3"><?php _e( 'Normal', 'imagify' ); ?></p>
						<p>
							<?php _e( 'This mode provides lossless optimization, your images will be optimized without any visible change.', 'imagify' ); ?>
						</p>
						<p>
							<?php _e( 'If you want the perfect quality for your images, we recommend you that mode.', 'imagify' ); ?>
						</p>
						<p>
							<em><?php _e( 'Note: the file size reduction will be less, compared to aggressive mode.', 'imagify' ); ?></em>
						</p>
					</div>
				</div>

				<button type="button" class="close-btn">
					<i class="dashicons dashicons-no-alt" aria-hidden="true"></i>
					<span class="screen-reader-text"><?php _e( 'Close' ); ?></span>
				</button>
			</div>
		</div>


		<div class="imagify-modal" id="imagify-visual-comparison">
			<div class="imagify-modal-content">

				<p class="imagify-comparison-title">
					<?php
						printf (
							__( 'I want to compare%s and%s', 'imagify'),
							'<span class="twentytwenty-left-buttons"></span>',
							'<span class="twentytwenty-right-buttons"></span>'
						);
					?>
				</p>

				<div class="twentytwenty-container"
									data-loader="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>loader-balls.svg"
									data-label-original="<?php _e( 'Original', 'imagify' ); ?>"
									data-label-normal="<?php _e( 'Normal', 'imagify' ); ?>"
									data-label-aggressive="<?php _e( 'Aggressive', 'imagify' ); ?>"
									data-label-ultra="<?php _e( 'Ultra', 'imagify' ); ?>"

									data-original-label="<?php _e( 'Original', 'imagify' ); ?>"
									data-original-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>bear-original.jpg"
									data-original-dim="1220x350"
									data-original-alt="Bear photography about 396kb"

									data-optimized-label="<?php _e( 'Normal', 'imagify' ); ?>"
									data-optimized-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>bear-optimized.jpg"
									data-optimized-dim="1220x350"
									data-optimized-alt="Imagified Bear photography about 363kb"

									data-aggressive-label="<?php _e( 'Aggressive', 'imagify' ); ?>"
									data-aggressive-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>bear-aggressive.jpg"
									data-aggressive-dim="1220x350"
									data-aggressive-alt="Imagified Bear photography about 140kb"

									data-ultra-label="<?php _e( 'Ultra', 'imagify' ); ?>"
									data-ultra-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>bear-ultra.jpg"
									data-ultra-dim="1220x350"
									data-ultra-alt="Imagified Bear photography about 56kb"></div>

				<div class="imagify-comparison-levels">
					<div class="imagify-c-level imagify-level-original go-left">
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
							<span class="value level"><?php _e( 'Original', 'imagify' ); ?></span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
							<span class="value">396kb</span>
						</p>
					</div>
					<div class="imagify-c-level imagify-level-optimized" aria-hidden="true">
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
							<span class="value level"><?php _e( 'Normal', 'imagify' ); ?></span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
							<span class="value size">363kb</span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
							<span class="value">
								<span class="imagify-chart">
									<span class="imagify-chart-container">
										<canvas id="imagify-consumption-chart-normal" width="15" height="15"></canvas>
									</span>
								</span><span class="imagify-chart-value">10,44</span>%
							</span>
						</p>
					</div>
					<div class="imagify-c-level imagify-level-aggressive">
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
							<span class="value level"><?php _e( 'Aggressive', 'imagify' ); ?></span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
							<span class="value size">140kb</span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
							<span class="value">
								<span class="imagify-chart">
									<span class="imagify-chart-container">
										<canvas id="imagify-consumption-chart-aggressive" width="15" height="15"></canvas>
									</span>
								</span><span class="imagify-chart-value">65.53</span>%
							</span>
						</p>
					</div>

					<div class="imagify-c-level imagify-level-ultra go-right">
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
							<span class="value level"><?php _e( 'Ultra', 'imagify' ); ?></span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
							<span class="value size">106kb</span>
						</p>
						<p class="imagify-c-level-row">
							<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
							<span class="value">
								<span class="imagify-chart">
									<span class="imagify-chart-container">
										<canvas id="imagify-consumption-chart-ultra" width="15" height="15"></canvas>
									</span>
								</span><span class="imagify-chart-value">74.16</span>%
							</span>
						</p>
					</div>
				</div>

				<button type="button" class="close-btn">
					<i class="dashicons dashicons-no-alt" aria-hidden="true"></i>
					<span class="screen-reader-text"><?php _e( 'Close' ); ?></span>
				</button>
			</div>
		</div>

	</div>
	<?php
}