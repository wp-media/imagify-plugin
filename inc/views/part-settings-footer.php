<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>

<div class="submit">
	<?php
	// Classical submit.
	submit_button();

	// Submit and go to bulk page.
	submit_button(
		esc_html__( 'Save &amp; Go to Bulk Optimizer', 'imagify' ),
		'secondary imagify-button-secondary', // Type/classes.
		'submit-goto-bulk', // Name (id).
		true, // Wrap.
		array() // Other attributes.
	);
	?>

	<div class="imagify-bulk-info">
		<p>
			<?php
			printf(
				/* translators: 1 is a link tag start, 2 is the link tag end. */
				__( 'Once your settings saved, optimize all your images by using the %1$sImagify Bulk Optimization%2$s feature.', 'imagify' ),
				'<a href="' . esc_url( get_imagify_admin_url( 'bulk-optimization' ) ) . '">',
				'</a>'
			);
			?>
		</p>
	</div>
</div>

<?php
