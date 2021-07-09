<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( ! imagify_can_optimize_custom_folders() ) {
	return;
}

// Get folders, remove excluded ones, sort them, add labels.
$custom_folders = Imagify_Folders_DB::get_instance()->get_active_folders_column( 'path' );
$themes         = array();

if ( $custom_folders ) {
	$custom_folders = array_combine( $custom_folders, $custom_folders );
	$custom_folders = array_map( array( 'Imagify_Files_Scan', 'remove_placeholder' ), $custom_folders );
	$custom_folders = array_map( 'trailingslashit', $custom_folders );
	$custom_folders = array_filter( $custom_folders, array( 'Imagify_Files_Scan', 'is_path_autorized' ) );
}

if ( $custom_folders ) {
	$custom_folders = array_map( array( $this->filesystem, 'make_path_relative' ), $custom_folders );
	$custom_folders = array_map( 'untrailingslashit', $custom_folders );
	natcasesort( $custom_folders );
	$custom_folders = array_map( 'trailingslashit', $custom_folders );

	if ( isset( $custom_folders['{{ROOT}}/'] ) ) {
		$custom_folders['{{ROOT}}/'] = __( 'Site\'s root', 'imagify' );
	}
}

// Current used theme(s).
if ( ! is_network_admin() ) {
	$current_theme    = wp_get_theme();
	$themes_not_added = array();

	foreach ( array( $current_theme, $current_theme->parent() ) as $theme ) {
		if ( ! $theme || ! $theme->exists() ) {
			continue;
		}

		$theme_path = trailingslashit( $theme->get_stylesheet_directory() );

		if ( ! Imagify_Files_Scan::is_path_forbidden( $theme_path ) ) {
			$theme = array(
				'name'  => $theme->display( 'Name' ),
				'path'  => Imagify_Files_Scan::add_placeholder( $theme_path ),
				'label' => $this->filesystem->make_path_relative( $theme_path ),
			);

			$themes[ $theme['path'] ] = $theme;
			$added                    = false;
			$rel_path                 = strtolower( $theme['label'] );

			foreach ( $custom_folders as $path => $label ) {
				if ( strpos( $rel_path, strtolower( $label ) ) === 0 ) {
					$added = true;
					break;
				}
			}

			if ( ! $added ) {
				$themes_not_added[] = $theme['path'];
			}
		}
	}

	$themes_count = count( $themes );
}
?>
	<div class="imagify-custom-folders-section">
		<div class="imagify-col" id="custom-folders">
			<h3 class="imagify-options-subtitle"><?php _e( 'Custom Folders', 'imagify' ); ?></h3>

			<div id="imagify-custom-folders" class="hide-if-no-js imagify-mt2">

				<?php if ( $themes ) { ?>
					<div class="imagify-folder-themes-suggestion">
						<div class="imagify-fts-header imagify-flex imagify-vcenter">
							<span><i class="dashicons dashicons-info"></i></span>
							<p>
								<?php
								$theme = reset( $themes );

								/* translators: %s is a theme name. */
								printf( __( 'You’re using %s', 'imagify' ), '<strong>' . $theme['name'] . '</strong>' );

								if ( $themes_count > 1 ) {
									$theme = end( $themes );
									echo '<br>';
									/* translators: %s is a theme name. */
									printf( __( 'child theme of %s', 'imagify' ), '<strong>' . $theme['name'] . '</strong>' );
								}
								?>
							</p>
						</div>

						<div class="imagify-fts-content">
							<?php
							if ( ! $themes_not_added ) {
								?>
								<p class="imagify-mb0"><?php echo _n( 'Your theme is already in the optimization process. All Good!', 'Your themes are already in the optimization process. All Good!', $themes_count, 'imagify' ); ?></p>
								<?php
							} elseif ( count( $themes_not_added ) !== $themes_count ) {
								$theme = reset( $themes_not_added );
								$theme = $themes[ $theme ];
								?>
								<p><?php _e( 'Only one of your current themes is in the optimization process, would you like to also optimize the other one?', 'imagify' ); ?></p>

								<button id="imagify-add-themes-to-custom-folder"
										class="button imagify-button-clean imagify-add-themes" type="button"
										data-theme="<?php echo esc_attr( $theme['path'] ) . '#///#' . esc_attr( $theme['label'] ); ?>">
									<span class="dashicons dashicons-plus"></span>
									<span class="button-text"><?php
										/* translators: %s is a theme name. */
										printf( __( 'Add %s to optimization', 'imagify' ), '<strong>' . $theme['name'] . '</strong>' );
									?></span>
								</button>
								<?php
							} else {
								foreach ( $themes as $path => $theme ) {
									$themes[ $path ] = esc_attr( $theme['path'] ) . '#///#' . esc_attr( $theme['label'] );
								}
								?>
								<p><?php echo _n( 'Would you like to optimize your theme?', 'Would you like to optimize your themes?', $themes_count, 'imagify' ); ?></p>

								<button id="imagify-add-themes-to-custom-folder"
										class="button imagify-button-clean imagify-add-themes" type="button"
										data-theme="<?php echo implode( '" data-theme-parent="', $themes ); ?>">
									<span class="dashicons dashicons-plus"></span>
									<span
										class="button-text"><?php echo _n( 'Add the theme to optimization', 'Add the themes to optimization', $themes_count, 'imagify' ); ?></span>
								</button>
								<?php
							}
							?>
						</div>
					</div>
				<?php } ?>

				<p class="imagify-kindof-title imagify-flex imagify-vcenter">
					<span><?php _e( 'Select folders for optimization.', 'imagify' ); ?></span>
					<span>
				<button id="imagify-add-custom-folder"
						class="button imagify-button-mini imagify-button-primary imagify-add-custom-folder"
						type="button">
					<span class="dashicons dashicons-plus"></span>
					<span class="button-text"><?php _e( 'Add folders', 'imagify' ); ?></span>
				</button>
				<img class="imagify-loader" aria-hidden="true" alt="<?php esc_attr_e( 'Loading...', 'imagify' ); ?>" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'loader-balls.svg' ); ?>" width="38" height="24"/>
			</span>
				</p>

				<fieldset id="imagify-custom-folders-selected">
					<?php
					if ( $custom_folders ) {
						foreach ( $custom_folders as $placeholder => $label ) {
							$this->print_template( 'part-settings-row-custom-folder', array(
								'value' => $placeholder,
								'label' => $label,
							) );
						}
					}
					?>
				</fieldset>

				<p>
					<?php
					printf(
					/* translators: 1 and 2 are <strong> opening and closing tags. */
						__( '%1$sSelecting a folder will also optimize images in sub-folders.%2$s The only exception is "Site’s root": when selected, only images that are directly at the site’s root will be optimized (sub-folders can be selected separately).', 'imagify' ),
						'<strong>',
						'</strong>'
					);
					?>
					<br/>
					<?php _e( 'Folders that are hidden in the folder selector window are excluded and will not be optimized even if a parent folder is selected.', 'imagify' ); ?>
				</p>

				<p class="imagify-success hidden"><?php _e( 'You changed your custom folder settings, don\'t forget to save your changes!', 'imagify' ); ?></p>

				<script type="text/html" id="tmpl-imagify-custom-folder">
					<?php
					$this->print_template( 'part-settings-row-custom-folder', array(
						'value' => '{{ data.value }}',
						'label' => '{{ data.label }}',
					) );
					?>
				</script>
			</div>
		</div>
	</div>
<?php
