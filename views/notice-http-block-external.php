<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$this->print_template( 'notice-header', array(
	'title'   => __( 'The external HTTP requests are blocked!', 'imagify' ),
	'classes' => array( 'error' ),
) );
?>
<p>
	<?php _e( 'You defined the <code>WP_HTTP_BLOCK_EXTERNAL</code> constant in the <code>wp-config.php</code> to block all external HTTP requests.', 'imagify' ); ?>
</p>
<p>
	<?php _e( 'To optimize your images, you have to put the following code in your <code>wp-config.php</code> file so that it works correctly.', 'imagify' ); ?><br/>
	<?php _e( 'Click on the field and press Ctrl-A to select all.', 'imagify' ); ?>
</p>
<p>
	<textarea readonly="readonly" class="large-text readonly" rows="1">define( 'WP_ACCESSIBLE_HOSTS', '*.imagify.io' );</textarea>
</p>
<?php
$this->print_template( 'notice-footer', array(
	'dismissible' => 'http-block-external',
) );
