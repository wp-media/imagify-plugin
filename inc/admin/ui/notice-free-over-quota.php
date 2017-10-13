<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$this->render_view( 'header', array(
	'title' => __( 'Oops, It\'s Over!', 'imagify' ),
) );
?>
<p>
	<?php
	printf(
		/* translators: 1 is a "bold" tag start, 2 is a formatted data quota, 3 is a date, 4 is the "bold" tag end. */
		__( 'You have consumed all your credit for this month. You will have %1$s%2$s back on %3$s%4$s.', 'imagify' ),
		'<strong>',
		size_format( $data->quota * 1048576 ),
		date_i18n( get_option( 'date_format' ), strtotime( $data->next_date_update ) ),
		'</strong>'
	);
	echo '<br/><br/>';
	printf(
		/* translators: 1 is a link tag start, 2 is the link tag end. */
		__( 'To continue to optimize your images, log in to your Imagify account to %1$sbuy a pack or subscribe to a plan%2$s.', 'imagify' ),
		'<a target="_blank" href="' . esc_url( imagify_get_external_url( 'subscription' ) ) . '">',
		'</a>'
	);
	?>
</p>
<?php
$this->render_view( 'footer', array(
	'dismissible' => 'free-over-quota',
) );
