<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$default_level = Imagify_Options::get_instance()->get( 'optimization_level' );
?>
<tr class="imagify-row-folder-type" data-group-id="<?php echo $data['group_id']; ?>" data-context="<?php echo $data['context']; ?>">
	<td class="imagify-cell-checkbox">
		<p>
			<span class="imagify-cell-checkbox-box">
				<input id="cb-select-<?php echo $data['group_id']; ?>" type="checkbox" name="group[]" checked="checked" value="<?php echo $data['group_id']; ?>" />
				<label for="cb-select-<?php echo $data['group_id']; ?>"></label>
			</span>
		</p>
	</td>
	<td class="imagify-cell-title">
		<label for="cb-select-<?php echo $data['group_id']; ?>"><?php echo $data['title']; ?></label>
	</td>
	<td class="imagify-cell-count-optimized">
		<?php echo $data['count-optimized']; ?>
	</td>
	<td class="imagify-cell-count-errors">
		<?php echo $data['count-errors']; ?>
	</td>
	<td class="imagify-cell-optimized-size-size">
		<?php echo $data['optimized-size']; ?>
	</td>
	<td class="imagify-cell-original-size-size">
		<?php echo $data['original-size']; ?>
	</td>
	<td class="imagify-cell-level">
		<?php
		$this->print_template( 'input/selector', [
			'current_label' => __( 'Current level:', 'imagify' ),
			'name'          => 'level[' . $data['group_id'] . ']',
			'value'         => $default_level,
			'values'        => [
				0 => imagify_get_optimization_level_label( 0, '%ICON% %s' ),
				1 => imagify_get_optimization_level_label( 1, '%ICON% %s' ),
				2 => imagify_get_optimization_level_label( 2, '%ICON% %s' ),
			],
		] );
		?>
	</td>
</tr>

<tr>
	<td class="imagify-bulk-table-footer" colspan="7">
		<?php echo $data['footer']; ?>
	</td>
</tr><!-- .imagify-bulk-table-footer -->
