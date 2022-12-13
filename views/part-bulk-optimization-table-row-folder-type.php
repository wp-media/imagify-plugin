<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );
?>
<tr class="imagify-row-folder-type" data-group-id="<?php echo $data['group_id']; ?>" data-context="<?php echo $data['context']; ?>">
	<td class="imagify-cell-checkbox">
		<p>
			<span class="imagify-cell-checkbox-loader <?php echo $data['spinner_class']; ?>" <?php echo $data['spinner_aria']; ?>>
				<svg width="27" height="28" viewBox="0 0 27 28" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="m13.3.254c-.773 0-1.4.627-1.4 1.4l0 4.2c0 .773.627 1.4 1.4 1.4.773 0 1.4-.627 1.4-1.4l0-4.2c0-.773-.627-1.4-1.4-1.4m-8.422 3.478c-.358 0-.711.142-.984.416-.547.547-.547 1.444 0 1.991l2.975 2.953c.547.547 1.422.547 1.969 0 .547-.547.547-1.422 0-1.969l-2.953-2.975c-.273-.273-.648-.416-1.01-.416m16.844 0c-.358 0-.733.142-1.01.416l-2.953 2.975c-.547.547-.547 1.422 0 1.969.547.547 1.422.547 1.969 0l2.975-2.953c.547-.547.547-1.444 0-1.991-.273-.273-.626-.416-.984-.416m-20.322 8.422c-.773 0-1.4.627-1.4 1.4 0 .773.627 1.4 1.4 1.4l4.2 0c.773 0 1.4-.627 1.4-1.4 0-.773-.627-1.4-1.4-1.4l-4.2 0m19.6 0c-.773 0-1.4.627-1.4 1.4 0 .773.627 1.4 1.4 1.4l4.2 0c.773 0 1.4-.627 1.4-1.4 0-.773-.627-1.4-1.4-1.4l-4.2 0m-13.147 5.447c-.358 0-.711.142-.984.416l-2.975 2.953c-.547.547-.547 1.444 0 1.991.547.547 1.444.547 1.991 0l2.953-2.975c.547-.547.547-1.422 0-1.969-.273-.273-.626-.416-.984-.416m10.894 0c-.358 0-.711.142-.984.416-.547.547-.547 1.422 0 1.969l2.953 2.975c.547.547 1.444.547 1.991 0 .547-.547.547-1.444 0-1.991l-2.975-2.953c-.273-.273-.626-.416-.984-.416m-5.447 2.253c-.773 0-1.4.627-1.4 1.4l0 4.2c0 .773.627 1.4 1.4 1.4.773 0 1.4-.627 1.4-1.4l0-4.2c0-.773-.627-1.4-1.4-1.4" fill="#40b1d0" fill-rule="nonzero"/></g></svg>
			</span>
			<span class="imagify-cell-checkbox-box <?php echo $data['checkbox_class']; ?>" <?php echo $data['checkbox_aria']; ?>>
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
			'value'         => $data['level'],
			'values'        => [
				0 => imagify_get_optimization_level_label( 0, '%ICON% %s' ),
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
