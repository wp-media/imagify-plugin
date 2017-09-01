window.imagify = window.imagify || {
	concat: ajaxurl.indexOf( '?' ) > 0 ? '&' : '?',
	log:    function( content ) {
		if ( undefined !== console ) {
			console.log( content ); // eslint-disable-line no-console
		}
	},
	info:   function( content ) {
		if ( undefined !== console ) {
			console.info( content ); // eslint-disable-line no-console
		}
	}
};

// Admin bar =======================================================================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var busy = false;

	$( '#wp-admin-bar-imagify' ).hover( function() {
		var $adminBarProfile;

		if ( true === busy ) {
			return;
		}

		busy = true;

		$adminBarProfile = $( '#wp-admin-bar-imagify-profile-content' );

		if ( ! $adminBarProfile.is( ':empty' ) ) {
			return;
		}

		$.get( ajaxurl + imagify.concat + 'action=imagify_get_admin_bar_profile&imagifygetadminbarprofilenonce=' + $( '#imagifygetadminbarprofilenonce' ).val() )
			.done( function( response ) {
				$adminBarProfile.html( response.data );
				$( '#wp-admin-bar-imagify-profile-loading' ).remove();
				busy = false;
			} );
	} );

} )(jQuery, document, window);
