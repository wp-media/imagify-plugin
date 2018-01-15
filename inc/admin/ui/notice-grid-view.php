<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

$this->render_view( 'header', array(
	'title' => __( 'You\'re missing out!', 'imagify' ),
) );
?>
<p><?php _e( 'Use the List view to optimize images with Imagify.', 'imagify' ); ?></p>
<p><a href="<?php echo esc_url( admin_url( 'upload.php?mode=list' ) ); ?>"><?php _e( 'Switch to the List View', 'imagify' ); ?></a></p>
<?php
$this->render_view( 'footer', array(
	'dismissible' => 'grid-view',
) );
