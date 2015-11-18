<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * This warning is displayed when the API key is empty
 *
 * @since 1.0
 */
add_action( 'all_admin_notices', '_imagify_warning_empty_api_key_notice' );
function _imagify_warning_empty_api_key_notice() {
	$current_screen  = get_current_screen();
	$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );
	$cap			 = ( imagify_is_active_for_network() ) ? 'manage_network_options' : 'manage_options';
	
	if ( ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) || in_array( 'welcome-steps', (array) $ignored_notices ) || get_imagify_option( 'api_key', false ) || ! current_user_can( apply_filters( 'imagify_capacity', $cap ) ) ) {
		return;
	}
	?>
	<div class="imagify-welcome">
		<div class="imagify-title">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="225" height="26" alt="Imagify" /> <small><sup><?php echo IMAGIFY_VERSION; ?></sup></small>
			<span class="baseline">
				<?php _e( 'Welcome to Imagify, the best way to easily optimize your images!', 'imagify' ); ?>
			</span>
			<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'welcome-steps' ); ?>" class="imagify-notice-dismiss imagify-welcome-remove" title="<?php _e( 'Dismiss this notice', 'imagify' ); ?>"><span class="dashicons dashicons-dismiss"></span><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
		</div>
		<div class="imagify-settings-section">
			<div class="imagify-columns counter">
				<div class="col-1-3">
					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>user.svg" width="48" height="48" alt="">
					<div class="imagify-col-content">
						<p class="imagify-col-title"><?php _e( 'Create an Account', 'imagify' ); ?></p>
						<p class="imagify-col-desc"><?php _e( 'Don\'t have an Imagify account yet? Optimize your images by creating an account in a few seconds!', 'imagify' ); ?></p>
						<p>
							<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
							<a id="imagify-signup" href="<?php echo IMAGIFY_APP_MAIN; ?>/#/register" class="button button-primary"><?php _e( 'Sign up, It\'s FREE!', 'imagify' ); ?></a></p>
					</div>
				</div>
				<div class="col-1-3">
					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>key.svg" width="48" height="48" alt="">
					<div class="imagify-col-content">
						<p class="imagify-col-title"><?php _e( 'Enter your API Key', 'imagify' ); ?></p>
						<p class="imagify-col-desc"><?php printf( __( 'Save your API Key you have received by email or you can get it on your %sImagify account page%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/api">', '</a>' ); ?></p>
						<p>
							<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>
							<a id="imagify-save-api-key" href="<?php echo get_imagify_admin_url(); ?>" class="button button-primary"><?php _e( 'I have my API key', 'imagify' ); ?></a></p>
					</div>
				</div>
				<div class="col-1-3">
					<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>gear.svg" width="48" height="48" alt="">
					<div class="imagify-col-content">
						<p class="imagify-col-title"><?php _e( 'Configure it', 'imagify' ); ?></p>
						<p class="imagify-col-desc"><?php _e( 'It’s almost done! You have just to configure your optimization settings.', 'imagify' ); ?></p>
						<p><a href="<?php echo get_imagify_admin_url(); ?>" class="button button-primary"><?php _e( 'Go to Settings', 'imagify' ); ?></a></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * This warning is displayed when the API key is empty
 *
 * @since 1.0
 */
add_action( 'all_admin_notices', '_imagify_warning_wrong_api_key_notice' );
function _imagify_warning_wrong_api_key_notice() {
	$current_screen  = get_current_screen();
	$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );
	$api_key		 = get_imagify_option( 'api_key', false );
	$cap			 = ( imagify_is_active_for_network() ) ? 'manage_network_options' : 'manage_options';
	
	if ( ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) || in_array( 'wrong-api-key', (array) $ignored_notices ) || empty( $api_key ) || imagify_valid_key() || ! current_user_can( apply_filters( 'imagify_capacity', $cap ) ) ) {
		return;
	}
	?>
	<div class="clear"></div>
	<div class="error imagify-notice below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<p class="imagify-notice-title"><strong><?php _e( 'Your API key isn\'t valid!', 'imagify' ); ?></strong></p>
			<p>
			<?php wp_nonce_field( 'imagify-signup', 'imagifysignupnonce', false ); ?>
			<?php printf( __( 'Go to your Imagify account page to get your API Key and specify it on %1$syour settings%3$s or %2$screate an account for free%3$s if you don\'t have one yet.', 'imagify' ), '<a href="' . get_imagify_admin_url() . '">', '<a id="imagify-signup" href="' . IMAGIFY_WEB_MAIN . '">', '</a>' ); ?></p>
		</div>
		<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'wrong-api-key' ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>

	<?php
}

