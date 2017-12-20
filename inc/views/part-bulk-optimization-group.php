<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$default_level = Imagify_Options::get_instance()->get( 'optimization_level' );
?>

<div class="imagify-bulk-table">
	<div class="imagify-table-header imagify-flex imagify-vcenter">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title" id="<?php echo $data['id']; ?>-title"><?php echo $data['title']; ?></p>
				<?php if ( isset( $data['subtitle'] ) && ! empty( $data['subtitle'] ) ) { ?>
				<p class="imagify-th-subtitle" id="<?php echo $data['id']; ?>-subtitle"><?php echo $data['subtitle']; ?></p>
				<?php } ?>
			</div>
		</div>
		<div class="imagify-th-action">
			<button class="hide-if-no-js button imagify-button-clean" type="button">
				<?php esc_html_e( 'View Details', 'imagify' ); ?>
				<span class="dashicons dashicons-menu"></span>
			</button>
		</div>
	</div>

	<table aria-labelledby="<?php echo $data['id']; ?>-title" aria-describedby="<?php echo $data['id']; ?>-subtitle">
		<thead>
			<tr class="screen-reader-text">
				<th class="imagify-cell-checkbox"><?php esc_html_e( 'Group selection', 'imagify' ); ?></th>
				<th class="imagify-cell-title"><?php esc_html_e( 'Group name', 'imagify' ); ?></th>
				<th class="imagify-cell-images-optimized"><?php _e( 'Number of images optimized', 'imagify' ); ?></th>
				<th class="imagify-cell-errors"><?php esc_html_e( 'Errors', 'imagify' ); ?></th>
				<th class="imagify-cell-optimized"><?php esc_html_e( 'Optimized Size', 'imagify' ); ?></th>
				<th class="imagify-cell-original"><?php esc_html_e( 'Original Size', 'imagify' ); ?></th>
				<th class="imagify-cell-level"><?php esc_html_e( 'Level Selection', 'imagify' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="7">
					<?php echo $data['footer']; ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<!-- The progress bar -->
			<tr aria-hidden="true" class="imagify-row-progress hidden">
				<td colspan="7">
					<div class="media-item">
						<div class="progress">
							<div id="imagify-progress-bar" class="bar"><div class="percent">0%</div></div>
						</div>
					</div>
				</td>
			</tr>
			<?php foreach ( $data['rows'] as $group => $row ) { ?>
				<tr>
					<td class="imagify-cell-checkbox">
						<input id="cb-select-<?php echo $group; ?>" type="checkbox" name="group[]" checked="checked" value="<?php echo $group; ?>" class="mini" />
						<label for="cb-select-<?php echo $group; ?>"></label>
					</td>
					<td class="imagify-cell-title">
						<label for="cb-select-<?php echo $group; ?>"><?php echo $row['title']; ?></label>
					</td>
					<td class="imagify-cell-images-optimized">
						<?php
						/* translators: %s is a formatted number, dont use %d. */
						printf( esc_html( _n( '%s Image Optimized', '%s Images Optimized', $row['optimized_images'], 'imagify' ) ), number_format_i18n( $row['optimized_images'] ) );
						?>
					</td>
					<td class="imagify-cell-errors">
						<?php
						/* translators: %s is a formatted number, dont use %d. */
						printf( esc_html( _n( '%s Error', '%s Errors', $row['errors'], 'imagify' ) ), number_format_i18n( $row['errors'] ) );

						if ( $row['errors'] ) {
							echo ' <a href="' . esc_url( $row['errors_url'] ) . '">' . esc_html__( 'View Errors', 'imagify' ) . '</a>';
						}
						?>
					</td>
					<td class="imagify-cell-optimized">
						<?php esc_html_e( 'Optimized Filesize', 'imagify' ); ?>
						<?php echo imagify_size_format( $row['optimized_size'], 3 ); ?>
					</td>
					<td class="imagify-cell-original">
						<?php esc_html_e( 'Original Filesize', 'imagify' ); ?>
						<?php echo imagify_size_format( $row['original_size'], 3 ); ?>
					</td>
					<td class="imagify-cell-level">
						<div class="imagify-level-selector">
							<span class="hide-if-js"><?php _e( 'Current level:', 'imagify'); ?> <?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></span>
							
							<button aria-controls="imagify-level-selector-list" type="button" class="button imagify-button-clean hide-if-no-js"><?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></button>

							<ul id="imagify-level-selector-list" role="listbox" aria-orientation="vertical" class="imagify-level-selector-list">
								<?php foreach ( array( 0, 1, 2 ) as $level ) { ?>
								<li<?php echo $level === $default_level ? ' class="imagify-current-level" aria-current' : ''; ?> role="option"> 
									<input type="radio" name="level[<?php echo $group; ?>]" value="<?php echo $level; ?>" id="<?php echo $group; ?>-level-<?php echo $level; ?>" <?php echo checked( $level, $default_level ); ?>>
									<label for="<?php echo $group; ?>-level-<?php echo $level; ?>"><?php echo imagify_get_optimization_level_label( $level, '%ICON% %s' ); ?></label>
								</li>
								<?php } ?>
							</ul>
						</div>
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
