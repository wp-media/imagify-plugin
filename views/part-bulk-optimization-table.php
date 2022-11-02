<?php
use Imagify\Bulk\Bulk;

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
	</div>

	<?php
	$bulk      = Bulk::get_instance();
	$remaining = 0;
	$total     = 0;

	foreach ( ['wp', 'custom-folders'] as $group ) {
		$media_ids = $bulk->get_bulk_instance( $group )->get_unoptimized_media_ids( 2 );

		$remaining += count( $media_ids );
		$total     += (int) get_transient( 'imagify_' . $group . '_optimize_total' );
	}

	$aria_hidden = 'aria-hidden="true"';
	$hidden  = 'hidden';
	$style   = '';
	$percent = 0;

	if ( 0 !== $total ) {
		$aria_hidden = '';
		$hidden  = '';
		$processed = $total - $remaining;
		$percent   = round( $processed / $total * 100 );
		$style = 'style=width:' . $percent . '%;';
	}
	?>

	<div class="imagify-bulk-table-content">
		<div class="imagify-bulk-table-container">
			<div <?php echo $aria_hidden; ?> class="imagify-row-progress <?php echo $hidden; ?>">
				<div class="media-item">
					<div class="progress">
						<div class="bar" <?php echo $style; ?>><div class="percent"><?php echo $percent ?>%</div></div>
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
						$context_data = $bulk->get_bulk_instance( $group['context'] )->get_context_data();
						$group        = array_merge( $group, $context_data );
						$default_level = Imagify_Options::get_instance()->get( 'optimization_level' );

						if ( Imagify_Options::get_instance()->get( 'lossless' ) ) {
							$default_level = 0;
						}

						$group['level'] = $default_level;

						$this->print_template( 'part-bulk-optimization-table-row-folder-type', $group );
					}
					?>
				</tbody>
			</table>
		</div><!-- .imagify-bulk-table-container -->
	</div><!-- .imagify-bulk-table-content -->
</div>
