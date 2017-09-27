<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$this->render_view( 'header' );
?>
<p>
	<?php
	printf(
		/* translators: 1 is a "bold" tag start, 2 is the "bold" tag end, 3 is a formatted number (don't use %3$d). */
		__( '%1$sCongratulations%2$s, you have optimized %1$s%3$s images%2$s and improved your website\'s speed by reducing your images size.', 'imagify' ),
		'<strong>',
		'</strong>',
		number_format_i18n( $data )
	);
	?>
</p>
<p class="imagify-rate-us">
	<?php
	$imagify_rate_url = 'https://wordpress.org/support/view/plugin-reviews/imagify?rate=5#postform';

	printf(
		/* translators: 1 is a "bold" tag start, 2 is the "bold" tag end + a line break tag, 3 is a link tag start, 4 is the link tag end. */
		__( '%1$sDo you like this plugin?%2$s Please take a few seconds to %3$srate it on WordPress.org%4$s!', 'imagify' ),
		'<strong>',
		'</strong><br />',
		'<a target="_blank" href="' . $imagify_rate_url . '">',
		'</a>'
	);
	?>
	<br>
	<a class="stars" target="_blank" href="<?php echo $imagify_rate_url; ?>">☆☆☆☆☆</a>
</p>
<?php
$this->render_view( 'footer', array(
	'dismissible' => 'rating',
) );
