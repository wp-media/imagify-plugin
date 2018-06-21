window.imagify = window.imagify || {};

jQuery.extend( window.imagify, {
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
	},
	openModal: function( $link ) {
		var target = $link.data( 'target' ) || $link.attr( 'href' );

		jQuery( target ).css( 'display', 'flex' ).hide().fadeIn( 400 ).attr( {
			'aria-hidden': 'false',
			'tabindex':    '0'
		} ).focus().removeAttr( 'tabindex' ).addClass( 'modal-is-open' );

		jQuery( 'body' ).addClass( 'imagify-modal-is-open' );
	},
	template: function( id ) {
		if ( undefined === _ ) {
			// No need to load underscore everywhere if we don't use it.
			return '';
		}

		return _.memoize( function( data ) {
			var compiled,
				options = {
					evaluate:    /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape:      /\{\{([^}]+?)\}\}(?!\})/g,
					variable:    'data'
				};

			return function() {
				compiled = compiled || _.template( jQuery( '#tmpl-' + id ).html(), null, options );
				data     = data || {};
				return compiled( data );
			};
		} );
	},
	humanSize: function( bytes ) {
		var sizes = ['B', 'kB', 'MB'],
			i;

		if ( 0 === bytes ) {
			return '0\xA0kB';
		}

		i = parseInt( Math.floor( Math.log( bytes ) / Math.log( 1024 ) ), 10 );

		return ( bytes / Math.pow( 1024, i ) ).toFixed( 2 ) + '\xA0' + sizes[ i ];
	}
} );


// Imagify light modal =============================================================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	// Accessibility.
	$( '.imagify-modal' ).attr( 'aria-hidden', 'true' );

	$( d )
		// On click on modal trigger, open modal.
		.on( 'click.imagify', '.imagify-modal-trigger', function( e ) {
			e.preventDefault();
			w.imagify.openModal( $( this ) );
		} )
		// On click on close button, close modal.
		.on( 'click.imagify', '.imagify-modal .close-btn', function() {
			var $modal = $( this ).closest( '.imagify-modal' );

			$modal.fadeOut( 400 ).attr( 'aria-hidden', 'true' ).removeClass( 'modal-is-open' ).trigger( 'modalClosed.imagify' );

			$( 'body' ).removeClass( 'imagify-modal-is-open' );
		} )
		// On close button blur, improve accessibility.
		.on( 'blur.imagify', '.imagify-modal .close-btn', function() {
			var $modal = $( this ).closest( '.imagify-modal' );

			if ( $modal.attr( 'aria-hidden' ) === 'false' ) {
				$modal.attr( 'tabindex', '0' ).focus().removeAttr( 'tabindex' );
			}
		} )
		// On click on dropped layer of modal, close modal.
		.on( 'click.imagify', '.imagify-modal', function( e ) {
			$( e.target ).filter( '.modal-is-open' ).find( '.close-btn' ).trigger( 'click.imagify' );
		} )
		// `Esc` key binding, close modal.
		.on( 'keydown.imagify', function( e ) {
			if ( 27 === e.keyCode && $( '.imagify-modal.modal-is-open' ).length > 0 ) {
				e.preventDefault();
				// Trigger the event.
				$( '.imagify-modal.modal-is-open' ).find( '.close-btn' ).trigger( 'click.imagify' );
			}
		} );

} )(jQuery, document, window);
