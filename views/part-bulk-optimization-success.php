<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>
<!-- The Success/Complete bar -->
<div class="imagify-row-complete hidden" aria-hidden="true">
	<div class="imagify-all-complete">
		<div class="imagify-ac-report">
			<div class="imagify-ac-chart" data-percent="0">
				<span class="imagify-chart">
					<span class="imagify-chart-container">
						<canvas id="imagify-ac-chart" width="46" height="46"></canvas>
					</span>
				</span>
			</div>
			<div class="imagify-ac-report-text">
				<p class="imagify-ac-rt-big"><?php _e( 'Well done!', 'imagify' ); ?></p>
				<p>
					<?php
					printf(
						// translators: %1$s = number of images, %2$s = data size, %3$s = data size.
						__( 'You optimized %1$s images and saved %2$s out of %3$s', 'imagify' ),
						'<strong class="imagify-ac-rt-total-images"></strong>',
						'<strong class="imagify-ac-rt-total-gain"></strong>',
						'<strong class="imagify-ac-rt-total-original"></strong>'
					);
					?>
				</p>
			</div>
		</div>
		<div class="imagify-ac-spread-word">
			<h3><?php esc_html_e( 'How about spreading the word?', 'imagify' ); ?></h3>
			<p>
			<?php
			printf(
				// translators: 1 is a link tag start, 2 is the link tag end.
				__( 'Please take a few seconds to leave a review on %1$sWordPress.org%2$s. It would mean the world to us!', 'imagify' ),
				'<a href="' . esc_url( imagify_get_external_url( 'rate' ) ) . '" target="_blank">',
				'</a>'
			);
			?>
			<br>
			<a class="stars" aria-hidden="true" href="<?php echo esc_url( imagify_get_external_url( 'rate' ) ); ?>" target="_blank"><?php echo str_repeat( '<span class="dashicons dashicons-star-empty"></span>', 5 ); ?></a>
		</p>
		</div>
		<div class="imagify-ac-leave-review">
			<a href="<?php echo esc_url( imagify_get_external_url( 'rate' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Leave a review', 'imagify' ); ?></a>
		</div>
	</div>
</div>
<?php
