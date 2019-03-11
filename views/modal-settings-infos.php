<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>

<div class="imagify-modal" id="imagify-more-info">
	<div class="imagify-modal-content">
		<p class="h2"><?php _e( 'You can choose three levels of compression', 'imagify' ); ?></p>
		<div class="imagify-columns">
			<div class="col-1-3">
				<p class="h3"><?php _e( 'Normal', 'imagify' ); ?></p>
				<p>
					<?php _e( 'This mode provides lossless optimization, your images will be optimized without any visible change.', 'imagify' ); ?>
				</p>
				<p>
					<?php _e( 'If you want the perfect quality for your images, we recommend you that mode.', 'imagify' ); ?>
				</p>
				<p>
					<em><?php _e( 'Note: the file size reduction will be less, compared to aggressive mode.', 'imagify' ); ?></em>
				</p>
			</div>

			<div class="col-1-3">
				<p class="h3"><?php _e( 'Aggressive', 'imagify' ); ?></p>
				<p>
					<?php _e( 'This mode provides perfect optimization of your images without any significant quality loss.', 'imagify' ); ?>
				</p>
				<p>
					<?php _e( 'This will provide a drastic savings on the initial weight, with a small reduction in image quality. Most of the time it\'s not even noticeable.', 'imagify' ); ?>
				</p>
				<p>
					<?php _e( 'If you want the maximum weight reduction, we recommend using this mode.', 'imagify' ); ?>
				</p>
			</div>

			<div class="col-1-3">
				<p class="h3"><?php _e( 'Ultra', 'imagify' ); ?></p>
				<p>
					<?php _e( 'This mode will apply all available optimizations for maximum image compression.', 'imagify' ); ?>
				</p>
				<p>
					<?php _e( 'This will provide a huge savings on the initial weight. Sometimes the image quality could be degraded a little.', 'imagify' ); ?>
				</p>
				<p>
					<?php _e( 'If you want the maximum weight reduction, and you agree to lose some quality on the images we recommend using this mode.', 'imagify' ); ?>
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
