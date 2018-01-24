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
			title:               imagifyNotices.labels.signupTitle,
			html:                imagifyNotices.labels.signupText,
			confirmButtonText:   imagifyNotices.labels.signupConfirmButtonText,
			input:               'email',
			padding:             0,
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyNotices.labels.signupErrorEmptyEmail );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					setTimeout( function() {
						$.get( ajaxurl + w.imagify.concat + 'action=imagify_signup&email=' + inputValue + '&imagifysignupnonce=' + $( '#imagifysignupnonce' ).val() )
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
				title:       imagifyNotices.labels.signupSuccessTitle,
				html:        imagifyNotices.labels.signupSuccessText,
				type:        'success',
				padding:     0,
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
			title:               imagifyNotices.labels.saveApiKeyTitle,
			html:                imagifyNotices.labels.saveApiKeyText,
			confirmButtonText:   imagifyNotices.labels.saveApiKeyConfirmButtonText,
			input:               'text',
			padding:             0,
			showLoaderOnConfirm: true,
			customClass:         'imagify-sweet-alert imagify-sweet-alert-signup',
			inputValidator:      function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					if ( $.trim( inputValue ) === '' || ! inputValue ) {
						reject( imagifyNotices.labels.ApiKeyErrorEmpty );
					} else {
						resolve();
					}
				} );
			},
			preConfirm:          function( inputValue ) {
				return new Promise( function( resolve, reject ) {
					$.get( ajaxurl + w.imagify.concat + 'action=imagify_check_api_key_validity&api_key=' + inputValue + '&imagifycheckapikeynonce=' + $( '#imagifycheckapikeynonce' ).val() )
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
				title:       imagifyNotices.labels.ApiKeyCheckSuccessTitle,
				html:        imagifyNotices.labels.ApiKeyCheckSuccessText,
				type:        'success',
				padding:     0,
				customClass: 'imagify-sweet-alert'
			} );
		} );
	} );

} )(jQuery, document, window);
