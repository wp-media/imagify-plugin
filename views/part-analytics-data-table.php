<?php
defined( 'ABSPATH' ) || exit;
/**
 * The analytics data-preview table, shared by the settings opt-in modal,
 * the opt-in ask banner, and the thank-you notice.
 *
 * @var string $wp_version
 * @var string $php_version
 * @var string $plugin_version
 * @var string $opt_level
 * @var string $next_gen
 * @var string $license_type
 */
?>
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
