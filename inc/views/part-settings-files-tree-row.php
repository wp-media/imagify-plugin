<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );
?>

<li>
	<?php if ( empty( $data['no_button'] ) ) : ?>
		<button type="button" data-folder="<?php echo $data['relative_path']; ?>" title="<?php
			/* translators: %s is a folder path. */
			printf( esc_attr__( 'Open/Close the folder "%s".', 'imagify' ), $data['relative_path'] );
			?>">
			<span class="dashicons dashicons-category"></span>
		</button>
	<?php else : ?>
		<span>
			<span class="dashicons dashicons-category"></span>
		</span>
	<?php endif; ?>

	<input type="checkbox" name="imagify-custom-files[]" value="<?php echo $data['checkbox_value']; ?>" id="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" class="screen-reader-text"<?php disabled( true, $data['checkbox_selected'] ); ?>/>

	<label for="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" title="<?php
		/* translators: %s is a folder path. */
		printf( esc_attr__( 'Select the folder "%s".', 'imagify' ), $data['relative_path'] );
		?>">
		<?php echo esc_html( $data['label'] ); ?>
	</label>
</li>
