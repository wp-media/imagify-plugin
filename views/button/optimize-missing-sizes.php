<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$html_atts = '';

if ( empty( $data['atts'] ) ) {
	$data['atts'] = [];
}

if ( ! isset( $data['atts']['class'] ) ) {
	// Class used for JS.
	$data['atts']['class'] = 'optimize-missing-sizes';
}

if ( ! isset( $data['atts']['data-processing-label'] ) ) {
	// Used for JS.
	$data['atts']['data-processing-label'] = __( 'Optimizing...', 'imagify' );
}

$html_atts = $this->build_attributes( $data['atts'] );
?>

<a href="<?php echo esc_url( $data['url'] ); ?>"<?php echo $html_atts; ?>>
	<span class="dashicons dashicons-admin-generic"></span>
	<?php
	printf(
		/* translators: 1 is the number of thumbnails to optimize, 2 is the opening of a HTML tag that will be hidden on small screens, 3 is the closing tag. */
		esc_html( _n( '%2$sOptimize %3$s%1$d missing thumbnail', '%2$sOptimize %3$s%1$d missing thumbnails', $data['count'], 'imagify' ) ),
		number_format_i18n( $data['count'] ),
		'<span class="imagify-hide-if-small">',
		'</span>'
	);
	?>
</a>

<?php
if ( ! empty( $data['atts']['data-processing-label'] ) ) {
	$this->print_js_template_in_footer( 'button/processing' );
}
