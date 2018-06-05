window.imagify = window.imagify || {};

jQuery.extend( window.imagify, {
	retina2x: {
		hookAjax: function() {
			jQuery( document ).ajaxSend( this.addData ).ajaxSuccess( this.updateInfo );
		},
		addData: function( e, jqXHR, settings ) {
			var isString, isFormData, actions, action, id;

			if ( ! settings.data ) {
				return;
			}

			isString   = typeof settings.data === 'string';
			isFormData = settings.data instanceof FormData;
			actions    = window.imagifyRetina2x;

			if ( isString ) {
				action = settings.data.match( /(?:^|&)action=([^&]+)/ );
				action = action ? action[1] : false;
				id     = settings.data.match( /(?:^|&)attachmentId=([^&]+)/ );
				id     = id ? parseInt( id[1], 10 ) : 0;
			} else if ( isFormData ) {
				action = settings.data.get( 'action' );
				id     = settings.data.get( 'attachmentId' );
			} else {
				action = settings.data.action;
				id     = settings.data.id;
			}

			if ( ! action || ! actions[ action ] ) {
				return;
			}

			if ( ! jQuery( '.imagify_optimized_file' ).length ) {
				id = 0;
			}

			if ( isString ) {
				settings.data += '&imagify_nonce=' + actions[ action ];

				if ( id > 0 ) {
					settings.data += '&imagify_info=1';
				}
			} else if ( isFormData ) {
				settings.data.append( 'imagify_nonce', actions[ action ] );

				if ( id > 0 ) {
					settings.data.append( 'imagify_info', 1 );
				}
			} else {
				settings.data = jQuery.extend( settings.data, { 'imagify_nonce': actions[ action ] } );

				if ( id > 0 ) {
					settings.data = jQuery.extend( settings.data, { 'imagify_info': 1 } );
				}
			}
		},
		updateInfo: function( e, jqXHR, settings, data ) {
			if ( typeof settings.data !== 'string' ) {
				return;
			}

			try {
				data = jQuery.parseJSON( data );
			}
			catch ( error ) {
				return;
			}

			if ( 'imagify' !== data.source || 'wr2x' !== data.context || ! data.imagify_info ) {
				return;
			}

			jQuery.each( data.imagify_info, function ( id, html ) {
				var $wrapper = jQuery( '#post-' + id ).children( '.imagify_optimized_file' );

				if ( $wrapper.length ) {
					$wrapper.html( html );
				}
			} );
		}
	}
} );

window.imagify.retina2x.hookAjax();
