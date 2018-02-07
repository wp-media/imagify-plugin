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

		<p>
			<?php $promo = __( 'Get %s OFF%s Now', 'rocket-lazyload' ); ?>
			<span class="imagify-rocket-cta-promo">
				<?php printf( $promo, '<strong>20%', '</strong>' ); ?>
			</span>
			<a class="btn btn-rocket" href="<?php echo esc_url( imagify_get_wp_rocket_url() ); ?>" target="_blank"><?php _e( 'Get WP Rocket now', 'imagify' ); ?></a>
		</p>

		<ul>
			<li><?php
				printf(
					/* translators: 1 is the start of the colored text wrapper, 2 is the end. */
					__( 'All you need to %1$simprove your Google PageSpeed%2$s score.', 'imagify' ),
					'<strong>',
					'</strong>'
				);
			?></li>
			<li><?php
				printf(
					/* translators: 1 is the start of the colored text wrapper, 2 is the end. */
					__( '%1$sBoost your SEO%2$s by preloading your cache page for Google’s bots.', 'imagify' ),
					'<strong>',
					'</strong>'
				);
			?></li>
			<li><?php
				printf(
					/* translators: 1 is the start of the colored text wrapper, 2 is the end. */
					__( 'Watch your conversion rise with the %1$100% WooCommerce compatibility%2$.', 'imagify' ),
					'<strong>',
					'</strong>'
				);
			?></li>
			<li><?php
				printf(
					/* translators: 1 is the start of the colored text wrapper, 2 is the end. */
					__( 'Minimal configuration, %1$Immediate results%2$.', 'imagify' ),
					'<strong>',
					'</strong>'
				);
			?></li>
		</ul>
	</div>
</div>

<?php
