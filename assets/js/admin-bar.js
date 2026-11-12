// Admin bar =======================================================================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var busy = false;

	$( d ).on( 'mouseenter', '#wp-admin-bar-imagify', function() {
		var $adminBarProfile, url, $adminBarPricing;

		if ( true === busy ) {
			return;
		}

		busy = true;

		$adminBarProfile = $( '#wp-admin-bar-imagify-profile-content' );
		$adminBarPricing = $( '#wp-admin-bar-imagify-pricing-content' );

		if ( ! $adminBarProfile.is( ':empty' ) ) {
			return;
		}

		if ( w.ajaxurl ) {
			url = w.ajaxurl;
		} else {
			url = w.imagifyAdminBar.ajaxurl;
		}

		url += url.indexOf( '?' ) > 0 ? '&' : '?';

		$.get( url + 'action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce=' + $( '#imagifygetadminbarprofilenonce' ).val() )
			.done( function( response ) {
				var $templates = response.data;
				$( '#wp-admin-bar-imagify-profile-loading' ).remove();
				if ( $templates.admin_bar_pricing ) {
					$adminBarPricing.html( $templates.admin_bar_pricing );
				} else {
					$adminBarPricing.remove();
				}

				if ( $templates.admin_bar_status ) {
					$adminBarProfile.html( $templates.admin_bar_status );
				}

				busy = false;
			} );
	} );

} )(jQuery, document, window);
