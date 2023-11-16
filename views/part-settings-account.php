<?php

use Imagify\User\User;

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( defined( 'IMAGIFY_HIDDEN_ACCOUNT' ) && IMAGIFY_HIDDEN_ACCOUNT ) {
	if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) {
		$options = Imagify_Options::get_instance();
		?>
		<input type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $options->get_option_name(); ?>[api_key]">
		<?php
	}
	return;
}

if ( Imagify_Requirements::is_api_key_valid() ) {
	$user = imagify_get_cached_user();

	if ( ! $user ) {
		// Lazyload user.
		Imagify_Assets::get_instance()->localize_script( 'options', 'imagifyUser', array(
			'action'   => 'imagify_get_user_data',
			'_wpnonce' => wp_create_nonce( 'imagify_get_user_data' ),
		) );
	}
}
?>
<div class="imagify-settings-section">
<?php $this->print_template( 'part-upsell' ); ?>

	<?php
	if ( ! defined( 'IMAGIFY_API_KEY' ) || ! IMAGIFY_API_KEY ) {
		if ( Imagify_Requirements::is_api_key_valid() ) {
			?>
			<h2 class="imagify-options-title">
				<?php esc_html_e( 'API Key', 'imagify' ); ?>
			</h2>
			<?php
		} else {
			?>
			<h2 class="imagify-options-title"><?php esc_html_e( 'Your Account', 'imagify' ); ?></h2>
			<p class="imagify-options-subtitle"><?php esc_html_e( 'Options page isn’t available until you enter your API Key', 'imagify' ); ?></p>
			<?php
		}
		?>

		<?php
		/**
		 * API key field.
		 */
		$options = Imagify_Options::get_instance();

		if ( ! $options->get( 'api_key' ) ) {
			?>
			<p class="imagify-api-key-invite"><?php esc_html_e( 'Don\'t have an API Key yet?', 'imagify' ); ?></p>

			<p><a id="imagify-signup" class="button imagify-button-secondary" href="<?php echo esc_url( imagify_get_external_url( 'register' ) ); ?>" target="_blank"><?php esc_html_e( 'Create a Free API Key', 'imagify' ); ?></a></p>
			<?php
		}
		?>

		<div class="imagify-api-line">
			<label for="api_key" class="screen-reader-text"><?php echo $options->get( 'api_key' ) ? esc_html__( 'API Key', 'imagify' ) : esc_html__( 'Enter Your API Key Below', 'imagify' ); ?></label>
			<input type="text" size="35" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="<?php echo $options->get_option_name(); ?>[api_key]" id="api_key">
			<?php
			if ( Imagify_Requirements::is_api_key_valid() ) {
				?>

				<span id="imagify-check-api-container" class="imagify-valid">
					<span class="imagify-icon">✓</span> <?php esc_html_e( 'Your API key is valid.', 'imagify' ); ?>
				</span>

				<?php
			} elseif ( ! Imagify_Requirements::is_api_key_valid() && $options->get( 'api_key' ) ) {
				?>

				<span id="imagify-check-api-container">
					<span class="dashicons dashicons-no"></span> <?php esc_html_e( 'Your API key isn\'t valid!', 'imagify' ); ?>
				</span>

				<?php
			}
			?>
			<input id="check_api_key" type="hidden" value="<?php echo esc_attr( $options->get( 'api_key' ) ); ?>" name="check_api_key">
		</div><!-- .imagify-api-line -->
		<?php
	}
	?>
</div>
<?php
