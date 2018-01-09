<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
?>

<div class="imagify-bulk-table">
	<table data-group-id="<?php echo $data['group_id']; ?>" data-context="<?php echo $data['context']; ?>">
		<thead>
			<tr class="imagify-bulk-table-title imagify-resting">
				<th class="">
					<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
				</th>
				<th class="" colspan="5">
					<?php echo esc_html( $data['title'] ); ?>
				</th>
				<th class="">
				</th>
			</tr>
			<tr class="imagify-bulk-table-title imagify-fetching hidden" aria-hidden="true">
				<th class="">
					<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
				</th>
				<th class="" colspan="5">
					<?php esc_html_e( 'Fetching your images...', 'imagify' ); ?>
				</th>
				<th class="">
				</th>
			</tr>
			<tr class="imagify-bulk-table-title imagify-optimizing hidden" aria-hidden="true">
				<th class="">
					<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
				</th>
				<th class="" colspan="5">
					<?php echo esc_html( $data['optimizing'] ); ?>
				</th>
				<th class="">
					<button class="imagify-view-optimization-details" type="button">
						<?php esc_html_e( 'View Details', 'imagify' ); ?>
						<span class="dashicons dashicons-menu"></span>
					</button>
				</th>
			</tr>
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
			<!-- The results. -->
			<tr class="imagify-row-optimization-details hidden" aria-hidden="true">
				<td colspan="7">
					<table summary="<?php _e( 'Optimization process results', 'imagify' ); ?>">
						<thead>
							<tr class="">
								<?php $this->print_template( 'part-bulk-optimization-results-header-' . $data['group_id'] ); ?>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</td>
			</tr>
			<!-- The progress bar. -->
			<tr class="imagify-row-progress">
				<td colspan="7">
					<div class="progress">
						<div class="imagify-progress-bar bar"><div class="percent">0%</div></div>
					</div>
				</td>
			</tr>
			<?php
			foreach ( $data['rows'] as $folder_type => $row ) {
				$row['folder_type'] = $folder_type;

				$row = array_merge( $row, imagify_get_folder_type_data( $folder_type ) );

				$this->print_template( 'part-bulk-optimization-group-row-folder-type', $row );
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="7">
					<?php echo $data['footer']; ?>
				</td>
			</tr>
		</tfoot>
	</table>
	<script id="tmpl-imagify-file-row-<?php echo $data['group_id']; ?>" type="text/html"><?php $this->print_template( 'part-bulk-optimization-underscore-file-row-' . $data['group_id'] ); ?></script>
</div>
