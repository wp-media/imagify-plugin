<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>

<div class="submit imagify-clearfix">
	<?php
	// Classical submit.
	submit_button();

	if ( imagify_valid_key() ) {
		// Submit and go to bulk page.
		submit_button(
			esc_html__( 'Save & Go to Bulk Optimizer', 'imagify' ),
			'secondary imagify-button-secondary', // Type/classes.
			'submit-goto-bulk', // Name (id).
			true, // Wrap.
			array() // Other attributes.
		);
	}
	?>
</div>

<?php
