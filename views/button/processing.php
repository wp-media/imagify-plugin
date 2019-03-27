<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( empty( $data['label'] ) ) {
	$data['label'] = '%s';
}
?>

<div class="button button-imagify-processing">
	<span class="imagify-spinner"></span>
	<?php echo esc_html( $data['label'] ); ?>
</div>

<?php

$this->print_js_template_in_footer( 'button/processing' );
