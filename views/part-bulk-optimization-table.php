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
	$types = [];
	$total       = 0;
	$remaining   = 0;
	$percentage  = 0;

	foreach ( $data['groups'] as $group ) {
		$types[ $group['group_id'] . '|' . $group['context'] ] = true;

		$transient = get_transient( "imagify_{$group['context']}_optimize_running" );

		if ( false !== $transient ) {
			$total     += $transient['total'];
			$remaining += $transient['remaining'];
		}
	}

	if ( 0 !== $total ) {
		$percentage = ( $total - $remaining ) / $total * 100;
	}

	$bulk = Bulk::get_instance();
	$aria_hidden = 'aria-hidden="true"';
	$hidden  = 'hidden';
	$style   = '';
	$display = '';

	if (
		0 !== $percentage
		&&
		100 !== $percentage
	) {
		$percentage  = round( $percentage );
		$aria_hidden = '';
		$hidden      = '';
		$style       = 'style="width:' . $percentage . '%;"';
		$display     = 'style="display:block;"';
	}
	?>

	<div class="imagify-bulk-table-content">
		<div class="imagify-bulk-table-container">
			<div <?php echo $aria_hidden; ?> class="imagify-row-progress <?php echo $hidden; ?>" <?php echo $display; ?>>
				<div class="media-item">
					<div class="progress">
						<div class="bar" <?php echo $style; ?>><div class="percent"><?php echo $percentage; ?>%</div></div>
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

						$running = get_transient( "imagify_{$group['context']}_optimize_running" );

						$group['spinner_class']  = 'hidden';
						$group['spinner_aria']   = 'aria-hidden="true"';
						$group['checkbox_class'] = '';
						$group['checkbox_aria']  = 'aria-hidden="false"';

						if ( false !== $running ) {
							$group['spinner_class']  = '';
							$group['spinner_aria']   = 'aria-hidden="false"';
							$group['checkbox_class'] = 'hidden';
							$group['checkbox_aria']  = 'aria-hidden="true"';
						}

						$this->print_template( 'part-bulk-optimization-table-row-folder-type', $group );
					}
					?>
				</tbody>
			</table>
		</div><!-- .imagify-bulk-table-container -->
	</div><!-- .imagify-bulk-table-content -->
</div>
