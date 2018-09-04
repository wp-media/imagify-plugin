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
						/* translators: 1 and 2 are data sizes. */
						__( 'you saved %1$s out of %2$s', 'imagify' ),
						'<strong class="imagify-ac-rt-total-gain"></strong>',
						'<strong class="imagify-ac-rt-total-original"></strong>'
					);
					?>
				</p>
			</div>
		</div>
		<div class="imagify-ac-share">
			<div class="imagify-ac-share-content">
				<p><?php _e( 'Share your awesome result', 'imagify' ); ?></p>
				<ul class="imagify-share-networks">
					<li>
						<a target="_blank" class="imagify-sn-twitter" href="<?php echo esc_url( imagify_get_external_url( 'share-twitter' ) ); ?>"><svg viewBox="0 0 23 18" width="23" height="18" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><title>Twitter</title><path d="m244.15 12.13c-.815.361-1.691.606-2.61.716.939-.563 1.659-1.453 1.998-2.514-.878.521-1.851.898-2.886 1.103-.829-.883-2.01-1.435-3.317-1.435-2.51 0-4.544 2.034-4.544 4.544 0 .356.04.703.118 1.035-3.777-.19-7.125-1.999-9.367-4.748-.391.671-.615 1.452-.615 2.285 0 1.576.802 2.967 2.02 3.782-.745-.024-1.445-.228-2.058-.568-.001.019-.001.038-.001.057 0 2.202 1.566 4.04 3.646 4.456-.381.104-.783.159-1.197.159-.293 0-.577-.028-.855-.081.578 1.805 2.256 3.119 4.245 3.156-1.555 1.219-3.515 1.945-5.644 1.945-.367 0-.728-.021-1.084-.063 2.01 1.289 4.399 2.041 6.966 2.041 8.359 0 12.929-6.925 12.929-12.929 0-.197-.004-.393-.013-.588.888-.64 1.658-1.44 2.268-2.352" transform="translate(-222-10)" fill="#fff"/></g></svg></a>
					</li>
					<li>
						<a target="_blank" class="imagify-sn-facebook" href="<?php echo esc_url( imagify_get_external_url( 'share-facebook' ) ); ?>"><svg viewBox="0 0 18 18" width="18" height="18" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><title>Facebook</title><path d="m203.25 10h-16.5c-.415 0-.75.336-.75.75v16.5c0 .414.336.75.75.75h8.812v-6.75h-2.25v-2.813h2.25v-2.25c0-2.325 1.472-3.469 3.546-3.469.993 0 1.847.074 2.096.107v2.43h-1.438c-1.128 0-1.391.536-1.391 1.322v1.859h2.813l-.563 2.813h-2.25l.045 6.75h4.83c.414 0 .75-.336.75-.75v-16.5c0-.414-.336-.75-.75-.75" transform="translate(-186-10)" fill="#fff"/></g></svg></a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
