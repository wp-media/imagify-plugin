<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

?>
<div class="imagify-modal-promotion" aria-hidden="true">
	<p class="imagify-promo-title">
		<?php
		printf(
			/* translators: First %s is a formatted percentage, Second %s is a subscription plan name. */
			__( '%s OFF on %s subscriptions', 'imagify' ),
			'<span class="imagify-promotion-number"></span>',
			'<span class="imagify-promotion-plan-name"></span>',
		);
		?>
	</p>
	<p class="imagify-until-date">
		<?php
		printf(
			/* translators: %s is a formatted date. */
			__( 'Special Offer<br><strong>Until %s</strong>', 'imagify' ),
			'<span class="imagify-promotion-date"></span>'
		);
		?>
	</p>
</div>
<?php
