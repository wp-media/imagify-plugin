<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$user = new Imagify_User();
?>

<h1 class="screen-reader-text"><?php _e( 'Bulk Optimization', 'imagify' ); ?> – Imagify <?php echo IMAGIFY_VERSION; ?></h1>

<div class="imagify-title">

	<?php if ( ! defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) || false === IMAGIFY_HIDDEN_ACCOUNT ) { ?>

		<div class="imagify-title-right">
			<div class="imagify-account">
				<p class="imagify-meteo-title"><?php _e( 'Account status', 'imagify' ); ?></p>
				<p class="imagify-meteo-subs"><?php _e( 'Your subscription:', 'imagify' ); ?>&nbsp;<strong class="imagify-user-plan"><?php echo $user->plan_label; ?></strong></p>
			</div>
			<div class="imagify-account-link">
				<a href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" class="button button-ghost" target="_blank">
					<span class="dashicons dashicons-admin-users"></span>
					<span class="button-text"><?php _e( 'View My Subscription', 'imagify' ); ?></span>
				</a>
			</div>

			<?php if ( 1 === $user->plan_id ) { ?>

				<div class="imagify-sep-v"></div>
				<div class="imagify-credit-left">
					<?php
					$unconsumed_quota  = $user->get_percent_unconsumed_quota();
					$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="37" height="38" alt="" />';
					$bar_class         = 'positive';
					$is_display_bubble = false;

					if ( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
						$bar_class  = 'neutral';
						$meteo_icon = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="37" height="38" alt="" />';
					} elseif ( $unconsumed_quota <= 20 ) {
						$bar_class         = 'negative';
						$is_display_bubble = true;
						$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="38" height="36" alt="" />';
					}
					?>
					<span class="imagify-meteo-icon"><?php echo $meteo_icon; ?></span>
					<div class="imagify-space-left">

						<p>
							<?php
							printf(
								/* translators: %s is a data quota. */
								__( 'You have %s space credit left' , 'imagify' ),
								'<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>'
							);
							?>
						</p>

						<div class="imagify-bar-<?php echo $bar_class; ?>">
							<div class="imagify-unconsumed-bar imagify-progress" style="width: <?php echo $unconsumed_quota . '%'; ?>;"></div>
						</div>
					</div>
					<div class="imagify-space-tooltips imagify-tooltips <?php echo ( ! $is_display_bubble ) ? 'hidden' : ''; ?>">
						<div class="tooltip-content tooltip-table">
							<div class="cell-icon">
								<span aria-hidden="true" class="icon icon-round">i</span>
							</div>
							<div class="cell-text">
								<?php _e( 'Upgrade your account to continue optimizing your images', 'imagify' ); ?>
							</div>
							<div class="cell-sep"></div>
							<div class="cell-cta">
								<a href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php _e( 'More info', 'imagify' ); ?></a>
							</div>
						</div>
					</div>
				</div>

			<?php } // End if(). ?>

		</div>

	<?php } // End if(). ?>

	<img width="191" height="22" alt="Imagify" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" class="imagify-logo" />
</div>
<?php
