<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$html_atts = '';

if ( empty( $data['atts'] ) ) {
	$data['atts'] = [];
}

if ( ! isset( $data['atts']['class'] ) ) {
	// Class used for JS.
	$data['atts']['class'] = 'button button-imagify-optimize';
}

if ( ! isset( $data['atts']['data-processing-label'] ) ) {
	// Used for JS.
	$data['atts']['data-processing-label'] = __( 'Optimizing...', 'imagify' );
}

$html_atts = $this->build_attributes( $data['atts'] );

if ( ! empty( $data['error'] ) ) {
	?>
	<strong>
		<?php
		echo wp_kses(
			imagify_translate_api_message( $data['error'] ),
			[
				'br'     => true,
				'code'   => true,
				'em'     => true,
				'strong' => true,
			]
		);
		?>
	</strong>
	<br/>
	<?php
}
?>
<a href="<?php echo esc_url( $data['url'] ); ?>"<?php echo $html_atts; ?>>
	<?php esc_html_e( 'Try again', 'imagify' ); ?>
</a>

<?php
if ( ! empty( $data['atts']['data-processing-label'] ) ) {
	$this->print_js_template_in_footer( 'button/processing' );
}
