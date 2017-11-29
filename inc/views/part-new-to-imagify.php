<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

if ( ! imagify_current_user_can() || ! imagify_valid_key() ) {
	return '';
}

/**
 * Filter whether the plan chooser section is displayed.
 *
 * @since  1.6
 * @author Geoffrey
 *
 * @param $show_new bool Default to true: display the section.
 */
$show_new = apply_filters( 'imagify_show_new_to_imagify', true );

if ( ! $show_new ) {
	return '';
}

?>

<div class="imagify-section imagify-section-positive">
	<div class="imagify-start imagify-mr2">
		<button id="imagify-get-pricing-modal" data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>" data-target="#imagify-pricing-modal" type="button" class="imagify-modal-trigger imagify-button imagify-button-light imagify-button-big">
			<i class="dashicons dashicons-dashboard" aria-hidden="true"></i>
			<span class="button-text"><?php _e( 'What plan do I need?', 'imagify' ); ?></span>
		</button>
	</div>
	<div class="imagify-oh">
		<p class="imagify-section-title"><?php _e( 'You\'re new to Imagify?', 'imagify' ); ?></p>
		<p><?php _e( 'Let us help you by analyzing your existing images and determine the best plan for you', 'imagify' ); ?></p>
	</div>
</div>

<?php
