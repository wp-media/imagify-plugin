<?php
/**
 * Troubleshooting Tools section — Settings page.
 *
 * Renders the "Reset Internal State" button and its inline JS handler.
 * Displayed at the bottom of the Settings page, outside the main settings form.
 *
 * @since 2.3
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="imagify-settings-main-content">
	<div class="imagify-settings-section imagify-clear">

		<h2 class="imagify-options-title"><?php esc_html_e( 'Troubleshooting', 'imagify' ); ?></h2>

		<p class="imagify-options-subtitle">
			<?php esc_html_e( 'If optimization is stuck, showing endless loading, or displaying false "out of quota" errors, use the button below to reset Imagify\'s internal state. This clears cached data and cancels stuck background jobs without deleting your settings or optimization data.', 'imagify' ); ?>
		</p>

		<p class="imagify-setting-line">
			<button
				type="button"
				id="imagify-reset-internal-state"
				class="button button-secondary"
				data-nonce="<?php echo esc_attr( wp_create_nonce( \Imagify\Tools\Subscriber::NONCE_ACTION ) ); ?>"
			>
				<?php esc_html_e( 'Reset Internal State', 'imagify' ); ?>
			</button>
			<span id="imagify-reset-internal-state-feedback" style="margin-left: 8px; vertical-align: middle;"></span>
		</p>

	</div>
</div>

<script>
( function ( $ ) {
	'use strict';

	$( '#imagify-reset-internal-state' ).on( 'click', function () {
		var $btn      = $( this );
		var $feedback = $( '#imagify-reset-internal-state-feedback' );

		$btn.prop( 'disabled', true );
		$feedback.text( <?php echo wp_json_encode( __( 'Resetting…', 'imagify' ) ); ?> ).css( 'color', '' );

		$.ajax( {
			url:  ajaxurl,
			type: 'POST',
			data: {
				action:      'imagify_reset_internal_state',
				_ajax_nonce: $btn.data( 'nonce' )
			}
		} ).done( function ( response ) {
			if ( response && response.success ) {
				$feedback.text( response.data.message ).css( 'color', '#00a32a' );
			} else {
				var msg = ( response && response.data && response.data.message )
					? response.data.message
					: <?php echo wp_json_encode( __( 'An error occurred. Please try again.', 'imagify' ) ); ?>;
				$feedback.text( msg ).css( 'color', '#d63638' );
			}
		} ).fail( function () {
			$feedback.text( <?php echo wp_json_encode( __( 'Request failed. Please try again.', 'imagify' ) ); ?> ).css( 'color', '#d63638' );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );
}( jQuery ) );
</script>
