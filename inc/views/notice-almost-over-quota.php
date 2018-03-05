<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$this->print_template( 'notice-header', array(
	'classes' => array( 'imagify-flex-notice-content', 'error' ),
) );

$user              = new Imagify_User();
$unconsumed_quota  = $user->get_percent_unconsumed_quota();
$meteo_icon        = '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="64" height="63" alt="" />';
$bar_class         = 'negative';
?>
<div class="imagify-notice-quota">
	<div class="imagify-flex imagify-vcenter">
		<span class="imagify-meteo-icon imagify-noshrink"><?php echo $meteo_icon; ?></span>
		<div class="imagify-space-left">
			<p>
				<?php
				printf(
					/* translators: %s is a data quota. */
					esc_html__( 'You have %s space credit left' , 'imagify' ),
					'<span class="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>'
				);
				?>
			</p>

			<div class="imagify-bar-<?php echo $bar_class; ?>">
				<div class="imagify-unconsumed-bar imagify-progress" style="width: <?php echo $unconsumed_quota . '%'; ?>;"></div>
			</div>
		</div>
	</div>
</div>
<p>
	<?php esc_html_e( 'You are running out of credit! Don’t forget to upgrade your Imagify’s account.', 'imagify' ); ?>

	<a target="_blank" class="button imagify-button imagify-button-secondary button-mini" href="<?php echo esc_url( imagify_get_external_url( 'subscription' ) ); ?>"><?php esc_html_e( 'See our plans', 'imagify' ); ?></a>
</p>
<?php
$this->print_template( 'notice-footer', array(
	'dismissible' => 'almost-over-quota',
) );
