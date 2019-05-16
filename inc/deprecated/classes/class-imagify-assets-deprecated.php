<?php
defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * Class containing deprecated methods of Imagify_Assets.
 *
 * @since  1.9.2
 * @author Grégory Viguier
 */
class Imagify_Assets_Deprecated {

	/**
	 * Add Intercom on Options page an Bulk Optimization.
	 * Previously was _imagify_admin_print_intercom()
	 *
	 * @since  1.6.10
	 * @since  1.9.2 Deprecated.
	 * @author Grégory Viguier
	 * @deprecated
	 */
	public function print_support_script() {
		_deprecated_function( get_class( $this ) . '::' . __FUNCTION__ . '()', '1.9.2' );

		if ( ! Imagify_Requirements::is_api_key_valid() ) {
			return;
		}

		$user = get_imagify_user();

		if ( empty( $user->is_intercom ) || empty( $user->display_support ) ) {
			return;
		}
		?>
		<script>
		window.intercomSettings = {
			app_id: 'cd6nxj3z',
			user_id: <?php echo (int) $user->id; ?>
		};
		(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/cd6nxj3z';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()
		</script>
		<?php
	}
}