/**
 * This warning is displayed when some plugins may conflict with Imagify
 *
 * @since 1.0
 */
add_action( 'all_admin_notices', '_imagify_warning_plugins_to_deactivate_notice' );
function _imagify_warning_plugins_to_deactivate_notice() {
	$plugins_to_deactivate = array();

	// Deactivate all plugins who can cause conflicts with Imagify
	$plugins = array(
		'wp-smush'     => 'wp-smushit/wp-smush.php', // WP Smush
		'kraken'       => 'kraken-image-optimizer/kraken.php', // Kraken.io
		'tinypng'      => 'tiny-compress-images/tiny-compress-images.php', // TinyPNG
		'shortpixel'   => 'shortpixel-image-optimiser/wp-shortpixel.php', // Shortpixel
		'ewww'         => 'ewww-image-optimizer/ewww-image-optimizer.php', // EWWW Image Optimizer
		'ewww-cloud'   => 'ewww-image-optimizer-cloud/ewww-image-optimizer-cloud.php', // EWWW Image Optimizer Cloud
		'imagerecycle' => 'imagerecycle-pdf-image-compression/wp-image-recycle.php', // ImageRecycle 
	);

	/**
	 * Filter the recommended plugins to deactivate to prevent conflicts
	 *
	 * @since 1.0
	 *
	 * @param string $plugins List of recommended plugins to deactivate
	*/
	$plugins = apply_filters( 'imagify_plugins_to_deactivate', $plugins );
	$plugins = array_filter( $plugins, 'is_plugin_active' );

	/** This filter is documented in inc/admin/options.php */
	if (  ! (bool) $plugins || ! imagify_valid_key() || ! current_user_can( apply_filters( 'imagify_capacity', 'manage_options' ) ) ) {
		return;
	} 
	?>
	<div class="clear"></div>
	<div class="imagify-notice error below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<p><?php _e( 'The following plugins are not compatible with this plugin and may cause unexpected results:', 'imagify' ); ?></p>

			<ul class="imagify-plugins-error">
			<?php
			foreach ( $plugins as $plugin ) {
				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin );
				echo '<li>' . $plugin_data['Name'] . '</span> <a href="' . wp_nonce_url( admin_url( 'admin-post.php?action=imagify_deactivate_plugin&plugin=' . urlencode( $plugin ) ), 'imagifydeactivatepluginnonce' ) . '" class="button button-mini alignright">' . __( 'Deactivate', 'imagify' ) . '</a></li>';
			}
			?>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * This notice is displayed to rate the plugin after 100 optimization & 7 days after the first installation
 *
 * @since 1.0
 */
add_action( 'all_admin_notices', '_imagify_rating_notice' );
function _imagify_rating_notice() {
	$current_screen  = get_current_screen();
	$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );

	if ( ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) || in_array( 'rating', (array) $ignored_notices ) || ! current_user_can( apply_filters( 'imagify_capacity', 'manage_options' ) ) ) {
		return;
	}

	$optimized_attachments = imagify_count_optimized_attachments();
	$saving_data 		   = imagify_count_saving_data();

	if ( $optimized_attachments < 100 || $saving_data['percent'] < 30 || ! get_site_transient( 'imagify_seen_rating_notice' ) ) {
		return;
	}
	?>
	<div class="clear"></div>
	<div class="updated imagify-notice below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<?php
			$imagify_rate_url = 'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform';
			?>
			<p><?php printf( __( '%1$sCongratulations%2$s, you have optimized %1$s%3$d images%2$s and reduced by %1$s%4$s%2$s%% your images size.', 'imagify' ), '<strong>', '</strong>', $optimized_attachments, $saving_data['percent'] ); ?></p>
			<p class="imagify-rate-us">
				<?php printf( __( '%sDo you like this plugin?%s Please take a few seconds to %srate it on WordPress.org%s!', 'imagify' ), '<strong>', '</strong><br />', '<a href="' . $imagify_rate_url . '">', '</a>' ); ?>
				<br>
				<a class="stars" href="<?php echo $imagify_rate_url; ?>">☆☆☆☆☆</a>
			</p>
		</div>
		<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'rating' ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>
	<?php
}

/**
 * This notice is displayed when external HTTP requests are blocked via the WP_HTTP_BLOCK_EXTERNAL constant
 *
 * @since 1.0
 */
