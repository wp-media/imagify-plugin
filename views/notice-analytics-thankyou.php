<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="notice imagify-notice imagify-analytics-thankyou-notice">
	<button type="button" class="notice-dismiss">
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'imagify' ); ?></span>
	</button>
	<h3><?php esc_html_e( 'Thank you!', 'imagify' ); ?></h3>
	<p><?php esc_html_e( 'Imagify now collects these metrics from your website:', 'imagify' ); ?></p>
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
</div>
