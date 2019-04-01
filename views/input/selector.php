<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( empty( $data['values'] ) || empty( $data['name'] ) ) {
	return;
}

if ( ! isset( $data['value'] ) ) {
	$data['value'] = key( $data['values'] );
}

if ( empty( $data['current_label'] ) ) {
	$data['current_label'] = __( 'Current value:', 'imagify' );
}

if ( ! isset( $data['values'][ $data['value'] ] ) ) {
	return;
}

$list_id = str_replace( [ '[', ']' ], [ '-', '' ], $data['name'] );
$list_id = 'imagify-' . $list_id . '-selector-list';
?>

<div class="imagify-selector">
	<span class="hide-if-js">
		<?php echo esc_html( $data['current_label'] ); ?>
		<span class="imagify-selector-current-value-info"><?php echo $data['values'][ $data['value'] ]; ?></span>
	</span>

	<button aria-controls="<?php echo esc_attr( $list_id ); ?>" type="button" class="button imagify-button-clean hide-if-no-js imagify-selector-button">
		<span class="imagify-selector-current-value-info"><?php echo $data['values'][ $data['value'] ]; ?></span>
	</button>

	<ul id="<?php echo esc_attr( $list_id ); ?>" role="listbox" aria-orientation="vertical" aria-hidden="true" class="imagify-selector-list hide-if-no-js">
		<?php
		foreach ( $data['values'] as $val => $label ) {
			$input_id = $list_id . '-' . sanitize_html_class( $val );
			?>
			<li class="imagify-selector-choice<?php echo $val === $data['value'] ? ' imagify-selector-current-value" aria-current="true' : ''; ?>" role="option">
				<input type="radio" name="<?php echo esc_attr( $data['name'] ); ?>" value="<?php echo esc_attr( $val ); ?>" id="<?php echo esc_attr( $input_id ); ?>" <?php checked( $val, $data['value'] ); ?> class="screen-reader-text">
				<label for="<?php echo esc_attr( $input_id ); ?>"><?php echo $label; ?></label>
			</li>
			<?php
		}
		?>
	</ul>
</div>
