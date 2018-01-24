<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( imagify_valid_key() ) {
	$user             = imagify_get_cached_user();
	$unconsumed_quota = $user ? $user->get_percent_unconsumed_quota : false;
	$hidden_class     = '';

	if ( ! $user ) {
		Imagify_Assets::get_instance()->localize_script( 'options', 'imagifyUser', array(
			'action'   => 'imagify_get_user_data',
			'_wpnonce' => wp_create_nonce( 'imagify_get_user_data' ),
		) );
	}
} else {
	$hidden_class = ' hidden';
}
?>
<div class="imagify-settings-section">
	<?php if ( imagify_valid_key() ) { ?>
		<h2 class="imagify-options-title">
			<?php _e( 'Account Type', 'imagify' ); ?>
			<strong class="imagify-user-plan-label"><?php echo $user ? esc_html( $user->plan_label ) : ''; ?></strong>
		</h2>
	<?php } else { ?>
		<h2 class="imagify-options-title"><?php _e( 'Your account', 'imagify' ); ?></h2>
	<?php } ?>

	<?php
	if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) {
		/**
		 * API key field.
		 */
		$options = Imagify_Options::get_instance();
		?>
		<div class="imagify-api-line">
			<label for="api_key"><?php _e( 'API Key', 'imagify' ); ?></label>
			<input type="text" size="35" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $options->get_option_name(); ?>[api_key]" id="api_key">
			<?php
			if ( imagify_valid_key() ) {
				?>

				<span id="imagify-check-api-container" class="imagify-valid">
					<span class="imagify-icon">✓</span> <?php _e( 'Your API key is valid.', 'imagify' ); ?>
				</span>

				<?php
			} elseif ( ! imagify_valid_key() && $options->get( 'api_key' ) ) {
				?>

				<span id="imagify-check-api-container">
					<span class="dashicons dashicons-no"></span> <?php _e( 'Your API key isn\'t valid!', 'imagify' ); ?>
				</span>

				<?php
			}

			if ( ! $options->get( 'api_key' ) ) {
				echo '<p class="description desc api_key">';
				printf(
					/* translators: 1 is a link tag start, 2 is the link tag end. */
					__( 'Don\'t have an API Key yet? %1$sCreate one, it\'s FREE%2$s.', 'imagify' ),
					'<a id="imagify-signup" href="' . esc_url( imagify_get_external_url( 'register' ) ) . '" target="_blank">',
					'</a>'
				);
				echo '</p>';
			}
			?>
			<input id="check_api_key" type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="check_api_key">
		</div><!-- .imagify-api-line -->
		<?php
	}

	if ( imagify_valid_key() ) {
		?>
		<div class="imagify-col-content">
			<?php
			/**
			 * Remaining quota.
			 */
			if ( ! $user || ( $unconsumed_quota <= 20 && $unconsumed_quota > 0 ) ) {
				if ( ! $user ) {
					echo '<div class="imagify-user-is-almost-over-quota hidden">';
				}
				?>
				<p><strong><?php _e( 'Oops, It\'s almost over!', 'imagify' ); ?></strong></p>
				<p><?php _e( 'You have almost used all your credit. Don\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ); ?></p>
				<p><a class="button imagify-button-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php _e( 'View My Subscription', 'imagify' ); ?></a></p>
				<?php
				if ( ! $user ) {
					echo '</div>';
				}
			}

			if ( ! $user || 0 === $unconsumed_quota ) {
				if ( ! $user ) {
					echo '<div class="imagify-user-is-over-quota hidden">';
				}
				?>
				<p><strong><?php _e( 'Oops, It\'s Over!', 'imagify' ); ?></strong></p>
				<p>
					<?php
					printf(
						/* translators: 1 is a data quota, 2 is a date. */
						__( 'You have consumed all your credit for this month. You will have <strong>%1$s back on %2$s</strong>.', 'imagify' ),
						'<span class="imagify-user-quota-formatted">' . ( $user ? esc_html( $user->quota_formatted ) : '' ) . '</span>',
						'<span class="imagify-user-next-date-update-formatted">' . ( $user ? esc_html( $user->next_date_update_formatted ) : '' ) . '</span>'
					);
					?>
				</p>
				<p class="center txt-center text-center"><a class="btn imagify-btn-ghost" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>" target="_blank"><?php _e( 'Upgrade My Subscription', 'imagify' ); ?></a></p>
				<?php
				if ( ! $user ) {
					echo '</div>';
				}
			}

			/**
			 * Best plan.
			 */
			?>
			<div class="<?php echo $hidden_class; ?>">
				<p><?php _e( 'We can also analyze your needs to prevent you to buy a plan that doesn\'t suit you.', 'imagify' ); ?><br>
				<?php _e( 'Our analyze will allow you to choose a better plan for your needs.', 'imagify' ); ?></p>

				<h3><?php _e( 'You’re new to Imagify', 'imagify' ); ?></h3>

				<p><?php _e( 'Let us help you by analyzing your existing images and determine the best plan for you.', 'imagify' ); ?></p>

				<button id="imagify-get-pricing-modal" data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-secondary">
					<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
					<span class="button-text"><?php _e( 'What plan do I need?', 'imagify' ); ?></span>
				</button>
			</div>
		</div><!-- .imagify-col-content -->
		<?php
	}
	?>
</div>
<?php
