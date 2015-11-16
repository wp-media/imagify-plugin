<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );

/**
 * Add Imagify menu in the admin bar
 *
 * @since 1.0
 */
add_action( 'admin_bar_menu', '_imagify_admin_bar', PHP_INT_MAX );
function _imagify_admin_bar( $wp_admin_bar ) {
	// if wrong user rights
	if ( ! current_user_can( 'upload_files' ) ) {
		return;
	}
	// if user deactivate the menu by himself
	if ( get_imagify_option( 'admin_bar_menu', 0 ) !== '1' ) {
		return;
	}
	
	$cap = ( imagify_is_active_for_network() ) ? 'manage_network_options' : 'manage_options';
	
	// Parent
    $wp_admin_bar->add_menu( array(
	    'id'    => 'imagify',
	    'title' => 'Imagify',
	    'href'  => ( current_user_can( apply_filters( 'imagify_capacity', $cap ) ) ) ? get_imagify_admin_url() : '#',
	));
	
	/** This filter is documented in inc/admin/options.php */
	if (  current_user_can( apply_filters( 'imagify_capacity', $cap ) ) )  {
		// Settings
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-settings',
			'title'  => __( 'Settings' ),
		    'href'   => get_imagify_admin_url(),
		));	
	}
	
	// Bulk Optimization
	if ( imagify_valid_key() && ! is_network_admin() && current_user_can( 'upload_files' ) ) {
		$wp_admin_bar->add_menu(array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-bulk-optimization',
			'title'  => __( 'Bulk Optimization', 'imagify' ),
		    'href'   => get_imagify_admin_url( 'bulk-optimization' ),
		));
	}

	// Quota & Profile informations
	if ( current_user_can( apply_filters( 'imagify_capacity', $cap ) ) ) {		
		$user = new Imagify_User();

		$unconsumed_quota	= $user->get_percent_unconsumed_quota();
		$meteo_icon			=  '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'sun.svg" width="37" height="38" alt="" />'; 
		$bar_class			= 'positive';
		$message			= '';
		
		if ( $unconsumed_quota >= 21 && $unconsumed_quota <= 50 ) {
			$bar_class	= 'neutral';
			$meteo_icon	= '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'cloudy-sun.svg" width="37" height="38" alt="" />';
		}
		elseif ( $unconsumed_quota <= 20 ) {
			$bar_class	= 'negative';
			$meteo_icon	= '<img src="' . IMAGIFY_ASSETS_IMG_URL . 'stormy.svg" width="38" height="36" alt="" />';
		}

		if ( $unconsumed_quota <= 20 && $unconsumed_quota > 0 ) {
			$message = '
				<div class="imagify-error">
					<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s almost over!', 'imagify' ) . '</strong></p>
 					<p>' . sprintf( __( 'You have almost used all your credit.%sDon\'t forget to upgrade your subscription to continue optimizing your images.', 'imagify' ), '<br/><br/>' ) . '</p>
					<p class="center txt-center text-center"><a class="btn btn-ghost" href="' . IMAGIFY_APP_MAIN . '/#/subscription" target="_blank">' . __( 'View My Subscription', 'imagify' ) . '</a></p>
				</div>
			';
		}

		if ( $unconsumed_quota === 0 ) {
			$message = '
				<div class="imagify-error">
					<p><i class="dashicons dashicons-warning" aria-hidden="true"></i><strong>' . __( 'Oops, It\'s Over!', 'imagify' ) . '</strong></p>
					<p>' . sprintf( __( 'You have consumed all your credit for this month. You will have <strong>%s back on %s</strong>.', 'imagify' ), size_format( $user->quota * 1048576 ), date_i18n( get_option( 'date_format' ), strtotime( $user->next_date_update ) ) ) . '</p>
					<p class="center txt-center text-center"><a class="btn btn-ghost" href="' . IMAGIFY_APP_MAIN . '/#/subscription" target="_blank">' . __( 'Upgrade My Subscription', 'imagify' ) . '</a></p>
				</div>
			';
		}

		// custom HTML
		$quota_section = '
			<div class="imagify-admin-bar-quota">
				<div class="imagify-abq-row">';

		if ( 1 === $user->plan_id ) {
			$quota_section .= '
					<div class="imagify-meteo-icon">
						' . $meteo_icon . '
					</div>';
		}

		$quota_section .= '
					<div class="imagify-account">
						<p class="imagify-meteo-title">' . __( 'Account status', 'imagify' ) . '</p>
						<p class="imagify-meteo-subs">' . __( 'Your subscription:', 'imagify' ) . '&nbsp;<strong class="imagify-user-plan">' . $user->get_plan_label() . '</strong></p>
					</div>
				</div>';

		if ( 1 === $user->plan_id ) {
			$quota_section .= '
				<div class="imagify-abq-row">
					<div class="imagify-space-left">
						<p>' . sprintf( __( 'You have %s space credit left', 'imagify'), '<span id="imagify-unconsumed-percent">' . $unconsumed_quota . '%</span>' ) . '</p>
						<div class="imagify-bar-' . $bar_class . '">
							<div style="width: ' . $unconsumed_quota . '%;" class="imagify-progress" id="imagify-unconsumed-bar"></div>
						</div>
					</div>
				</div>';
		}

		$quota_section .= '
				<p class="imagify-abq-row">
					<a class="imagify-account-link" href="' . IMAGIFY_APP_MAIN . '/#/subscription" target="_blank">
						<span class="dashicons dashicons-admin-users"></span>
						<span class="button-text">' . __( 'View my subscription', 'imagify' ) . '</span>
					</a>
				</p>
			</div>
			' . $message;

		// insert custom HTML
		$wp_admin_bar->add_menu( array(
			'parent' => 'imagify',
			'id' 	 => 'imagify-profile',
			'title'  => $quota_section
		) );	
	}

	// TO DO - Rate it & Support
}

/**
 * Include Admin Bar Profile informations styles in front
 * 
 * @since  1.1.7
 */
add_action( 'admin_bar_init', '_imagify_admin_bar_styles' );
function _imagify_admin_bar_styles() {
	if ( ! is_admin() && get_imagify_option( 'admin_bar_menu', 0 ) === '1' ) {
		$css_ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
		wp_enqueue_style( 'imagify_admin_bar', IMAGIFY_ASSETS_CSS_URL . 'admin-bar' . $css_ext, array(), IMAGIFY_VERSION, 'all' );
	}
}