add_action( 'all_admin_notices', '_imagify_http_block_external_notice' );
function _imagify_http_block_external_notice() {
	$current_screen  = get_current_screen();
	$ignored_notices = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );

	if ( ( isset( $current_screen ) && ( 'settings_page_imagify' === $current_screen->base || 'settings_page_imagify-network' === $current_screen->base ) ) || in_array( 'http-block-external', (array) $ignored_notices ) || ! current_user_can( apply_filters( 'imagify_capacity', 'manage_options' ) ) || ! imagify_valid_key() || ! is_imagify_blocked() ) {
		return;
	}
	?>	
	<div class="clear"></div>
	<div class="error imagify-notice below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<p class="imagify-notice-title"><strong><?php _e( 'The external HTTP requests are blocked!', 'imagify' ); ?></strong></p>
			<p><?php _e( 'You defined the <code>WP_HTTP_BLOCK_EXTERNAL</code> constant in the <code>wp-config.php</code> to block all external HTTP requests.', 'imagify' ); ?></p>
			<p>
			<?php _e( 'To optimize your images, you have to put the following code in your <code>wp-config.php</code> file so that it works correctly.', 'imagify' ); ?><br/>
			<?php _e( 'Click on the field and press Ctrl-A to select all.', 'imagify' ); ?>
			</p>
			<p><textarea readonly="readonly" class="large-text readonly" rows="1">define( 'WP_ACCESSIBLE_HOSTS', '*.imagify.io' );</textarea></p>
		</div>
		<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'http-block-external' ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>	
	<?php
}

/**
 * This warning is displayed when the grid view is active on the library
 *
 * @since 1.0.2
 */
add_action( 'all_admin_notices', '_imagify_warning_grid_view_notice' );
function _imagify_warning_grid_view_notice() {
	$current_screen     = get_current_screen();
	$ignored_notices    = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );
	$media_library_mode = get_user_option( 'media_library_mode', get_current_user_id() );

	if ( ( isset( $current_screen ) && 'upload' !== $current_screen->base ) || in_array( 'grid-view', (array) $ignored_notices ) || ! current_user_can( 'upload_files' ) || ! imagify_valid_key() || $media_library_mode == 'list' ) {
		return;
	}
	?>
	<div class="clear"></div>
	<div class="error imagify-notice below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<p class="imagify-notice-title"><strong><?php _e( 'You\'re missing out!', 'imagify' ); ?></strong></p>
			<p><?php _e( 'Use the List view to optimize images with Imagify.', 'imagify' ); ?></p>
			<p><a href="<?php echo admin_url( 'upload.php?mode=list' ); ?>"><?php _e( 'Switch to the List View', 'imagify' ); ?></a></p>
		</div>
		<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'grid-view' ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>	
	<?php
}

/**
 * This warning is displayed when a user has consumed its monthly free quota.
 *
 * @since 1.1.1
 */
add_action( 'all_admin_notices', '_imagify_warning_over_quota_notice' );
function _imagify_warning_over_quota_notice() {
	$current_screen     = get_current_screen();
	$ignored_notices    = get_user_meta( $GLOBALS['current_user']->ID, '_imagify_ignore_notices', true );
	$user 				= new Imagify_User();
	$cap			    = ( imagify_is_active_for_network() ) ? 'manage_network_options' : 'manage_options';

	if ( ( isset( $current_screen ) && ( 'media_page_imagify-bulk-optimization' !== $current_screen->base && 'settings_page_imagify' !== $current_screen->base && 'settings_page_imagify-network' !== $current_screen->base ) ) || in_array( 'free-over-quota', (array) $ignored_notices ) || ! current_user_can( apply_filters( 'imagify_capacity', $cap ) ) || ! imagify_valid_key() || ! $user->is_over_quota() ) {
		return;
	}
	?>
	<div class="clear"></div>
	<div class="error imagify-notice below-h2">
		<div class="imagify-notice-logo">
			<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
		</div>
		<div class="imagify-notice-content">
			<p class="imagify-notice-title"><strong><?php _e( 'Oops, It\'s Over!', 'imagify' ); ?></strong></p>
			<p>
			<?php printf( __( 'You have consumed all your credit for this month. You will have <strong>%s back on %s</strong>.', 'imagify' ), size_format( $user->quota * 1048576 ), date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) ) ) . '<br/><br/>' . sprintf( __( 'To continue to optimize your images, log in to your Imagify account to %sbuy a pack or subscribe to a plan%s.', 'imagify' ), '<a href="' . IMAGIFY_APP_MAIN . '/#/subscription' . '">', '</a>' ); ?>
			</p>
		</div>
		<a href="<?php echo get_imagify_admin_url( 'dismiss-notice', 'free-over-quota' ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>	
	<?php
}