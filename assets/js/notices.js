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

// All notices =====================================================================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * Close an Imagify notice.
	 */
	$( '.imagify-notice-dismiss' ).on( 'click.imagify', function( e ) {
		var $this   = $( this ),
			$parent = $this.parents( '.imagify-welcome, .imagify-notice, .imagify-rkt-notice' ),
			href    = $this.attr( 'href' );

		e.preventDefault();

		// Hide the notice.
		$parent.fadeTo( 100 , 0, function() {
			$( this ).slideUp( 100, function() {
				$( this ).remove();
			} );
		} );

		// Save the dismiss notice.
		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) );
	} );

} )(jQuery, document, window);


// The "welcome steps" notice + "wrong API key" notice =============================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * 1. Create a new Imagify account.
	 */
	$( '#imagify-signup' ).on( 'click.imagify', function( e ) {
		e.preventDefault();

		// Display the sign up form.
		swal( {
			title:               imagifyAdmin.labels.signupTitle,
			html:                imagifyAdmin.labels.signupText,
			confirmButtonText:   imagifyAdmin.labels.signupConfirmButtonText,
			input:               'email',
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyAdmin.labels.signupErrorEmptyEmail );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					setTimeout( function() {
						$.get( ajaxurl + imagify.concat + 'action=imagify_signup&email=' + inputValue + '&imagifysignupnonce=' + $( '#imagifysignupnonce' ).val() )
							.done( function( response ) {
								if ( ! response.success ) {
									reject( response.data );
								} else {
									resolve();
								}
							} );
					}, 2000 );
				} );
			},
		} ).then( function() {
			swal( {
				title:       imagifyAdmin.labels.signupSuccessTitle,
				html:        imagifyAdmin.labels.signupSuccessText,
				type:        'success',
				customClass: 'imagify-sweet-alert'
			} );
		} );
	} );

	/**
	 * 2. Check and save the Imagify API Key.
	 */
	$( '#imagify-save-api-key' ).on( 'click.imagify', function( e ) {
		e.preventDefault();

		// Display the API key form.
		swal( {
			title:               imagifyAdmin.labels.saveApiKeyTitle,
			html:                imagifyAdmin.labels.saveApiKeyText,
			confirmButtonText:   imagifyAdmin.labels.saveApiKeyConfirmButtonText,
			input:               'text',
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyAdmin.labels.ApiKeyErrorEmpty );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					$.get( ajaxurl + imagify.concat + 'action=imagify_check_api_key_validity&api_key=' + inputValue + '&imagifycheckapikeynonce=' + $( '#imagifycheckapikeynonce' ).val() )
						.done( function( response ) {
							if ( ! response.success ) {
								reject( response.data );
							} else {
								resolve();
							}
						} );
				} );
			},
		} ).then( function() {
			swal( {
				title:       imagifyAdmin.labels.ApiKeyCheckSuccessTitle,
				html:        imagifyAdmin.labels.ApiKeyCheckSuccessText,
				type:        'success',
				customClass: 'imagify-sweet-alert'
			} );
		} );
	} );

} )(jQuery, document, window);
