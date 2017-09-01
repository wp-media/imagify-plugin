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


// Imagify light modal =============================================================================
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var imagifyOpenModal = function( $theLink ) {
		var theTarget = $theLink.data( 'target' ) || $theLink.attr( 'href' );

		$( theTarget ).css( 'display', 'flex' ).hide().fadeIn( 400 ).attr( 'aria-hidden', 'false' ).attr( 'tabindex', '0' ).focus().removeAttr( 'tabindex' ).addClass( 'modal-is-open' );
		$( 'body' ).addClass( 'imagify-modal-is-open' );
	};

	// Accessibility.
	$( '.imagify-modal' ).attr( 'aria-hidden', 'true' );

	$( d )
		// On click on modal trigger, open modal.
		.on( 'click.imagify', '.imagify-modal-trigger', function( e ) {
			e.preventDefault();
			imagifyOpenModal( $( this ) );
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
