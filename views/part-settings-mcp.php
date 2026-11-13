<?php
defined( 'ABSPATH' ) || exit;
/**
 * Variables passed from SettingsSection::render():
 *
 * @var bool   $is_enabled
 * @var string $option_name
 * @var string $doc_url
 */
?>
<div class="imagify-mcp-option imagify-setting-line">
	<?php // Unchecked boxes are not posted, this stores the disabled state explicitly. ?>
	<input type="hidden" name="<?php echo esc_attr( $option_name . '[mcp]' ); ?>" value="0">
	<label class="imagify-analytics-label" for="imagify_mcp">
		<input
			type="checkbox"
			id="imagify_mcp"
			name="<?php echo esc_attr( $option_name . '[mcp]' ); ?>"
			class="imagify-analytics-checkbox"
			value="1"
			aria-describedby="describe-mcp"
			<?php checked( $is_enabled, true ); ?>
		>
		<span class="imagify-analytics-toggle-ui" aria-hidden="true"></span>
		<?php esc_html_e( 'MCP', 'imagify' ); ?>
	</label>
	<p id="describe-mcp" class="imagify-analytics-description">
		<?php esc_html_e( 'Connect your AI assistant to optimize and manage your images.', 'imagify' ); ?>
		<a href="<?php echo esc_url( $doc_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more about MCP', 'imagify' ); ?></a>
	</p>
</div>
