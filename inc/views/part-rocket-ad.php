<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( defined( 'WP_ROCKET_VERSION' ) ) {
	return '';
}

$discount_percent = '20%';
$discount_code    = 'IMAGIFY20';
?>

<div class="imagify-col imagify-sidebar">
	<div class="imagify-sidebar-section">
		<p class="imagify-sidebar-title">
			<?php _e( 'We recommend for you', 'corporate' ); ?>
		</p>

		<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.png" srcset="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 1x, <?php echo IMAGIFY_ASSETS_IMG_URL; ?>logo-wprocket.svg 2x" alt="WP Rocket" width="232" height="63">

		<p class="imagify-sidebar-description">
			<?php
			/* translators: 1 is a "bold" tag opening, 2 is the "bold" tag closing. Please use a non-breaking space for WP Rocket. */
			printf( __( 'WP Rocket is a %1$sspeed optimization plugin for WordPress%2$s helping you implement  a variety of speed-boosting features to you WordPress site.', 'imagify' ), '<strong>', '</strong>' );
			?>
		</p>

		<p>
			<span class="imagify-rocket-cta-promo">
				<?php
				/* translators: %s is a coupon code. */
				printf( __( 'Coupon: %s', 'imagify' ), '<strong>' . $discount_code . '</strong>' );
				?>
			</span>
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

	<?php $dismiss_url = get_imagify_admin_url( 'dismiss-notice', 'wp-rocket-sidebar-add' ); ?>

	<a class="imagify-sidebar-close" href="<?php echo esc_url( $dismiss_url ); ?>"><span class="screen-reader-text"><?php _e( 'Remove the ad', 'imagify' ); ?></span><i class="dashicons dashicons-no-alt" aria-hidden="true"></i></a>
	</div>
</div>

<?php
