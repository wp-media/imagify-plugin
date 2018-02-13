<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! imagify_can_optimize_custom_folders() ) {
	return;
}

$settings = Imagify_Settings::get_instance();
?>
<div class="imagify-col" id="imagify-custom-folders">
	<h3 class="imagify-options-subtitle"><?php _e( 'Custom folders', 'imagify' ); ?></h3>
	<p><?php _e( 'You can choose to optimize custom folders on your site.', 'imagify' ); ?></p>

	<button id="imagify-add-custom-folder" class="button imagify-button-clean imagify-add-custom-folder" type="button">
		<span class="dashicons dashicons-plus"></span>
		<span class="button-text"><?php _e( 'Add folder', 'imagify' ); ?></span>
	</button>
	<img class="imagify-loader" alt="<?php esc_attr_e( 'Loading...', 'imagify' ); ?>" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'loader-balls.svg' ); ?>" width="38" height="24"/>
	<?php
	$disabled_values = Imagify_Files_Scan::get_forbidden_folders();
	$disabled_values = array_map( array( 'Imagify_Files_Scan', 'add_placeholder' ), $disabled_values );
	$custom_folders  = Imagify_Folders_DB::get_instance()->get_active_folders_column_not_in( 'path', 'path', $disabled_values );

	if ( $custom_folders ) {
		sort( $custom_folders );
		$custom_folders = array_combine( $custom_folders, $custom_folders );
		$custom_folders = array_map( array( 'Imagify_Files_Scan', 'remove_placeholder' ), $custom_folders );
		$custom_folders = array_map( 'imagify_make_file_path_relative', $custom_folders );

		if ( isset( $custom_folders['{{ABSPATH}}/'] ) ) {
			$custom_folders['{{ABSPATH}}/'] = __( 'Site\'s root', 'imagify' );
		}

		$settings->field_checkbox_list( array(
			'option_name'    => 'custom_folders',
			'legend'         => __( 'Choose the folders to optimize', 'imagify' ),
			'current_values' => $custom_folders,
			'values'         => $custom_folders,
		) );
	}
	?>
</div>
<?php
