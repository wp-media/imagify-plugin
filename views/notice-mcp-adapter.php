<?php
defined( 'ABSPATH' ) || exit;
/**
 * Variables passed from AdapterNotice::display().
 *
 * @var string $install_url
 * @var string $dismiss_url
 */
?>
<div class="notice notice-info imagify-mcp-adapter-notice">
	<p>
		<?php
		printf(
			/* translators: %1$s is the opening link tag, %2$s is the closing link tag. */
			esc_html__( 'Imagify no longer bundles the MCP Adapter, which is now available as a standalone plugin on WordPress.org. To keep using MCP with Imagify, please install and activate the %1$sMCP Adapter plugin%2$s.', 'imagify' ),
			'<a href="' . esc_url( $install_url ) . '">',
			'</a>'
		);
		?>
	</p>
	<p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss this notice', 'imagify' ); ?></a></p>
</div>
