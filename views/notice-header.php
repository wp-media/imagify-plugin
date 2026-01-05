<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<div class="clear"></div>
<div class="imagify-notice below-h2<?php echo ( ! empty( $data['classes'] ) ? ' ' . implode( ' ', $data['classes'] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<div class="imagify-notice-logo">
		<img class="imagify-logo" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL ); ?>imagify-logo.png" width="138" height="16" alt="Imagify" />
	</div>
	<div class="imagify-notice-content">
		<?php if ( ! empty( $data['title'] ) ) : ?>
			<p class="imagify-notice-title"><strong><?php echo esc_html( $data['title'] ); ?></strong></p>
		<?php endif; ?>
