<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$disabled_attr  = disabled( true, $data['checkbox_selected'], false );
$disabled_class = $data['checkbox_selected'] ? ' disabled' : '';
$folder_icon    = '<svg width="20px" height="17px" viewBox="0 0 20 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" transform="translate(-608.000000, -318.000000)" stroke-linecap="round" stroke-linejoin="round"><g transform="translate(609.000000, 319.000000)" stroke="#000000" stroke-width="2"><path d="M0,14.1428571 L18,14.1428571 L18,1.92857143 L7.71428571,1.92857143 L5.14285714,0 L0,0 L0,14.1428571 Z M18,5.14285714 L0,5.14285714 L18,5.14285714 Z"></path></g></g></svg>';
?>

<li<?php echo $data['checkbox_selected'] ? ' class="imagify-folder-already-selected"' : ''; ?>>
	<?php if ( empty( $data['no_button'] ) ) : ?>
		<button type="button" class="imagify-folder" data-folder="<?php echo $data['relative_path']; ?>"<?php echo $disabled_attr; ?> title="<?php
		if ( $data['checkbox_selected'] ) {
			/* translators: %s is a folder path. */
			printf( esc_attr__( 'The folder "%s" is already selected.', 'imagify' ), $data['relative_path'] );
		} else {
			/* translators: %s is a folder path. */
			printf( esc_attr__( 'Open/Close the folder "%s".', 'imagify' ), $data['relative_path'] );
		}
		?>">
			<?php if ( ! $data['checkbox_selected'] ) { ?>
				<span class="imagify-loader"><img alt="<?php esc_attr_e( 'Loading...', 'imagify' ); ?>" src="<?php echo esc_url( IMAGIFY_ASSETS_IMG_URL . 'spinner.gif' ); ?>" width="20" height="20"/></span>
			<?php } ?>
			<span class="imagify-folder-icon"><?php echo $folder_icon; ?></span>
		</button>
	<?php else : ?>
		<span class="imagify-folder<?php echo $disabled_class; ?>">
			<span class="imagify-folder-icon"><?php echo $folder_icon; ?></span>
		</span>
	<?php endif; ?>

	<input type="checkbox" name="imagify-custom-files[]" value="<?php echo $data['checkbox_value']; ?>" id="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" class="screen-reader-text"<?php echo $disabled_attr; ?>/>

	<label for="imagify-custom-folder-<?php echo $data['checkbox_id']; ?>" title="<?php
	if ( $data['checkbox_selected'] ) {
		/* translators: %s is a folder path. */
		printf( esc_attr__( 'The folder "%s" is already selected.', 'imagify' ), $data['relative_path'] );
	} else {
		/* translators: %s is a folder path. */
		printf( esc_attr__( 'Select the folder "%s".', 'imagify' ), $data['relative_path'] );
	}
	?>">
		<?php echo esc_html( $data['label'] ); ?>

		<span class="imagify-add-ed-folder">
			<?php
			if ( $data['checkbox_selected'] ) {
				_e( 'Folder Added', 'imagify' );
			} else {
				_ex( 'Add Folder', 'checkbox label', 'imagify' );
			}
			?>
			<span class="imagify-fake-checkbox"></span>
		</span>
	</label>
</li>
