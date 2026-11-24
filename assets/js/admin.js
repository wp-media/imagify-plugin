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
		var target = $link.data( 'target' ) || $link.attr( 'href' ),
			$target = jQuery( target );

		// Remember the opener, to give the focus back to it when the modal closes.
		$target.data( 'imagifyOpener', $link.get( 0 ) );

		$target.css( 'display', 'flex' ).hide().fadeIn( 400 ).attr( {
			'aria-hidden': 'false',
			'tabindex':    '0'
		} ).trigger('focus').removeAttr( 'tabindex' ).addClass( 'modal-is-open' );

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
			var $modal = $( this ).closest( '.imagify-modal' ),
				modal    = $modal.get( 0 ),
				openerEl = $modal.data( 'imagifyOpener' );

			// Leave the open state first, so the close button `blur` handler below does not pull the focus back into the modal.
			$modal.removeClass( 'modal-is-open' );

			// Move the focus out of the modal before hiding it: Chrome blocks `aria-hidden` on an element that still contains the focused element.
			if ( modal.contains( d.activeElement ) ) {
				if ( openerEl && d.body.contains( openerEl ) ) {
					openerEl.focus();
				}

				// No usable opener (modal opened by code, or opener not focusable).
				if ( modal.contains( d.activeElement ) ) {
					d.activeElement.blur();
				}
			}

			$modal.fadeOut( 400 ).attr( 'aria-hidden', 'true' ).trigger( 'modalClosed.imagify' );

			$( 'body' ).removeClass( 'imagify-modal-is-open' );
		} )
		// On close button blur, improve accessibility.
		.on( 'blur.imagify', '.imagify-modal .close-btn', function() {
			var $modal = $( this ).closest( '.imagify-modal' );

			if ( $modal.hasClass( 'modal-is-open' ) && $modal.attr( 'aria-hidden' ) === 'false' ) {
				$modal.attr( 'tabindex', '0' ).trigger('focus').removeAttr( 'tabindex' );
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
