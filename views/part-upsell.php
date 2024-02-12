<?php

use Imagify\User\User;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$imagify_user = new User();
$unconsumed_quota = $imagify_user ? $imagify_user->get_percent_unconsumed_quota() : 0;
$infinite     = $imagify_user->is_infinite();
$upgrade      = '';
$price        = '';
$upgrade_link = '';
$user_id      = get_current_user_id();
$notices      = get_user_meta( $user_id, '_imagify_ignore_notices', true );
$notices      = $notices && is_array( $notices ) ? array_flip( $notices ) : [];
$api_key_valid = Imagify_Requirements::is_api_key_valid();

if (
	$imagify_user->is_free()
	&&
	$api_key_valid
	&&
	$unconsumed_quota > 20
) {
	?>
	<div class="imagify-col-content imagify-block-secondary imagify-mt2">
		<div class="best-plan<?php echo $api_key_valid ? '' : ' hidden'; ?>">
			<h3 class="imagify-user-best-plan-title">
				<?php esc_html_e( 'You\'re new to Imagify?', 'imagify' ); ?>
			</h3>
			<p><?php esc_html_e( 'Let us help you by analyzing your existing images and determine the best plan for you.', 'imagify' ); ?></p>
			<button id="imagify-get-pricing-modal" data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-light imagify-full-width">
				<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
				<span class="button-text"><?php esc_html_e( 'What plan do I need?', 'imagify' ); ?></span>
			</button>
		</div>
	</div><!-- .imagify-col-content -->
	<?php
}

if (
	Imagify_Requirements::is_api_key_valid()
	&&
	! $infinite
	&&
	! isset( $notices['upsell-banner'] )
	&&
	$unconsumed_quota <= 20
	) {
	?>
<div class="imagify-col-content imagify-upsell">
	<div class="imagify-flex imagify-vcenter">
		<span class="imagify-meteo-icon imagify-noshrink"><?php echo $this->get_quota_icon(); ?></span>
		<div class="imagify-space-left imagify-full-width">
			<p>
				<?php
				printf(
					/* translators: %s is a data quota. */
					__( 'You have %s space credit left', 'imagify' ),
					'<span class="imagify-unconsumed-percent">' . $this->get_quota_percent() . '%</span>'
				);
				?>
			</p>

			<div class="<?php echo $this->get_quota_class(); ?>">
				<div class="imagify-unconsumed-bar imagify-progress" style="width: <?php echo $this->get_quota_percent() . '%'; ?>;"></div>
			</div>
		</div>
	</div>
	<?php
	if ( $imagify_user->is_free() ) {
		$upgrade = esc_html__( 'Upgrade your plan now for more!', 'imagify' );
		$price = esc_html__( 'From $5.99/month only, keep going with image optimization!', 'imagify' );
		$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/?utm_source=plugin&utm_medium=upsell_banner';
	} elseif ( $imagify_user->is_growth() ) {
		$upgrade = esc_html__( 'Upgrade your plan now to keep optimizing your images.', 'imagify' );

		if ( $imagify_user->is_monthly ) {
			$price = esc_html__( 'For $9.99/month only, choose unlimited image optimization!', 'imagify' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=1&utm_source=plugin&utm_medium=upsell_banner';
		} else {
			$price = esc_html__( 'For $99.9/year only, choose unlimited image optimization!', 'imagify' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=2&utm_source=plugin&utm_medium=upsell_banner';
		}
	}
	?>
	<p><?php echo $upgrade; ?></p>
	<p><?php echo $price; ?></p>

	<a href="<?php echo esc_url( $upgrade_link ); ?>" target="_blank" rel="noopener noreferrer" class="imagify-upsell-button"><span class="imagify-upsell-arrow"><?php esc_html_e( 'Upgrade now', 'imagify' ); ?></span></a>
	<a href="<?php echo esc_url( get_imagify_admin_url( 'dismiss-notice', 'upsell-banner' ) ); ?>" class="imagify-notice-dismiss imagify-upsell-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'imagify' ); ?></span></a>
</div><!-- .imagify-col-content -->
	<?php
}
?>
