<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="imagify-admin-bar-quota">
	<div class="imagify-abq-row">
		<?php if ( $data['plan_with_quota'] ): ?>
		<div class="imagify-meteo-icon"><?php echo $data['quota_icon']; ?></div>
		<?php endif; ?>
		<div class="imagify-account">
			<p class="imagify-meteo-title"><?php esc_html_e( 'Account status', 'imagify' ); ?></p>
			<p class="imagify-meteo-subs"><?php esc_html_e( 'Your subscription:', 'imagify' ); ?> &nbsp;<strong class="imagify-user-plan"><?php echo $data['plan_label']; ?></strong></p>
		</div>
	</div>
	<?php if ( $data['plan_with_quota'] ): ?>
	<div class="imagify-abq-row">
		<div class="imagify-space-left">
			<p><?php printf( __( 'You have %s space credit left', 'imagify' ), '<span class="imagify-unconsumed-percent">' . $data['unconsumed_quota'] . '%</span>' ); ?></p>
			<div class="<?php echo esc_attr( $data['quota_class'] ); ?>">
				<div style="width: <?php echo esc_attr( $data['unconsumed_quota'] ); ?>%;" class="imagify-unconsumed-bar imagify-progress"></div>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<?php if ( $data['unconsumed_quota'] <= 20 ) : ?>
	<div class="imagify-error">
		<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong><?php esc_html_e( 'Oops, It\'s almost over!', 'imagify' ); ?></strong></p>
		<p><?php printf(
			/* translators: %s is a line break. */
			__( 'You have almost used all your credit.%sDon\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ), '<br/><br/>' ); ?></p>
		<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php esc_html_e( 'View My Subscription', 'imagify' ); ?></a></p>
	</div>
	 <?php elseif ( 0 === $data['unconsumed_quota'] ) : ?>
	<div class="imagify-error">';
		<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong><?php esc_html_e( 'Oops, It\'s Over!', 'imagify' ); ?></strong></p>
		<p><?php printf(
					/* translators: 1 is a data quota, 2 is a date. */
					__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
					imagify_size_format( $data['user_quota'] * pow( 1024, 2 ) ),
					date_i18n( get_option( 'date_format' ), strtotime( $data['next_date_update'] ) )
		); ?></p>
		<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php esc_html_e( 'Upgrade My Subscription', 'imagify' ); ?></a></p>
	</div>
	<?php endif; ?>
</div>
