<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>

<div class="imagify-bulk-table">
	<div class="imagify-table-header imagify-flex imagify-vcenter imagify-resting">
		<div class="imagify-th-titles imagify-flex imagify-vcenter">
			<span class="dashicons dashicons-<?php echo $data['icon']; ?>"></span>
			<div class="imagify-th-titles">
				<p class="imagify-th-title"><?php echo $data['title']; ?></p>
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
			<table class="imagify-bulk-table-details hidden" aria-hidden="true">
				<thead>
				</thead>
				<tbody>
				</tbody>
			</table>

			<div aria-hidden="true" class="imagify-row-progress hidden">
				<div class="media-item">
					<div class="progress">
						<div class="bar"><div class="percent">0%</div></div>
					</div>
				</div>
			</div>

			<table>
				<thead>
					<tr class="screen-reader-text">
						<th class="imagify-cell-checkbox"><?php esc_html_e( 'Group selection', 'imagify' ); ?></th>
						<th class="imagify-cell-title"><?php esc_html_e( 'Group name', 'imagify' ); ?></th>
						<th class="imagify-cell-count-optimized"><?php esc_html_e( 'Number of images optimized', 'imagify' ); ?></th>
						<th class="imagify-cell-count-errors"><?php esc_html_e( 'Errors', 'imagify' ); ?></th>
						<th class="imagify-cell-optimized-size-size"><?php esc_html_e( 'Optimized Size', 'imagify' ); ?></th>
						<th class="imagify-cell-original-size-size"><?php esc_html_e( 'Original Size', 'imagify' ); ?></th>
						<th class="imagify-cell-level"><?php esc_html_e( 'Level Selection', 'imagify' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data['groups'] as $group ) {
						$context_data = Imagify_Admin_Ajax_Post::get_instance()->get_bulk_instance( $group['context'] )->get_context_data();
						$group        = array_merge( $group, $context_data );

						$this->print_template( 'part-bulk-optimization-table-row-folder-type', $group );
					}
					?>
				</tbody>
			</table>
		</div><!-- .imagify-bulk-table-container -->
	</div><!-- .imagify-bulk-table-content -->

	<?php
	foreach ( $data['groups'] as $group ) {
		?>
		<script type="text/html" id="tmpl-imagify-file-header-<?php echo $group['group_id']; ?>">
			<?php $this->print_template( 'part-bulk-optimization-header-row-' . $group['group_id'] ); ?>
		</script>
		<script type="text/html" id="tmpl-imagify-file-row-<?php echo $group['group_id']; ?>">
			<?php $this->print_template( 'part-bulk-optimization-underscore-file-row-' . $group['group_id'] ); ?>
		</script>
		<?php
	}
	?>
</div>
