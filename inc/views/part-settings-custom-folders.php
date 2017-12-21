<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! imagify_can_optimize_custom_folders() ) {
	return;
}

$settings = Imagify_Settings::get_instance();
?>
<div class="imagify-col" id="custom-folders">
	<h3 class="imagify-options-subtitle"><?php _e( 'Themes and Plugins', 'imagify' ); ?></h3>

	<div>
		<h4 class="imagify-h4-like"><?php _e( 'Themes to optimize', 'imagify' ); ?></h4>
		<p><?php _e( 'You can choose to optimize the themes on your site.', 'imagify' ); ?></p>
		<?php
		$themes  = Imagify_Settings::get_themes();
		$plugins = Imagify_Settings::get_plugins();

		$themes_and_plugins = array_keys( array_merge( $themes, $plugins ) );
		$custom_folders     = Imagify_Folders_DB::get_instance()->get_optimized_folders_column_in( 'path', 'path', $themes_and_plugins );
		$custom_folders     = array_flip( $custom_folders );

		$disabled_values = Imagify_Files_Scan::get_forbidden_folders();
		$disabled_values = array_map( array( 'Imagify_Files_Scan', 'add_placeholder' ), $disabled_values );
		$disabled_values = array_flip( $disabled_values );

		/**
		 * Themes.
		 */
		$settings->field_checkbox_list( array(
			'option_name'     => 'custom_folders',
			'legend'          => __( 'Choose the themes to optimize', 'imagify' ),
			'current_values'  => $custom_folders,
			'values'          => $themes,
			'disabled_values' => $disabled_values,
		) );
		?>
	</div>

	<div>
		<h4 class="imagify-h4-like"><?php _e( 'Plugins to optimize', 'imagify' ); ?></h4>
		<p><?php _e( 'You can choose to optimize the plugins on your site.', 'imagify' ); ?></p>
		<?php
		/**
		 * Plugins.
		 */
		$settings->field_checkbox_list( array(
			'option_name'     => 'custom_folders',
			'legend'          => __( 'Choose the plugins to optimize', 'imagify' ),
			'current_values'  => $custom_folders,
			'values'          => $plugins,
			'disabled_values' => $disabled_values,
		) );
		?>
	</div>

	<div id="imagify-custom-folders" class="hide-if-no-js imagify-mt3">
		<h3 class="imagify-options-subtitle"><?php _e( 'Custom folders', 'imagify' ); ?></h3>
		<p><?php _e( 'You can choose to optimize custom folders on your site.', 'imagify' ); ?></p>

		<button id="imagify-add-custom-folder" class="button imagify-button-clean imagify-add-custom-folder" type="button">
			<span class="dashicons dashicons-plus"></span>
			<span class="button-text"><?php _e( 'Add folder', 'imagify' ); ?></span>
		</button>
		<img class="imagify-loader" alt="<?php esc_attr_e( 'Loading...', 'imagify' ); ?>" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'loader-balls.svg' ); ?>" width="38" height="24"/>
		<?php
		/**
		 * Other custom folders.
		 */
		$disabled_values = array_merge( $themes_and_plugins, array_flip( $disabled_values ) );
		$custom_folders  = Imagify_Folders_DB::get_instance()->get_optimized_folders_column_not_in( 'path', 'path', $disabled_values );

		if ( $custom_folders ) {
			$custom_folders = array_combine( $custom_folders, $custom_folders );
			$custom_folders = array_map( array( 'Imagify_Files_Scan', 'remove_placeholder' ), $custom_folders );
			$custom_folders = array_map( 'imagify_make_file_path_relative', $custom_folders );

			$settings->field_checkbox_list( array(
				'option_name'    => 'custom_folders',
				'legend'         => __( 'Choose the folders to optimize', 'imagify' ),
				'current_values' => $custom_folders,
				'values'         => $custom_folders,
			) );
		}
		?>
	</div>
</div>
<?php
