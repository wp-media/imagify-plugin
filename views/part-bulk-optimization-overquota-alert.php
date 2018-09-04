<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$overquota_url = imagify_get_external_url( 'subscription', array(
	'utm_source'  => 'plugin',
	'utm_medium'  => 'imagify-wp',
	'utm_content' => 'over-quota',
) );
?>
<div class="imagify-swal-subtitle"><?php esc_html_e( 'Upgrade your account to continue optimizing your images.', 'imagify' ); ?></div>
<div class="imagify-swal-content imagify-txt-start">
	<?php if ( Imagify_Requirements::is_api_key_valid() ) { ?>
		<strong><?php esc_html_e( 'To continue optimizing your images, you can:', 'imagify' ); ?></strong>
		<ul class="imagify-count-list">
			<li>
				<?php
				/* translators: 1 is the beginning of strong tag 2 is the closing tag. */
				printf( esc_html__( '%1$sUpgrade your subscription%2$s to optimize more images per month', 'imagify' ), '<strong>', '</strong>&nbsp;' );
				?>
			</li>
			<li>
				<?php
				/* translators: 1 is the beginning of strong tag 2 is the closing tag. */
				printf( esc_html__( '%1$sBuy a One Time plan%2$s to optimize the remaining images only', 'imagify' ), '<strong>', '</strong>&nbsp;' );
				?>
			</li>
		</ul>
	<?php } ?>
</div>
<div class="imagify-swal-buttonswrapper">
	<a href="<?php echo esc_url( $overquota_url ); ?>" target="_blank" class="imagify-button imagify-button-primary button">
		<svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><g fill="#000" fill-rule="nonzero" transform="translate(1 1)" stroke="#fff"><polygon points="8.75 0 8.75 0.7 12.8065 0.7 5.0015 8.5015 5.4985 8.9985 13.3 1.1935 13.3 5.25 14 5.25 14 0"/><polygon points="11.9 13.3 0.7 13.3 0.7 2.1 6.3 2.1 6.3 1.4 0 1.4 0 14 12.6 14 12.6 7.7 11.9 7.7"/></g></svg>
		<?php esc_html_e( 'See our plans on the Imagify’s website', 'imagify' ); ?>
	</a>
</div>
