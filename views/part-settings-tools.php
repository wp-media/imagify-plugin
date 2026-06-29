<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="imagify-settings-section">
	<h2 class="imagify-options-title"><?php esc_html_e( 'Troubleshooting', 'imagify' ); ?></h2>
	<p>
		<?php esc_html_e( 'If image optimization gets stuck or bulk optimization never completes, use this button to clear internal optimization locks, running-state transients, and pending scheduled jobs. Your settings and optimized images will not be affected.', 'imagify' ); ?>
	</p>
	<button type="button"
		id="imagify-reset-internal-state"
		class="button imagify-button-primary"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'imagify_reset_internal_state' ) ); ?>">
		<?php esc_html_e( 'Reset Internal State', 'imagify' ); ?>
	</button>
	<span id="imagify-reset-internal-state-feedback"></span>
</div>
