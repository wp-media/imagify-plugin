<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
?>

<div class="imagify-bulk-table" data-group-id="<?php echo $data['group_id']; ?>" data-context="<?php echo $data['context']; ?>">
	<div class="imagify-table-header imagify-flex imagify-vcenter imagify-resting">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title" id="<?php echo $data['group_id']; ?>-title"><?php echo $data['title']; ?></p>
				<?php if ( ! empty( $data['subtitle'] ) ) { ?>
					<p class="imagify-th-subtitle" id="<?php echo $data['group_id']; ?>-subtitle"><?php echo $data['subtitle']; ?></p>
				<?php } ?>
			</div>
		</div>

		<div class="imagify-th-action"></div>
	</div>

	<div class="imagify-table-header imagify-flex imagify-vcenter imagify-fetching hidden" aria-hidden="true">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title"><?php esc_html_e( 'Fetching your images...', 'imagify' ); ?></p>
			</div>
		</div>

		<div class="imagify-th-action"></div>
	</div>

	<div class="imagify-table-header imagify-flex imagify-vcenter imagify-optimizing hidden" aria-hidden="true">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title"><?php echo esc_html( $data['optimizing'] ); ?></p>
			</div>
		</div>

		<div class="imagify-th-action">
			<button class="button imagify-button-clean imagify-show-table-details" type="button" data-label-show="<?php esc_attr_e( 'View Details', 'imagify' ); ?>" data-label-hide="<?php esc_attr_e( 'Hide Details', 'imagify' ); ?>">
				<?php esc_html_e( 'View Details', 'imagify' ); ?>
				<span class="dashicons dashicons-menu"></span>
			</button>
		</div>
	</div>

	<div class="imagify-bulk-table-content">
		<div class="imagify-bulk-table-container">
			<table class="imagify-bulk-table-details hidden" aria-hidden="true" aria-labelledby="<?php echo $data['group_id']; ?>-title"<?php echo ! empty( $data['subtitle'] ) ? ' aria-describedby="' . $data['group_id'] . '-subtitle"' : ''; ?>>
				<thead>
					<tr>
						<?php $this->print_template( 'part-bulk-optimization-results-header-' . $data['group_id'] ); ?>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>

			<div aria-hidden="true" class="imagify-row-progress hidden" id="imagify-row-progress-<?php echo $data['group_id']; ?>">
				<div class="media-item">
					<div class="progress">
						<div class="bar"><div class="percent">0%</div></div>
					</div>
				</div>
			</div>

			<table aria-labelledby="<?php echo $data['group_id']; ?>-title"<?php echo ! empty( $data['subtitle'] ) ? ' aria-describedby="' . $data['group_id'] . '-subtitle"' : ''; ?>>
				<thead>
					<tr class="screen-reader-text">
						<th class="imagify-cell-checkbox"><?php esc_html_e( 'Group selection', 'imagify' ); ?></th>
						<th class="imagify-cell-title"><?php esc_html_e( 'Group name', 'imagify' ); ?></th>
						<th class="imagify-cell-images-optimized"><?php esc_html_e( 'Number of images optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-errors"><?php esc_html_e( 'Errors', 'imagify' ); ?></th>
						<th class="imagify-cell-optimized"><?php esc_html_e( 'Optimized Size', 'imagify' ); ?></th>
						<th class="imagify-cell-original"><?php esc_html_e( 'Original Size', 'imagify' ); ?></th>
						<th class="imagify-cell-level"><?php esc_html_e( 'Level Selection', 'imagify' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data['rows'] as $folder_type => $row ) {
						$row['folder_type'] = $folder_type;

						$row = array_merge( $row, imagify_get_folder_type_data( $folder_type ) );

						$this->print_template( 'part-bulk-optimization-group-row-folder-type', $row );
					}
					?>
				</tbody>
			</table>
		</div><!-- .imagify-bulk-table-container -->

		<div class="imagify-bulk-table-footer">
			<?php echo $data['footer']; ?>
		</div><!-- .imagify-bulk-table-footer -->
	</div><!-- .imagify-bulk-table-content -->
	<script id="tmpl-imagify-file-row-<?php echo $data['group_id']; ?>" type="text/html"><?php $this->print_template( 'part-bulk-optimization-underscore-file-row-' . $data['group_id'] ); ?></script>
</div>
