<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="imagify-bulk-table imagify-newbie">
	<p class="imagify-new-feature"><?php _e( 'New Feature!', 'imagify' ); ?></p>
	<div class="imagify-table-header imagify-flex imagify-vcenter">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-admin-plugins"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title"><?php _e( 'Optimize the images from your site\'s folders', 'imagify' ); ?></p>
			</div>
		</div>
		<div class="imagify-th-action">
			<a class="hide-if-no-js button imagify-button-secondary" href="<?php echo esc_url( get_imagify_admin_url() ); ?>#custom-folders">
				<?php esc_html_e( 'Choose your folders to optimize', 'imagify' ); ?>
			</a>
		</div>
	</div>
</div>
