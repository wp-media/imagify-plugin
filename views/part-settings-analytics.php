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
	<div class="imagify-modal-content">
		<button type="button" class="close-btn">
			<i class="dashicons dashicons-no-alt" aria-hidden="true"></i>
			<span class="screen-reader-text"><?php esc_attr_e( 'Close', 'imagify' ); ?></span>
		</button>
		<p class="h2"><?php esc_html_e( 'Imagify Analytics', 'imagify' ); ?></p>
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
		<?php require IMAGIFY_PATH . 'views/part-analytics-data-table.php'; ?>
		<p>
			<em><?php esc_html_e( 'We never collect your images, their content, or any personal data — only anonymous performance metrics.', 'imagify' ); ?></em>
		</p>
		<?php if ( ! $is_enabled ) : ?>
		<p>
			<button type="button" id="imagify-analytics-enable-from-modal" class="button button-primary">
				<?php esc_html_e( 'Activate Imagify Analytics', 'imagify' ); ?>
			</button>
		</p>
		<?php endif; ?>
	</div>
</div>
