<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$dismiss_url  = get_imagify_admin_url( 'dismiss-notice', 'wp-rocket' );
$wprocket_url = imagify_get_wp_rocket_url();
?>
<div class="updated imagify-rkt-notice">
	<a href="<?php echo esc_url( $dismiss_url ); ?>" class="imagify-notice-dismiss imagify-cross"><span class="dashicons dashicons-no"></span></a>

	<p class="imagify-rkt-logo">
		<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.png" srcset="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 1x, <?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 2x" alt="WP Rocket" width="118" height="32">
	</p>
	<p class="imagify-rkt-msg">
		<?php
		esc_html_e( 'Discover the best caching plugin to speed up your website.', 'imagify' );
		?>
	</p>
	<p class="imagify-rkt-cta">
		<a target="_blank" href="<?php echo esc_url( $wprocket_url ); ?>" class="button button-primary tgm-plugin-update-modal"><?php esc_html_e( 'Get WP Rocket now', 'imagify' ); ?></a>
	</p>
</div>
