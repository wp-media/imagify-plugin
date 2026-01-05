<?php
use Imagify\Notices\Notices;

defined( 'ABSPATH' ) || exit;

$this->print_template(
	'notice-header',
	[
		'classes' => [ 'error' ],
	]
);
?>
<p><?php echo esc_html( _n( 'The following plugin is not compatible with this plugin and may cause unexpected results:', 'The following plugins are not compatible with this plugin and may cause unexpected results:', count( $data ), 'imagify' ) ); ?></p>

<ul class="imagify-plugins-error">
<?php
foreach ( $data as $plugin_name ) {
	$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_name );
	$deactivate_url = wp_nonce_url( admin_url( 'admin-post.php?action=imagify_deactivate_plugin&plugin=' . rawurlencode( $plugin_name ) ), Notices::DEACTIVATE_PLUGIN_NONCE_ACTION );
	echo '<li>' . esc_html( $plugin_data['Name'] ) . '</span> <a href="' . esc_url( $deactivate_url ) . '" class="button button-mini alignright">' . esc_html__( 'Deactivate', 'imagify' ) . '</a></li>';
}
?>
</ul>
<?php
$this->print_template( 'notice-footer' );
