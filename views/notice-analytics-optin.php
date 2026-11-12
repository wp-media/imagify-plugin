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
		<?php require IMAGIFY_PATH . 'views/part-analytics-data-table.php'; ?>
	</div>
	<p>
		<a href="<?php echo esc_url( $accept_url ); ?>" class="button button-primary"><?php esc_html_e( 'Yes, allow', 'imagify' ); ?></a>
		<a href="<?php echo esc_url( $decline_url ); ?>" class="button button-secondary"><?php esc_html_e( 'No, thanks', 'imagify' ); ?></a>
	</p>
</div>
