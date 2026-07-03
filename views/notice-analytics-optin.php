<?php
defined( 'ABSPATH' ) || exit;
/**
 * Variables passed from Notices::render_optin_notice():
 *
 * @var string $wp_version
 * @var string $php_version
 * @var string $plugin_version
 * @var string $opt_level
 * @var string $next_gen
 * @var string $license_type
 */

$accept_url  = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_analytics_optin&value=yes' ), 'imagify_analytics_optin' );
$decline_url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_analytics_optin&value=no' ), 'imagify_analytics_optin' );
?>
<div class="notice notice-info imagify-analytics-optin-notice">
	<p>
		<strong><?php esc_html_e( 'Would you allow Imagify to collect non-sensitive diagnostic data from this website?', 'imagify' ); ?></strong>
		<br>
		<?php esc_html_e( 'This would help us to improve Imagify for you in the future.', 'imagify' ); ?>
	</p>
	<p>
		<button type="button" class="imagify-btn-link imagify-analytics-preview-toggle"><?php esc_html_e( 'What info will we collect?', 'imagify' ); ?></button>
	</p>
	<div class="imagify-analytics-data-container hidden">
		<p class="description">
			<?php esc_html_e( 'Below is a detailed view of all data Imagify will collect if granted permission. We never collect your images, their content, or any personal data — only anonymous performance metrics.', 'imagify' ); ?>
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
	</div>
	<p>
		<a href="<?php echo esc_url( $accept_url ); ?>" class="button button-primary"><?php esc_html_e( 'Yes, allow', 'imagify' ); ?></a>
		<a href="<?php echo esc_url( $decline_url ); ?>" class="button button-secondary"><?php esc_html_e( 'No, thanks', 'imagify' ); ?></a>
	</p>
</div>
