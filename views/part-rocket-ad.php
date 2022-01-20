<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$plugins = get_plugins();

if ( isset( $plugins['wp-rocket/wp-rocket.php'] ) ) {
	return '';
}

$notice  = 'wp-rocket';
$user_id = get_current_user_id();
$notices = get_user_meta( $user_id, '_imagify_ignore_ads', true );
$notices = $notices && is_array( $notices ) ? array_flip( $notices ) : array();

if ( isset( $notices[ $notice ] ) ) {
	return;
}

$discount_percent = '20%';
$dismiss_url      = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_dismiss_ad&ad=' . $notice ), 'imagify-dismiss-ad' );
?>

<div class="imagify-col imagify-sidebar">
	<div class="imagify-sidebar-section">
		<p class="imagify-sidebar-title">
			<?php _e( 'We recommend for you', 'corporate' ); ?>
		</p>

		<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.png" srcset="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 1x, <?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 2x" alt="WP Rocket" width="232" height="63">

		<p class="imagify-sidebar-description">
			<?php
			/* translators: 1 is a "bold" tag opening, 2 is the "bold" tag closing. Please use a non-breaking space for WP Rocket. */
			printf( __( 'WP Rocket is a %1$sspeed optimization plugin for WordPress%2$s helping you to implement a variety of speed-boosting features to your WordPress site.', 'imagify' ), '<strong>', '</strong>' );
			?>
		</p>

		<p>
			<a class="btn btn-rocket" href="<?php echo esc_url( imagify_get_wp_rocket_url() ); ?>" target="_blank">
				<?php
				/* translators: %s is a percentage. */
				printf( __( 'Get %s OFF Now!', 'imagify' ), $discount_percent );
				?>
			</a>
		</p>

		<ul>
			<li><?php _e( 'Improve your Google PageSpeed Score.', 'imagify' ); ?></li>
			<li><?php _e( 'Boost your SEO.', 'imagify' ); ?></li>
			<li><?php _e( 'WooCommerce compatibility.', 'imagify' ); ?></li>
			<li><?php _e( 'Immediate results.', 'imagify' ); ?></li>
		</ul>

	<a class="imagify-sidebar-close" href="<?php echo esc_url( $dismiss_url ); ?>"><span class="screen-reader-text"><?php _e( 'Remove the ad', 'imagify' ); ?></span><i class="dashicons dashicons-no-alt" aria-hidden="true"></i></a>
	</div>
</div>

<?php
