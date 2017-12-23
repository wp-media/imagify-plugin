<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$default_level = Imagify_Options::get_instance()->get( 'optimization_level' );
?>
<tr class="imagify-row-folder-type">
	<td class="imagify-cell-checkbox">
		<input id="cb-select-<?php echo $data['folder_type']; ?>" type="checkbox" name="group[]" checked="checked" value="<?php echo $data['folder_type']; ?>" class="mini" />
		<label for="cb-select-<?php echo $data['folder_type']; ?>"></label>
	</td>
	<td class="imagify-cell-title">
		<label for="cb-select-<?php echo $data['folder_type']; ?>"><?php echo $data['title']; ?></label>
	</td>
	<td class="imagify-cell-images-optimized">
		<?php echo $data['images-optimized']; ?>
	</td>
	<td class="imagify-cell-errors">
		<?php echo $data['errors']; ?>
	</td>
	<td class="imagify-cell-optimized">
		<?php echo $data['optimized']; ?>
	</td>
	<td class="imagify-cell-original">
		<?php echo $data['original']; ?>
	</td>
	<td class="imagify-cell-level">
		<select name="level[<?php echo $data['folder_type']; ?>]">
			<?php foreach ( array( 0, 1, 2 ) as $level ) { ?>
				<option value="<?php echo $level; ?>"<?php selected( $level, $default_level ); ?>><?php echo esc_html( imagify_get_optimization_level_label( $level, '%ICON% %s' ) ); ?></option>
			<?php } ?>
		</select>
	</td>
</tr>
