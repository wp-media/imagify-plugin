<?php
defined( 'ABSPATH' ) || exit;

$reset_url = wp_nonce_url(
	self_admin_url( 'admin-post.php?action=imagify_reset_internal_state' ),
	'imagify-reset-internal-state'
);
?>
<div class="imagify-settings-section imagify-clear">
	<h2 class="imagify-options-title"><?php esc_html_e( 'Troubleshooting', 'imagify' ); ?></h2>
	<p>
		<?php esc_html_e( 'If optimization appears stuck, reset Imagify internal state to clear transient and queue data without changing your settings.', 'imagify' ); ?>
	</p>
	<p>
		<a class="button imagify-button-secondary" href="<?php echo esc_url( $reset_url ); ?>">
			<?php esc_html_e( 'Reset internal state', 'imagify' ); ?>
		</a>
	</p>
</div>
