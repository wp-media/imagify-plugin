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
		<span class="imagify-sidebar-title">
			<img src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>phone.svg" width="19" height="16" alt=""> <?php _e( 'Is your website too slow?', 'imagify' ); ?>
		</span>
		<ul class="wp-media-products">
			<li>
				<div class="links wprocket-link">
					<p><strong>
						<?php _e( 'Discover the best caching plugin to speed up your website.', 'imagify' ); ?>
					</strong></p>

					<p class="imagify-big-text">
						<?php
						printf(
							/* translators: 1 is the start of a styled wrapper, 2 is a "bold" tag start, 3 is a percentage, 4 is the "bold" tag end, 5 is the styled wrapper end, 6 is a discount code. */
							__( '%1$sGet %2$s%3$s off%4$s%5$s with this coupon code: %6$s', 'imagify' ),
							'<span class="imagify-mark-styled"><span>',
							'<strong>',
							$discount_percent,
							'</strong>',
							'</span></span>',
							'<span class="imagify-discount-code">' . $discount_code . '</span>'
						);
						?>
					</p>

					<p>
						<a class="btn btn-rocket" href="<?php echo esc_url( imagify_get_wp_rocket_url() ); ?>" target="_blank"><?php _e( 'Get WP Rocket now', 'imagify' ); ?></a>
					</p>
				</div>
			</li>
		</ul>
	</div>
</div>

<?php
