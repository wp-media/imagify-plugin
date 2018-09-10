<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

$this->print_template( 'notice-header', array(
	'classes' => array( 'error' ),
) );
?>
<p><?php echo _n( 'The following plugin is not compatible with this plugin and may cause unexpected results:', 'The following plugins are not compatible with this plugin and may cause unexpected results:', count( $data ), 'imagify' ); ?></p>

<ul class="imagify-plugins-error">
<?php
foreach ( $data as $plugin ) {
	$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin );
	$deactivate_url = esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=imagify_deactivate_plugin&plugin=' . rawurlencode( $plugin ) ), Imagify_Notices::DEACTIVATE_PLUGIN_NONCE_ACTION ) );
	echo '<li>' . $plugin_data['Name'] . '</span> <a href="' . $deactivate_url . '" class="button button-mini alignright">' . __( 'Deactivate', 'imagify' ) . '</a></li>';
}
?>
</ul>
<?php
$this->print_template( 'notice-footer' );
