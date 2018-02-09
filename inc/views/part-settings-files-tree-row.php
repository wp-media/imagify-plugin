<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$disabled_attr  = disabled( true, $data['checkbox_selected'], false );
$disabled_class = $data['checkbox_selected'] ? ' disabled' : '';
?>

<li>
	<?php if ( empty( $data['no_button'] ) ) : ?>
		<button type="button" class="imagify-folder" data-folder="<?php echo $data['relative_path']; ?>"<?php echo $disabled_attr; ?> title="<?php
			/* translators: %s is a folder path. */
			printf( esc_attr__( 'Open/Close the folder "%s".', 'imagify' ), $data['relative_path'] );
			?>">
			<span class="dashicons dashicons-category"></span>
		</button>
	<?php else : ?>
		<span class="imagify-folder<?php echo $disabled_class; ?>">
			<span class="dashicons dashicons-category"></span>
		</span>
	<?php endif; ?>

	<input type="checkbox" name="imagify-custom-files[]" value="<?php echo $data['checkbox_value']; ?>" id="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" class="screen-reader-text"<?php echo $disabled_attr; ?>/>

	<label for="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" title="<?php
		/* translators: %s is a folder path. */
		printf( esc_attr__( 'Select the folder "%s".', 'imagify' ), $data['relative_path'] );
		?>">
		<?php echo esc_html( $data['label'] ); ?>
	</label>
</li>
