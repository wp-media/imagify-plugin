<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

?>
<div class="imagify-modal-promotion" aria-hidden="true">
	<p class="imagify-promo-title">
		<?php
		printf(
			/* translators: %s is a formatted percentage. */
			__( '%s OFF on all the subscriptions', 'secupress' ),
			'<span class="imagify-promotion-number"></span>'
		);
		?>
	</p>
	<p class="imagify-until-date">
		<?php
		printf(
			/* translators: %s is a formatted date. */
			__( 'Special Offer<br><strong>Until %s</strong>', 'secupress' ),
			'<span class="imagify-promotion-date"></span>'
		);
		?>
	</p>
</div>
<?php
