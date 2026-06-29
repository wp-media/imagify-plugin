<?php
defined( 'ABSPATH' ) || exit;
/**
 * Variables passed from Notices::render_optin_section():
 *
 * @var bool   $is_enabled
 * @var string $wp_version
 * @var string $php_version
 * @var string $plugin_version
 * @var string $opt_level
 * @var string $next_gen
 * @var string $license_type
 */
?>
<div class="imagify-analytics-optin imagify-setting-line">
	<label class="imagify-analytics-label" for="imagify-analytics-enabled">
		<input
			type="checkbox"
			id="imagify-analytics-enabled"
			class="imagify-analytics-checkbox"
			value="1"
			<?php checked( $is_enabled, true ); ?>
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'imagify_tracking_optin' ) ); ?>"
		>
		<span class="imagify-analytics-toggle-ui" aria-hidden="true"></span>
		<?php esc_html_e( 'Imagify Analytics', 'imagify' ); ?>
	</label>
	<p class="imagify-analytics-description">
		<?php
		printf(
			/* translators: %1$s = opening <button> tag, %2$s = closing </button> tag */
			esc_html__( 'I agree to share anonymous data with the development team to help improve Imagify. %1$sWhat info will we collect?%2$s', 'imagify' ),
			'<button type="button" class="imagify-btn-link imagify-modal-trigger" href="#imagify-analytics-info-modal">',
			'</button>'
		);
		?>
	</p>
</div>

<!-- "What info will we collect?" modal -->
<div id="imagify-analytics-info-modal" class="imagify-modal" aria-hidden="true" role="dialog">
	<div class="imagify-modal-inner">
		<button class="close-btn" aria-label="<?php esc_attr_e( 'Close', 'imagify' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<h2><?php esc_html_e( 'Imagify Analytics', 'imagify' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %1$s = <strong>, %2$s = </strong> */
				esc_html__( 'Below is a detailed view of all data Imagify will collect %1$sif granted permission.%2$s', 'imagify' ),
				'<strong>',
				'</strong>'
			);
			?>
		</p>
		<table class="imagify-analytics-data-table">
			<tbody>
				<tr><td><?php esc_html_e( 'WordPress version', 'imagify' ); ?></td><td><?php echo esc_html( $wp_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'PHP version', 'imagify' ); ?></td><td><?php echo esc_html( $php_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Plugin version', 'imagify' ); ?></td><td><?php echo esc_html( $plugin_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Optimization level', 'imagify' ); ?></td><td><?php echo esc_html( $opt_level ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Media type', 'imagify' ); ?></td><td><?php esc_html_e( 'MIME type of the optimized file (jpeg, png, gif…)', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Savings percentage', 'imagify' ); ?></td><td><?php esc_html_e( 'Percentage of file size reduced per optimization', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Next-gen format', 'imagify' ); ?></td><td><?php echo esc_html( $next_gen ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Trigger', 'imagify' ); ?></td><td><?php esc_html_e( 'How the optimization was started (auto, bulk, manual)', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'License type', 'imagify' ); ?></td><td><?php echo esc_html( $license_type ); ?></td></tr>
			</tbody>
		</table>
		<p class="imagify-analytics-modal-footer">
			<?php esc_html_e( 'Imagify will never transmit any email addresses, IP addresses, or API keys.', 'imagify' ); ?>
		</p>
		<?php if ( ! $is_enabled ) : ?>
		<div class="imagify-analytics-modal-cta">
			<button type="button" id="imagify-analytics-enable-from-modal" class="button button-primary">
				<?php esc_html_e( 'Activate Imagify Analytics', 'imagify' ); ?>
			</button>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- "Thank you" modal — shown via JS immediately after enabling -->
<div id="imagify-analytics-thankyou-modal" class="imagify-modal" aria-hidden="true" role="dialog">
	<div class="imagify-modal-inner">
		<button class="close-btn" aria-label="<?php esc_attr_e( 'Close', 'imagify' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
		<h2><?php esc_html_e( 'Thank you!', 'imagify' ); ?></h2>
		<p><?php esc_html_e( 'Imagify now collects these metrics from your website:', 'imagify' ); ?></p>
		<table class="imagify-analytics-data-table">
			<tbody>
				<tr><td><?php esc_html_e( 'WordPress version', 'imagify' ); ?></td><td><?php echo esc_html( $wp_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'PHP version', 'imagify' ); ?></td><td><?php echo esc_html( $php_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Plugin version', 'imagify' ); ?></td><td><?php echo esc_html( $plugin_version ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Optimization level', 'imagify' ); ?></td><td><?php echo esc_html( $opt_level ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Media type', 'imagify' ); ?></td><td><?php esc_html_e( 'MIME type of the optimized file (jpeg, png, gif…)', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Savings percentage', 'imagify' ); ?></td><td><?php esc_html_e( 'Percentage of file size reduced per optimization', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Next-gen format', 'imagify' ); ?></td><td><?php echo esc_html( $next_gen ); ?></td></tr>
				<tr><td><?php esc_html_e( 'Trigger', 'imagify' ); ?></td><td><?php esc_html_e( 'How the optimization was started (auto, bulk, manual)', 'imagify' ); ?></td></tr>
				<tr><td><?php esc_html_e( 'License type', 'imagify' ); ?></td><td><?php echo esc_html( $license_type ); ?></td></tr>
			</tbody>
		</table>
	</div>
</div>
