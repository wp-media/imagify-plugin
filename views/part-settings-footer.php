<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>

<div class="submit imagify-clearfix">
	<?php
	// Classical submit.
	submit_button();

	if ( Imagify_Requirements::is_api_key_valid() ) {
		$user_can = imagify_get_context( 'wp' )->current_user_can( 'bulk-optimize' ) || imagify_get_context( 'custom-folders' )->current_user_can( 'bulk-optimize' );

		if ( $user_can ) {
			// Submit and go to bulk page.
			submit_button(
				esc_html__( 'Save & Go to Bulk Optimizer', 'imagify' ),
				'secondary imagify-button-secondary', // Type/classes.
				'submit-goto-bulk', // Name (id).
				true, // Wrap.
				array() // Other attributes.
			);
		}
	}
	?>
</div>

<?php
