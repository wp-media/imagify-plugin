<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$html_atts = '';

if ( empty( $data['atts'] ) ) {
	$data['atts'] = [];
}

if ( ! isset( $data['atts']['class'] ) ) {
	// Class used for JS.
	$data['atts']['class'] = 'button-imagify-manual-reoptimize';
}

if ( ! isset( $data['atts']['data-processing-label'] ) ) {
	// Used for JS.
	$data['atts']['data-processing-label'] = __( 'Optimizing...', 'imagify' );
}

$level_labels = [
	__( 'Normal', 'imagify' ),
	__( 'Aggressive', 'imagify' ),
	__( 'Ultra', 'imagify' ),
];
$level_label = $level_labels[ $data['optimization_level'] ];

$html_atts = $this->build_attributes( $data['atts'] );
?>

<a href="<?php echo esc_url( $data['url'] ); ?>"<?php echo $html_atts; ?>>
	<span class="dashicons dashicons-admin-generic"></span>
	<span class="imagify-hide-if-small">
		<?php
		printf(
			/* translators: %s is an optimization level. */
			esc_html__( 'Re-Optimize to %s', 'imagify' ),
			'</span>' . esc_html( $level_label ) . '<span class="imagify-hide-if-small">'
		);
		?>
	</span>
</a>

<?php
if ( ! empty( $data['atts']['data-processing-label'] ) ) {
	$this->print_js_template_in_footer( 'button/processing' );
}
