<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<div class="imagify-welcome">
	<div class="imagify-title">
		<img class="imagify-logo" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" width="225" height="26" alt="Imagify" />
		<span class="baseline">
			<?php _e( 'Welcome to Imagify, the best way to easily optimize your images!', 'imagify' ); ?>
		</span>
		<a href="<?php echo esc_url( get_imagify_admin_url( 'dismiss-notice', 'welcome-steps' ) ); ?>" class="imagify-notice-dismiss imagify-welcome-remove" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="dashicons dashicons-dismiss"></span><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
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
						<a id="imagify-signup" target="_blank" href="<?php echo esc_url( imagify_get_external_url( 'register' ) ); ?>" class="button button-primary"><?php _e( 'Sign up, It\'s FREE!', 'imagify' ); ?></a>
					</p>
				</div>
			</div>
			<div class="col-1-3">
				<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>key.svg" width="48" height="48" alt="">
				<div class="imagify-col-content">
					<p class="imagify-col-title"><?php _e( 'Enter your API Key', 'imagify' ); ?></p>
					<p class="imagify-col-desc">
						<?php
						printf(
							/* translators: 1 is a link tag start, 2 is the link tag end. */
							__( 'Save your API Key you have received by email or you can get it on your %1$sImagify account page%2$s.', 'imagify' ),
							'<a target="_blank" href="' . esc_url( imagify_get_external_url( 'get-api-key' ) ) . '">',
							'</a>'
						);
						?>
					</p>
					<p>
						<?php wp_nonce_field( 'imagify-check-api-key', 'imagifycheckapikeynonce', false ); ?>
						<a id="imagify-save-api-key" href="<?php echo esc_url( get_imagify_admin_url() ); ?>" class="button button-primary"><?php _e( 'I have my API key', 'imagify' ); ?></a>
					</p>
				</div>
			</div>
			<div class="col-1-3">
				<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>gear.svg" width="48" height="48" alt="">
				<div class="imagify-col-content">
					<p class="imagify-col-title"><?php _e( 'Configure it', 'imagify' ); ?></p>
					<p class="imagify-col-desc"><?php _e( 'It’s almost done! You have just to configure your optimization settings.', 'imagify' ); ?></p>
					<p><a href="<?php echo esc_url( get_imagify_admin_url() ); ?>" class="button button-primary"><?php _e( 'Go to Settings', 'imagify' ); ?></a></p>
				</div>
			</div>
		</div>
	</div>
</div>
