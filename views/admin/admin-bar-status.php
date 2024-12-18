<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$pos = strpos( $data['plan_label'], '_' );
$plan_label = false !== $pos ? substr( $data['plan_label'], 0, $pos ) : $data['plan_label'];
?>
<div class="imagify-admin-bar-quota">
	<div class="imagify-abq-row">
		<?php if ( $data['plan_with_quota'] ) : ?>
		<div class="imagify-meteo-icon"><?php echo $data['quota_icon']; ?></div>
		<?php endif; ?>
		<div class="imagify-account">
			<p class="imagify-meteo-title"><?php esc_html_e( 'Account status', 'imagify' ); ?></p>
			<p class="imagify-meteo-subs"><?php esc_html_e( 'Your subscription:', 'imagify' ); ?> &nbsp;<strong class="imagify-user-plan"><?php echo $plan_label; ?></strong></p>
		</div>
	</div>
	<?php if ( $data['plan_with_quota'] ) : ?>
	<div class="imagify-abq-row">
		<div class="imagify-space-left">
			<p><?php
				printf(
				// translators: %s = percentage.
				__( 'You have %s space credit left', 'imagify' ), '<span class="imagify-unconsumed-percent">' . $data['unconsumed_quota'] . '%</span>' );
				?></p>
			<div class="<?php echo esc_attr( $data['quota_class'] ); ?>">
				<div style="width: <?php echo esc_attr( $data['unconsumed_quota'] ); ?>%;" class="imagify-unconsumed-bar imagify-progress"></div>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<?php if ( $data['plan_with_quota'] && $data['unconsumed_quota'] <= 20 ) : ?>
	<div class="imagify-upsell-admin-bar">
		<?php if ( $data['unconsumed_quota'] <= 20 ) : ?>
		<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong><?php esc_html_e( 'Oops, It\'s almost over!', 'imagify' ); ?></strong></p>
		<?php elseif ( 0 === $data['unconsumed_quota'] ) : ?>
		<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong><?php esc_html_e( 'Oops, It\'s Over!', 'imagify' ); ?></strong></p>
		<?php endif; ?>
		<p><?php echo $data['text']; ?></p>
		<p class="center txt-center text-center"><a class="imagify-upsell-admin-bar-button" href="<?php echo esc_url( $data['upgrade_link'] ); ?>" target="_blank"><?php echo $data['button_text']; ?></a></p>
		<a href="<?php echo esc_url( get_imagify_admin_url( 'dismiss-notice', 'upsell-admin-bar' ) ); ?>" class="imagify-notice-dismiss imagify-upsell-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'imagify' ); ?></span></a>
	</div>
	<?php endif; ?>
</div>
