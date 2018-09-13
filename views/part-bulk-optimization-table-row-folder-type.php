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
		<div class="imagify-level-selector">
			<span class="hide-if-js">
				<?php esc_html_e( 'Current level:', 'imagify' ); ?>
				<span class="imagify-current-level-info"><?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></span>
			</span>

			<button aria-controls="imagify-<?php echo $data['group_id']; ?>-level-selector-list" type="button" class="button imagify-button-clean hide-if-no-js imagify-level-selector-button"><span class="imagify-current-level-info"><?php echo imagify_get_optimization_level_label( $default_level, '%ICON% %s' ); ?></span></button>

			<ul id="imagify-<?php echo $data['group_id']; ?>-level-selector-list" role="listbox" aria-orientation="vertical" aria-hidden="true" class="imagify-level-selector-list hide-if-no-js">
				<?php foreach ( array( 0, 1, 2 ) as $level ) { ?>
				<li class="imagify-level-choice<?php echo $level === $default_level ? ' imagify-current-level" aria-current="true' : ''; ?>" role="option">
					<input type="radio" name="level[<?php echo $data['group_id']; ?>]" value="<?php echo $level; ?>" id="<?php echo $data['group_id']; ?>-level-<?php echo $level; ?>" <?php checked( $level, $default_level ); ?> class="screen-reader-text">
					<label for="<?php echo $data['group_id']; ?>-level-<?php echo $level; ?>"><?php echo imagify_get_optimization_level_label( $level, '%ICON% %s' ); ?></label>
				</li>
				<?php } ?>
			</ul>
		</div>
	</td>
</tr>

<tr>
	<td class="imagify-bulk-table-footer" colspan="7">
		<?php echo $data['footer']; ?>
	</td>
</tr><!-- .imagify-bulk-table-footer -->
