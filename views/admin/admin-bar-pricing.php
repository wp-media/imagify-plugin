<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

if ( $data['upgrade_pricing'] ) : ?>
	<button data-nonce="<?php echo wp_create_nonce( 'imagify_get_pricing_' . get_current_user_id() ); ?>"
			data-target="#imagify-pricing-modal" type="button"
			class="imagify-get-pricing-modal imagify-modal-trigger imagify-admin-bar-upgrade-plan">
		<?php esc_html_e( 'Upgrade Plan', 'imagify' ); ?>
	</button>
<?php endif; ?>
