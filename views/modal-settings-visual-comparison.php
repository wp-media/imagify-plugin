<?php
defined( 'ABSPATH' ) || exit;

// translators: %s is a formatted file size.
$original_alt = sprintf( esc_attr__( 'Original photography about %s', 'imagify' ), imagify_size_format( 343040 ) );

// translators: %s is a formatted file size.
$normal_alt = sprintf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 301056 ) );

// translators: %s is a formatted file size.
$aggressive_alt = sprintf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 108544 ) );

// translators: %s is a formatted file size.
$ultra_alt = sprintf( esc_attr__( 'Optimized photography about %s', 'imagify' ), imagify_size_format( 46080 ) );
?>

<div class="imagify-modal" id="imagify-visual-comparison">
	<div class="imagify-modal-content">

		<p class="imagify-comparison-title">
			<?php
			printf(
				/* translators: 1 and 2 are optimization levels: "Original", "Normal", "Aggressive", or "Ultra". */
				esc_html__( 'I want to compare %1$s and %2$s', 'imagify' ),
				'<span class="twentytwenty-left-buttons"></span>',
				'<span class="twentytwenty-right-buttons"></span>'
			);
			?>
		</p>

		<div class="twentytwenty-container"
			data-loader="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>loader-balls.svg"
			data-label-original="<?php esc_attr_e( 'Original', 'imagify' ); ?>"
			data-label-normal="<?php esc_attr_e( 'Normal', 'imagify' ); ?>"
			data-label-aggressive="<?php esc_attr_e( 'Aggressive', 'imagify' ); ?>"
			data-label-ultra="<?php esc_attr_e( 'Ultra', 'imagify' ); ?>"

			data-original-label="<?php esc_attr_e( 'Original', 'imagify' ); ?>"
			data-original-img="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>mushrooms-original.jpg"
			data-original-dim="1220x350"
			data-original-alt="<?php echo esc_attr( $original_alt ); ?>"

			data-normal-label="<?php esc_attr_e( 'Normal', 'imagify' ); ?>"
			data-normal-img="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>mushrooms-normal.jpg"
			data-normal-dim="1220x350"
			data-normal-alt="<?php echo esc_attr( $normal_alt ); ?>"

			data-aggressive-label="<?php esc_attr_e( 'Aggressive', 'imagify' ); ?>"
			data-aggressive-img="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>mushrooms-aggressive.jpg"
			data-aggressive-dim="1220x350"
			data-aggressive-alt="<?php echo esc_attr( $aggressive_alt ); ?>"

			data-ultra-label="<?php esc_attr_e( 'Ultra', 'imagify' ); ?>"
			data-ultra-img="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>mushrooms-ultra.jpg"
			data-ultra-dim="1220x350"
			data-ultra-alt="<?php echo esc_attr( $ultra_alt ); ?>">
		</div>

		<div class="imagify-comparison-levels">
			<div class="imagify-c-level imagify-level-original go-left">
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php esc_html_e( 'Original', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'File Size:', 'imagify' ); ?></span>
					<span class="value"><?php echo esc_html( imagify_size_format( 343040 ) ); ?></span>
				</p>
			</div>
			<div class="imagify-c-level imagify-level-optimized imagify-level-normal" aria-hidden="true">
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php esc_html_e( 'Normal', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'File Size:', 'imagify' ); ?></span>
					<span class="value size"><?php echo esc_html( imagify_size_format( 301056 ) ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'Original Saving:', 'imagify' ); ?></span>
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
					<span class="label"><?php esc_html_e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php esc_html_e( 'Aggressive', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'File Size:', 'imagify' ); ?></span>
					<span class="value size"><?php echo esc_html( imagify_size_format( 108544 ) ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'Original Saving:', 'imagify' ); ?></span>
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
					<span class="label"><?php esc_html_e( 'Level:', 'imagify' ); ?></span>
					<span class="value level"><?php esc_html_e( 'Ultra', 'imagify' ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'File Size:', 'imagify' ); ?></span>
					<span class="value size"><?php echo esc_html( imagify_size_format( 46080 ) ); ?></span>
				</p>
				<p class="imagify-c-level-row">
					<span class="label"><?php esc_html_e( 'Original Saving:', 'imagify' ); ?></span>
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
			<span class="screen-reader-text"><?php esc_html_e( 'Close', 'imagify' ); ?></span>
		</button>
	</div>
</div>

<?php
