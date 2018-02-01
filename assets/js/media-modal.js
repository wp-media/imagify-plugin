/**
 * Mini chart.
 *
 * @param {element} canvas The canvas element.
 */
window.imagify.drawMeAChart = function( canvas ) {
	canvas.each( function() {
		var value = parseInt( jQuery( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() );

		new window.imagify.Chart( this, { // eslint-disable-line no-new
			type: 'doughnut',
			data: {
				datasets: [{
					data:            [ value, 100 - value ],
					backgroundColor: [ '#00B3D3', '#D8D8D8' ],
					borderColor:     '#fff',
					borderWidth:     1
				}]
			},
			options: {
				legend: {
					display: false
				},
				events:    [],
				animation: {
					easing: 'easeOutBounce'
				},
				tooltips: {
					enabled: false
				},
				responsive: false
			}
		} );
	} );
};

(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var working = false;

	/**
	 * Toggle slide in custom column.
	 */
	$( '.imagify-datas-details' ).hide();

	$( d ).on( 'click', '.imagify-datas-more-action a', function( e ) {
		var $this = $( this );

		e.preventDefault();

		if ( $this.hasClass( 'is-open' ) ) {
			$( $this.attr( 'href' ) ).slideUp( 300 ).removeClass( 'is-open' );
			$this.removeClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'open' ) );
		} else {
			$( $this.attr( 'href' ) ).slideDown( 300 ).addClass( 'is-open' );
			$this.addClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'close' ) );
		}
	} );

	/**
	 * Process to one of these actions: restore, optimize, re-optimize, or optimize missing sizes.
	 */
	$( d ).on( 'click', '.button-imagify-restore, .button-imagify-manual-upload, .button-imagify-manual-override-upload, .button-imagify-optimize-missing-sizes', function( e ) {
		var $obj    = $( this ),
			$parent = $obj.parents( '.column-imagify_optimized_file, .compat-field-imagify .field' ),
			href    = $obj.attr( 'href' );

		e.preventDefault();

		if ( ! $parent.length ) {
			$parent = $obj.closest( '.column' );
		}

		$parent.html( '<div class="button"><span class="imagify-spinner"></span>' + $obj.data( 'waiting-label' ) + '</div>' );

		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
			.done( function( response ) {
				working = true;
				$parent.html( response.data );
				$parent.find( '.imagify-datas-more-action a' ).addClass( 'is-open' ).find( '.the-text' ).text( $parent.find( '.imagify-datas-more-action a' ).data( 'close' ) );
				$parent.find( '.imagify-datas-details' ).addClass( 'is-open' );

				w.imagify.drawMeAChart( $parent.find( '.imagify-consumption-chart' ) );
				working = false;
			} );
	} );

	/**
	 * Update the chart in the media modal when a media is selected, and the ones already printed.
	 */
	$( w ).on( 'canvasprinted.imagify', function( e, selector ) {
		var $canvas;

		selector = selector || '.imagify-consumption-chart';
		$canvas  = $( selector );

		w.imagify.drawMeAChart( $canvas );

		if ( ! working ) {
			$canvas.closest( '.imagify-datas-list' ).siblings( '.imagify-datas-details' ).hide();
		}
	} )
		.trigger( 'canvasprinted.imagify' );

} )(jQuery, document, window);
