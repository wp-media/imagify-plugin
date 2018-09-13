<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>

<div class="imagify-modal" id="imagify-visual-comparison">
	<div class="imagify-modal-content">

		<p class="imagify-comparison-title">
			<?php
			printf(
				/* translators: 1 and 2 are optimization levels: "Original", "Normal", "Aggressive", or "Ultra". */
				__( 'I want to compare %1$s and %2$s', 'imagify' ),
				'<span class="twentytwenty-left-buttons"></span>',
				'<span class="twentytwenty-right-buttons"></span>'
			);
			?>
		</p>

		<div class="twentytwenty-container"
			data-loader="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>loader-balls.svg"
			data-label-original="<?php esc_attr_e( 'Original', 'imagify' ); ?>"
			data-label-normal="<?php esc_attr_e( 'Normal', 'imagify' ); ?>"
			data-label-aggressive="<?php esc_attr_e( 'Aggressive', 'imagify' ); ?>"
			data-label-ultra="<?php esc_attr_e( 'Ultra', 'imagify' ); ?>"

			data-original-label="<?php esc_attr_e( 'Original', 'imagify' ); ?>"
			data-original-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>mushrooms-original.jpg"
			data-original-dim="1220x350"
			data-original-alt="<?php
				/* translators: %s is a formatted file size. */
				printf( esc_attr__( 'Original photography about %s', 'imagify' ), imagify_size_format( 343040 ) );
			?>"

			data-normal-label="<?php esc_attr_e( 'Normal', 'imagify' ); ?>"
			data-normal-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>mushrooms-normal.jpg"
			data-normal-dim="1220x350"
			data-normal-alt="<?php
				/* translators: %s is a formatted file size. */
				printf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 301056 ) );
			?>"

			data-aggressive-label="<?php esc_attr_e( 'Aggressive', 'imagify' ); ?>"
			data-aggressive-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>mushrooms-aggressive.jpg"
			data-aggressive-dim="1220x350"
			data-aggressive-alt="<?php
				/* translators: %s is a formatted file size. */
				printf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 108544 ) );
			?>"

			data-ultra-label="<?php esc_attr_e( 'Ultra', 'imagify' ); ?>"
			data-ultra-img="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>mushrooms-ultra.jpg"
			data-ultra-dim="1220x350"
			data-ultra-alt="<?php
				/* translators: %s is a formatted file size. */
				printf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 46080 ) );
			?>"></div>

		<div class="imagify-comparison-levels">
			<div class="imagify-c-level imagify-level-original go-left">
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php _e( 'Original', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
					<span class="value"><?php echo imagify_size_format( 343040 ); ?></span>
				</p>
			</div>
			<div class="imagify-c-level imagify-level-optimized imagify-level-normal" aria-hidden="true">
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php _e( 'Normal', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'File Size:', 'imagify' ); ?></span>
					<span class="value size"><?php echo imagify_size_format( 301056 ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
					<span class="value">
						<span class="imagify-chart">
							<span class="imagify-chart-container">
								<canvas id="imagify-consumption-chart-normal" width="15" height="15"></canvas>
							</span>
						</span><span class="imagify-chart-value">12.24</span>%
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
					<span class="value size"><?php echo imagify_size_format( 108544 ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
					<span class="value">
						<span class="imagify-chart">
							<span class="imagify-chart-container">
								<canvas id="imagify-consumption-chart-aggressive" width="15" height="15"></canvas>
							</span>
						</span><span class="imagify-chart-value">68.36</span>%
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
					<span class="value size"><?php echo imagify_size_format( 46080 ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php _e( 'Original Saving:', 'imagify' ); ?></span>
					<span class="value">
						<span class="imagify-chart">
							<span class="imagify-chart-container">
								<canvas id="imagify-consumption-chart-ultra" width="15" height="15"></canvas>
							</span>
						</span><span class="imagify-chart-value">86.57</span>%
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

<?php
