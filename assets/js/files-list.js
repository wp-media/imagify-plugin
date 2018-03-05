/**
 * Mini chart.
 *
 * @param {element} canvas The canvas element.
 */
window.imagify.drawMeAChart = function( canvas ) {
	canvas.each( function() {
		var value = parseInt( jQuery( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() );

		new imagify.Chart( this, { // eslint-disable-line no-new
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

	/**
	 * Update the charts.
	 */
	$( w ).on( 'canvasprinted.imagify', function( e, selector ) {
		var $canvas;

		selector = selector || '.imagify-consumption-chart';
		$canvas  = $( selector );

		w.imagify.drawMeAChart( $canvas );
	} )
		.trigger( 'canvasprinted.imagify' );

} )(jQuery, document, window);


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * Handle bulk actions.
	 */
	var bulkActions = '<option value="imagify-bulk-optimize">' + imagifyFiles.labels.bulkActionsOptimize + '</option>';

	if ( imagifyFiles.backupOption || $( '.file-has-backup' ).length ) {
		// If the backup option is enabled, or if we have items that can be restored.
		bulkActions += '<option value="imagify-bulk-restore">' + imagifyFiles.labels.bulkActionsRestore + '</option>';
	}

	$( '.bulkactions select[name="action"] option:first-child, .bulkactions select[name="action2"] option:first-child' ).after( bulkActions );

	/**
	 * Process one of these actions: bulk restore, bulk optimize, or bulk refresh-status.
	 */
	$( '#doaction, #doaction2' )
		.on( 'click.imagify', function( e ) {
			var value = $( this ).prev( 'select' ).val(),
				action, ids;

			if ( 'imagify-bulk-optimize' !== value && 'imagify-bulk-restore' !== value && 'imagify-bulk-refresh-status' !== value ) {
				return;
			}

			e.preventDefault();

			action = value.replace( 'bulk-', '' );
			ids    = $( 'input[name="bulk_select[]"]:checked' ).map( function() {
				return this.value;
			} ).get();

			ids.forEach( function( id, index ) {
				var $button = $( '#' + action + '-' + id );

				if ( ! $button.length ) {
					$button.closest( 'tr' ).find( '#cb-select-' + id ).prop( 'checked', false );
					return;
				}

				setTimeout( function() {
					$button.trigger( 'click.imagify' );
				}, index * 500 );
			} );
		} );

	/**
	 * Process one of these actions: optimize, re-optimize, restore, or refresh-status.
	 */
	$( d ).on( 'click.imagify', '.button-imagify-optimize, .button-imagify-reoptimize, .button-imagify-restore, .button-imagify-refresh-status', function( e ) {
		var $button = $( this ),
			$row    = $button.closest( 'tr' ),
			$parent, href;

		e.preventDefault();

		if ( $row.hasClass( 'working' ) ) {
			return;
		}

		$row.addClass( 'working' );
		$parent = $button.closest( '.column-actions, .column-status' );
		href    = $button.attr( 'href' );

		$parent.html( '<div class="button"><span class="imagify-spinner"></span>' + $button.data( 'waiting-label' ) + '</div>' );

		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
			.done( function( r ) {
				if ( ! r.success ) {
					if ( r.data.row ) {
						$row.html( '<td class="colspanchange" colspan="' + $row.children().length + '">' + r.data.row + '</td>' );
					} else {
						$parent.html( r.data );
					}
					return;
				}

				$.each( r.data, function( k, v ) {
					$row.children( '.column-' + k ).html( v );
				} );
			} )
			.always( function() {
				$row.removeClass( 'working' ).find( '.check-column [type="checkbox"]' ).prop( 'checked', false );
			} );
	} );

} )(jQuery, document, window);


(function(w) { // eslint-disable-line no-shadow, no-shadow-restricted-names

	/**
	 * requestAnimationFrame polyfill by Erik Möller.
	 * Fixes from Paul Irish and Tino Zijdel.
	 * MIT license - http://paulirish.com/2011/requestanimationframe-for-smart-animating/ - http://my.opera.com/emoller/blog/2011/12/20/requestanimationframe-for-smart-er-animating.
	 */
	var lastTime = 0,
		vendors  = ['ms', 'moz', 'webkit', 'o'];

	for ( var x = 0; x < vendors.length && ! w.requestAnimationFrame; ++x ) {
		w.requestAnimationFrame = w[vendors[x] + 'RequestAnimationFrame'];
		w.cancelAnimationFrame  = w[vendors[x] + 'CancelAnimationFrame'] || w[vendors[x] + 'CancelRequestAnimationFrame'];
	}

	if ( ! w.requestAnimationFrame ) {
		w.requestAnimationFrame = function( callback ) {
			var currTime   = new Date().getTime(),
				timeToCall = Math.max( 0, 16 - ( currTime - lastTime ) ),
				id         = setTimeout( function() {
					callback( currTime + timeToCall );
				}, timeToCall );

			lastTime = currTime + timeToCall;
			return id;
		};
	}

	if ( ! w.cancelAnimationFrame ) {
		w.cancelAnimationFrame = function( id ) {
			clearTimeout( id );
		};
	}

})(window);


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * LazyLoad images in the list.
	 */
	var lazyImages = $( '#imagify-files-list-form' ).find( '[data-lazy-src]' ),
		lazyTimer;

	function lazyLoadThumbnails() {
		w.cancelAnimationFrame( lazyTimer );
		lazyTimer = w.requestAnimationFrame( lazyLoadThumbnailsCallback ); // eslint-disable-line no-use-before-define
	}

	function lazyLoadThumbnailsCallback() {
		var $w = $( w ),
			winScroll = $w.scrollTop(),
			winHeight = $w.outerHeight();

		$.each( lazyImages, function() {
			var $image                  = $( this ),
				imgTop                  = $image.offset().top,
				imgBottom               = imgTop + $image.outerHeight(),
				screenTopThresholded    = winScroll - 150,
				screenBottomThresholded = winScroll + winHeight + 150,
				src;

			lazyImages = lazyImages.not( $image );

			if ( ! lazyImages.length ) {
				$w.off( 'scroll resize orientationchange', lazyLoadThumbnails );
			}

			/**
			 * Hidden images that are above the fold and below the top, are reported as:
			 *  - offset: window scroll,
			 *  - height: 0,
			 * (at least in Firefox).
			 *  That's why I use <= and >=.
			 *
			 * 150 is the threshold.
			 */
			if ( imgBottom >= screenTopThresholded && imgTop <= screenBottomThresholded ) {
				src = $image.attr( 'data-lazy-src' );

				if ( undefined !== src && src ) {
					$image.attr( 'src', src ).removeAttr( 'data-lazy-src' );
				}

				$image.next( 'noscript' ).remove();
			}
		} );
	}

	if ( lazyImages.length ) {
		$( w ).on( 'scroll resize orientationchange', lazyLoadThumbnails );
		lazyLoadThumbnailsCallback();
	}

} )(jQuery, document, window);
