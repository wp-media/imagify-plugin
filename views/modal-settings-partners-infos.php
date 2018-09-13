<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="imagify-modal" id="imagify-partners-info">
	<div class="imagify-modal-content">
		<p>
			<?php esc_html_e( 'Partner links are links to products we trust which might be displayed in the Imagify plugin or the plugin modal page.  We may earn an affiliate commission if you make a purchase via these links.', 'imagify' ); ?>
		</p>

		<button type="button" class="close-btn">
			<i class="dashicons dashicons-no-alt" aria-hidden="true"></i>
			<span class="screen-reader-text"><?php esc_html_e( 'Close' ); ?></span>
		</button>
	</div>
</div>

<?php
