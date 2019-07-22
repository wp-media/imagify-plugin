<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$html_atts = '';

if ( empty( $data['atts'] ) ) {
	$data['atts'] = [];
}

if ( ! isset( $data['atts']['class'] ) ) {
	// Class used for JS.
	$data['atts']['class'] = 'button-imagify-delete-webp';
}

if ( ! isset( $data['atts']['data-processing-label'] ) ) {
	// Used for JS.
	$data['atts']['data-processing-label'] = __( 'Deleting...', 'imagify' );
}

$html_atts = $this->build_attributes( $data['atts'] );
?>

<a href="<?php echo esc_url( $data['url'] ); ?>"<?php echo $html_atts; ?>>
	<span class="dashicons dashicons-image-rotate"></span>
	<?php esc_html_e( 'Delete webp images', 'imagify' ); ?>
</a>

<?php
if ( ! empty( $data['atts']['data-processing-label'] ) ) {
	$this->print_js_template_in_footer( 'button/processing' );
}
