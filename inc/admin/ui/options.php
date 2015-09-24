<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * The main settings page construtor using the required functions from WP
 *
 * @since 1.0
 */
function _imagify_display_options_page() { ?>
	<div class="wrap imagify-settings">

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
			
			<?php
			/*
			?>
			<div class="imagify-sidebar-section">
				<span class="imagify-sidebar-title">
					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>mail.svg" width="21" height="18" alt=""> <?php _e( 'Join our mailing list', 'imagify' ); ?>
				</span>
				<div class="imagify-sidebar-content">
					<p>
					<?php
					_e( 'Join our mailing list to receive countless amount of cat pictures, discount codes and awesome stuf.', 'imagify' );
					?>
					</p>
					<form action="." method="post">
						<p>
							<label for="imagify-email" class="screen-reader-text"><?php _e( 'Your email address', 'imagify' ); ?></label>
							<input type="email" name="imagify-email" id="imagify-email" placeholder="<?php _e( 'Your email address', 'imagify' ); ?>" />
						</p>
						<p>
							<button type="submit" class="button button-primary button-mini"><?php _e( 'Subscribe', 'imagify' ); ?></button>
						</p>
					</form>
				</div>
			</div>
			<?php
			*/
			?>
		</div>

		<div class="imagify-col">
			<?php $heading_tag = version_compare( $GLOBALS['wp_version'], '4.3' ) >= 0 ? 'h1' : 'h2'; ?>
			<div class="imagify-title">
				<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="255" height="50" alt="Imagify" /> <small><sup><?php echo IMAGIFY_VERSION; ?></sup></small>

				<?php $imagify_rate_url =  'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform'; ?>
				<p class="imagify-rate-us">
					<?php echo sprintf( __( '%sDo you like this plugin?%s Please take a few seconds to %srate it on WordPress.org%s!', 'imagify' ), '<strong>', '</strong><br />', '<a href="' . $imagify_rate_url . '">', '</a>' ); ?>
					<br>
					<a class="stars" href="<?php echo $imagify_rate_url; ?>">☆☆☆☆☆</a>
				</p>
			</div>
			<?php $form_action = ( imagify_is_active_for_network() ) ? admin_url( 'admin-post.php' ) : admin_url( 'options.php' ); ?>
			<form action="<?php echo $form_action; ?>" id="imagify-settings" method="post">

				<?php settings_fields( IMAGIFY_SLUG ); ?>
				<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
				<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>

				<input id="check_api_key" type="hidden" value="<?php echo esc_attr( get_imagify_option( 'api_key' ) ); ?>" name="check_api_key">
				<input id="version" type="hidden" value="<?php echo esc_attr( get_imagify_option( 'version' ) ); ?>" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[version]">

				<h3 class="screen-reader-text"><?php _e( 'Settings', 'imagify' ); ?></h3>
				
				<?php
				if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) { ?>
				<div class="imagify-sub-header">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="api_key">API Key</label></th>
								<td>
									<input type="text" size="35" value="<?php echo get_imagify_option( 'api_key' ); ?>" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[api_key]" id="api_key">
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
										echo '<p class="description desc api_key">' . sprintf( __( 'Don\'t have an API Key yet? %sCreate one, it\'s FREE%s.', 'imagify' ), '<a id="imagify-signup" href="' . IMAGIFY_WEB_MAIN . '">', '</a>' ) . '</p>';
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
							
				<div class="imagify-settings-section <?php echo ( imagify_valid_key() ) ?: 'hidden'; ?>">
					<table class="form-table">
						<tbody>
							<tr class="imagify-middle">
								<th scope="row"><?php _e( 'Optimization Level', 'imagify' ); ?></th>
								<td>
									<p class="imagify-inline-options">
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
											<a href="#"><?php _e( 'More info?', 'imagify' ); ?></a>
										</span>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Auto-Optimize images on upload', 'imagify' ); ?></th>
								<td>
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[auto_optimize]" id="auto_optimize" <?php checked( get_imagify_option( 'auto_optimize', 0 ), 1 ); ?> aria-describedby="describe-auto-optimize" />
									<!-- Empty onclick attribute to make clickable labels on iTruc & Mac -->
									<label for="auto_optimize" onclick=""><span class="screen-reader-text"><?php _e( 'Auto-Optimize images on upload', 'imagify' ); ?></span></label>

									<span id="describe-auto-optimize" class="imagify-info">
										<span class="dashicons dashicons-info"></span>
										<?php _e( 'Optimize automatically every image you will upload to WordPress.', 'imagify' ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Backup original images', 'imagify' ); ?></th>
								<td>
									<input type="checkbox" value="1" name="<?php echo IMAGIFY_SETTINGS_SLUG; ?>[backup]" id="backup" <?php checked( get_imagify_option( 'backup', 0 ), 1 ); ?> aria-describedby="describe-backup" />
									<label for="backup" onclick=""><span class="screen-reader-text"><?php _e( 'Backup original images', 'imagify' ); ?></span></label>

									<span id="describe-backup" class="imagify-info">
										<span class="dashicons dashicons-info"></span>
										<?php _e( 'Keep your original images in a secure place before optimization process.', 'imagify' ); ?>
									</span>
								</td>
							</tr>
							<?php
							if ( ! imagify_is_active_for_network() ) { ?>

							<tr>
								<th scope="row"><?php _e( 'Files optimization', 'imagify' ); ?></th>
								<td>
									<p><?php _e( 'You can choose to compress different image sizes created by WordPress here.', 'imagify' ); ?><br/><span class="imagify-important"><?php _e( 'Remember each additional image size will affect your Imagify monthly usage!', 'imagify' ); ?></span></p><br>

									<?php
									global $_wp_additional_image_sizes;

								    $sizes = array( 'full' => false );
								    $get_intermediate_image_sizes = get_intermediate_image_sizes();

								    // Create the full array with sizes and crop info
								    foreach ( $get_intermediate_image_sizes as $size ) {
								        if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
								            $sizes[ $size ]['width']  = get_option( $size . '_size_w' );
								            $sizes[ $size ]['height'] = get_option( $size . '_size_h' );
								        } elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
								            $sizes[ $size ] = array(
								                    'width'  => $_wp_additional_image_sizes[ $size ]['width'],
								                    'height' => $_wp_additional_image_sizes[ $size ]['height']
								            );
								        }
								    }

									foreach( $sizes as $size_key => $size_data ) {
										$label  = $size_key;

										if ( 'full' != $size_key ) {
											$label .= ' - ' . $size_data['width'] . 'x' . $size_data['height'];
										}
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
				</div>
				<?php submit_button(); ?>
			</form>
		</div>

	</div>
	<?php
}