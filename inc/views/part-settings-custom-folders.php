<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! imagify_can_optimize_custom_folders() ) {
	return;
}

$settings = Imagify_Settings::get_instance();

$theme_name           = 'The Theme'; // TODO: dynamic.
$child_theme_name     = 'The Kid'; // TODO: dynamic.
$themes_count         = 2; // TODO: dynamic. 1 if main theme, 2 if is a child.
$themes_already_added = false; // TODO: dynamic.
?>

<div class="imagify-col" id="custom-folders">
	<h3 class="imagify-options-subtitle"><?php _e( 'Custom Folders', 'imagify' ); ?></h3>

	<div id="imagify-custom-folders" class="hide-if-no-js imagify-mt2">

		<div class="imagify-folder-themes-suggestion">
			<div class="imagify-fts-header imagify-flex imagify-vcenter">
				<span><i class="dashicons dashicons-info"></i></span>
				<p>
					<?php
					// TODO: dynamic. Sometimes you only have a main theme ;p.
					/* translators: %s is a theme name. */
					printf( __( 'You’re using %s', 'imagify' ), '<strong>' . $child_theme_name . '</strong>' );
					echo '<br>';
					/* translators: %s is a theme name. */
					printf( __( 'child theme of %s', 'imagify' ), '<strong>' . $theme_name . '</strong>' );
					?>
				</p>
			</div>

			<div class="imagify-fts-content">
				<?php if ( $themes_already_added ) { ?>
					<p class="imagify-mb0"><?php echo _n( 'Your theme is already in the optimization process. All Good!', 'Your themes are already in the optimization process. All Good!', $themes_count, 'imagify' ); ?></p>
				<?php } else { ?>
					<p><?php echo _n( 'Would you like to optimize your theme?', 'Would you like to optimize your themes?', $themes_count, 'imagify' ); ?></p>

					<button id="imagify-add-themes-to-custom-folder" class="button imagify-button-clean imagify-add-themes" type="button">
						<span class="dashicons dashicons-plus"></span>
						<span class="button-text"><?php echo _n( 'Add the theme to optimization', 'Add the themes to optimization', $themes_count , 'imagify' ); ?></span>
					</button>
				<?php } ?>
			</div>
		</div>

		<p class="imagify-kindof-title imagify-flex imagify-vcenter">
			<span><?php _e( 'Optimize Images in custom folders here.', 'imagify' ); ?></span>
			<span>
				<button id="imagify-add-custom-folder" class="button imagify-button-mini imagify-button-primary imagify-add-custom-folder" type="button">
					<span class="dashicons dashicons-plus"></span>
					<span class="button-text"><?php _e( 'Add folders', 'imagify' ); ?></span>
				</button>
				<img class="imagify-loader" aria-hidden="true" alt="<?php esc_attr_e( 'Loading...', 'imagify' ); ?>" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'loader-balls.svg' ); ?>" width="38" height="24"/>
			</span>
		</p>

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

			// TODO: remove that sample of markup.
			for ( $i = 0; $i <= 2; $i++ ) {
				echo '<p id="imagify_custom_folders_{CONTENTfoldername}" class="imagify-custom-folder-line" data-value="{{CONTENT}}/folder/name/">';
				echo '/wp-content/folder/name/';
				echo '<button type="button" class="imagify-custom-folders-remove"><span class="imagify-custom-folders-remove-text">' . __( 'Remove', 'imagify' ) . '</span><i class="dashicons dashicons-no-alt" aria-hidden="true"></i></button>';
				echo '</p>';
			}

			// TODO: Create a new "field_removable_item_list" method, for example.
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
