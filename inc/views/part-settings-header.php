<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>
<h1 class="screen-reader-text"><?php esc_html_e( 'Settings', 'imagify' ); ?> — Imagify <?php echo IMAGIFY_VERSION; ?></h1>

<div class="imagify-title">
	<p class="imagify-logo-block">
		<img width="191" height="22" alt="Imagify" src="<?php echo IMAGIFY_ASSETS_IMG_URL; ?>imagify-logo.png" class="imagify-logo" />
		<small>
			<sup><?php echo IMAGIFY_VERSION; ?></sup>
		</small>
	</p>

	<p class="imagify-rate-us">
		<?php
		printf(
			/* translators: 1 is a "bold" tag start, 2 is the "bold" tag end + a line break tag, 3 is a link tag start, 4 is the link tag end. */
			__( '%1$sDo you like this plugin?%2$s Please take a few seconds to %3$srate it on WordPress.org%4$s!', 'imagify' ),
			'<strong>',
			'</strong><br />',
			'<a href="' . esc_url( imagify_get_external_url( 'rate' ) ) . '" target="_blank">',
			'</a>'
		);
		?>
		<br>
		<a class="stars" aria-hidden="true" href="<?php echo esc_url( imagify_get_external_url( 'rate' ) ); ?>" target="_blank"><?php echo str_repeat( '<span class="dashicons dashicons-star-filled"></span>', 5 ); ?></a>
	</p>

	<?php $this->print_template( 'part-documentation-link' ); ?>
</div>
<?php
