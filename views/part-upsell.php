<?php

use Imagify\User\User;

defined( 'ABSPATH' ) || exit;

$imagify_user     = new User();
$unconsumed_quota = $imagify_user ? $imagify_user->get_percent_unconsumed_quota() : 0;
$infinite         = $imagify_user->is_infinite();
$upgrade          = '';
$price            = '';
$upgrade_link     = '';
$user_id          = get_current_user_id();
$notices          = get_user_meta( $user_id, '_imagify_ignore_notices', true );
$notices          = $notices && is_array( $notices ) ? array_flip( $notices ) : [];
$api_key_valid    = Imagify_Requirements::is_api_key_valid();

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
				<?php esc_html_e( 'Unlock Imagify\'s full potential', 'imagify' ); ?>
			</h3>
			<p><?php esc_html_e( 'Expand your image quota and eliminate upload limits.', 'imagify' ); ?></p>
			<button data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-light imagify-full-width imagify-upsell-cta imagify-get-pricing-modal">
				<span class="button-text"><?php esc_html_e( 'Upgrade Now', 'imagify' ); ?></span>
				<svg class="imagify-svg-icon" width="23px" height="23px">
					<use href="#imagify_arrow_long_right"></use>
				</svg>
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
		$upgrade      = esc_html__( 'Upgrade your plan now for more!', 'imagify' );
		$price        = esc_html__( 'From $5.99/month only, keep going with image optimization!', 'imagify' );
		$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/?utm_source=plugin&utm_medium=upsell_banner';
	} elseif ( $imagify_user->is_growth() ) {
		$upgrade = esc_html__( 'Upgrade your plan now to keep optimizing your images.', 'imagify' );

		if ( $imagify_user->is_monthly ) {
			$price        = esc_html__( 'For $11.99/month only, choose unlimited image optimization!', 'imagify' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=1&utm_source=plugin&utm_medium=upsell_banner';
		} else {
			$price        = esc_html__( 'For $9.99/month only, choose unlimited image optimization!', 'imagify' );
			$upgrade_link = IMAGIFY_APP_DOMAIN . '/subscription/plan_switch/?label=infinite&payment_plan=2&utm_source=plugin&utm_medium=upsell_banner';
		}
	}
	?>
	<p><?php echo $upgrade; ?></p>
	<p><?php echo $price; ?></p>

	<a href="<?php echo esc_url( $upgrade_link ); ?>" target="_blank" rel="noopener noreferrer" class="imagify-upsell-button imagify-full-width imagify-upsell-cta">
		<?php esc_html_e( 'Upgrade now', 'imagify' ); ?>
		<svg class="imagify-svg-icon" width="23px" height="23px">
			<use href="#imagify_arrow_long_right"></use>
		</svg>
	</a>
	<a href="<?php echo esc_url( get_imagify_admin_url( 'dismiss-notice', 'upsell-banner' ) ); ?>" class="imagify-notice-dismiss notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'imagify' ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'imagify' ); ?></span></a>
</div><!-- .imagify-col-content -->
	<?php
}
?>

<svg style="display: none;">
	<symbol id="imagify_arrow_long_right" viewBox="0 0 512.00 512.00">
		<path d="M313.941 216H12c-6.627 0-12 5.373-12 12v56c0 6.627 5.373 12 12 12h301.941v46.059c0 21.382 25.851 32.09 40.971 16.971l86.059-86.059c9.373-9.373 9.373-24.569 0-33.941l-86.059-86.059c-15.119-15.119-40.971-4.411-40.971 16.971V216z" fill="currentColor"/>
	</symbol>
</svg>

