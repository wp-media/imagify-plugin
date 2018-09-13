<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
	</div>
	<?php if ( ! empty( $data['dismissible'] ) ) : ?>
		<a href="<?php echo esc_url( get_imagify_admin_url( 'dismiss-notice', $data['dismissible'] ) ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	<?php endif; ?>
</div>
