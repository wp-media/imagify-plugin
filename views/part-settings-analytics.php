<?php
defined( 'ABSPATH' ) || exit;
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
			'<button type="button" class="imagify-btn-link imagify-modal-trigger" href="#imagify-analytics-modal">',
			'</button>'
		);
		?>
	</p>
</div>

<div id="imagify-analytics-modal" class="imagify-modal" aria-hidden="true" role="dialog">
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
				<tr>
					<td><?php esc_html_e( 'WordPress version', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'WordPress version number', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'PHP version', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'PHP version number', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Plugin version', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'Imagify plugin version number', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Optimization level', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'The compression level used (normal, aggressive, ultra)', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Media type', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'The MIME type of the optimized file', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Savings percentage', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'How much the file size was reduced', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Next-gen format', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'Whether AVIF or WebP was generated', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Trigger', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'How the optimization was started (auto upload, bulk, manual)', 'imagify' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'License type', 'imagify' ); ?></td>
					<td><?php esc_html_e( 'Your Imagify license tier', 'imagify' ); ?></td>
				</tr>
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
