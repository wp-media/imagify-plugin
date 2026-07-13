<?php
defined( 'ABSPATH' ) || exit;
/**
 * Variables passed from Notices::render_thankyou_notice():
 *
 * @var string $wp_version
 * @var string $php_version
 * @var string $plugin_version
 * @var string $opt_level
 * @var string $next_gen
 * @var string $license_type
 */
?>
<div class="notice notice-success is-dismissible imagify-analytics-thankyou-notice">
	<p><strong><?php esc_html_e( 'Thank you!', 'imagify' ); ?></strong></p>
	<p><?php esc_html_e( 'Imagify now collects these metrics from your website:', 'imagify' ); ?></p>
	<?php require IMAGIFY_PATH . 'views/part-analytics-data-table.php'; ?>
	<p><em><?php esc_html_e( 'We never collect your images, their content, or any personal data — only anonymous performance metrics.', 'imagify' ); ?></em></p>
</div>
