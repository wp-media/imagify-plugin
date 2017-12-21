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
	<div class="imagify-bulk-table-content">
		<div class="imagify-bulk-table-container">
			<table class="imagify-bulk-table-details" aria-labelledby="<?php echo $data['id']; ?>-title" aria-describedby="<?php echo $data['id']; ?>-subtitle">
				<thead>
					<tr>
						<th class="imagify-cell-filename"><?php esc_html_e( 'Filename', 'imagify' ); ?></th>
						<th class="imagify-cell-status"><?php esc_html_e( 'Status', 'imagify' ); ?></th>
						<th class="imagify-cell-thumbs"><?php _e( 'Thumbnails optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-original"><?php esc_html_e( 'Original', 'imagify' ); ?></th>
						<th class="imagify-cell-optimized"><?php esc_html_e( 'Optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-percentage"><?php esc_html_e( 'Percentage', 'imagify' ); ?></th>
						<th class="imagify-cell-overall"><?php esc_html_e( 'Overall savings', 'imagify' ); ?></th>
					</tr>
				</thead>
				<tfoot>
					<!-- The progress bar -->
					<tr aria-hidden="true" class="imagify-row-progress hidden" id="imagify-row-progress-<?php echo $data['id']; ?>">
						<td colspan="7">
							<div class="media-item">
								<div class="progress">
									<div id="imagify-progress-bar" class="bar"><div class="percent">0%</div></div>
								</div>
							</div>
						</td>
					</tr>
				</tfoot>
				<tbody>

				</tbody>
			</table>

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
				<tbody>
					<?php foreach ( $data['rows'] as $group => $row ) { ?>
						<tr>
							<td class="imagify-cell-checkbox">
								<p>
									<input id="cb-select-<?php echo $group; ?>" type="checkbox" name="group[]" checked="checked" value="<?php echo $group; ?>" />
									<label for="cb-select-<?php echo $group; ?>"></label>
								</p>
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
								<span class="imagify-cell-label"><?php esc_html_e( 'Optimized Size', 'imagify' ); ?></span>
								<span class="imagify-cell-value"><?php echo imagify_size_format( $row['optimized_size'], 3 ); ?></span>
							</td>
							<td class="imagify-cell-original">
								<span class="imagify-cell-label"><?php esc_html_e( 'Original Size', 'imagify' ); ?></span>
								<span class="imagify-cell-value"><?php echo imagify_size_format( $row['original_size'], 3 ); ?></span>
							</td>
							<td class="imagify-cell-level">
								<div class="imagify-level-selector">
									<span class="hide-if-js">
										<?php _e( 'Current level:', 'imagify'); ?>
										<span class="imagify-current-level-info"><?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></span>
									</span>
									
									<button aria-controls="imagify-<?php echo $group; ?>-level-selector-list" type="button" class="button imagify-button-clean hide-if-no-js imagify-level-selector-button"><span class="imagify-current-level-info"><?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></span></button>

									<ul id="imagify-<?php echo $group; ?>-level-selector-list" role="listbox" aria-orientation="vertical" class="imagify-level-selector-list">
										<?php foreach ( array( 0, 1, 2 ) as $level ) { ?>
										<li class="imagify-level-choice<?php echo $level === $default_level ? ' imagify-current-level" aria-current="true' : ''; ?>" role="option"> 
											<input type="radio" name="level[<?php echo $group; ?>]" value="<?php echo $level; ?>" id="<?php echo $group; ?>-level-<?php echo $level; ?>" <?php echo checked( $level, $default_level ); ?> class="screen-reader-text">
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
		</div><!-- .imagify-bulk-table-container -->

		<div class="imagify-bulk-table-footer">
			<?php echo $data['footer']; ?>
		</div><!-- .imagify-bulk-table-footer -->
	</div><!-- .imagify-bulk-table-content -->
</div>
