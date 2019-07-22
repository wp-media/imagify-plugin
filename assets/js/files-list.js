/**
 * Mini chart.
 *
 * @param {element} canvas The canvas element.
 */
window.imagify.drawMeAChart = function( canvas ) {
	canvas.each( function() {
		var value = parseInt( jQuery( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text(), 10 );

		new window.imagify.Chart( this, { // eslint-disable-line no-new
			type: 'doughnut',
			data: {
				datasets: [ {
					data:            [ value, 100 - value ],
					backgroundColor: [ '#00B3D3', '#D8D8D8' ],
					borderColor:     '#fff',
					borderWidth:     1
				} ]
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

	w.imagify.filesList = {

		working: [],

		/*
		 * Init.
		 */
		init: function () {
			var $document = $( d ),
				$processing;

			// Update the chart in the media modal when a media is selected, and the ones already printed.
			$( w ).on( 'canvasprinted.imagify', this.updateChart ).trigger( 'canvasprinted.imagify' );

			// Handle bulk actions.
			this.insertBulkActionTags();

			$( '#doaction, #doaction2' ).on( 'click.imagify', this.processBulkAction );

			// Optimize, restore, etc.
			$document.on( 'click.imagify', '.button-imagify-optimize, .button-imagify-manual-reoptimize, .button-imagify-generate-webp, .button-imagify-delete-webp, .button-imagify-restore, .button-imagify-refresh-status', this.processOptimization );

			$document.on( 'imagifybeat-send', this.addToImagifybeat );
			$document.on( 'imagifybeat-tick', this.processImagifybeat );

			// Some items may be processed in background on page load.
			$processing = $( '.wp-list-table.imagify-files .button-imagify-processing' );

			if ( $processing.length ) {
				// Some media are already being processed.
				// Lock the items, so we can check their status with Imagifybeat.
				$processing.closest( 'tr' ).find( '.check-column [name="bulk_select[]"]' ).each( function() {
					var id = w.imagify.filesList.sanitizeId( this.value );

					w.imagify.filesList.lockItem( w.imagifyFiles.context, id );
				} );

				// Fasten Imagifybeat.
				w.imagify.beat.interval( 15 );
			}
		},

		// Charts ==================================================================================

		/**
		 * Update the chart in the media modal when a media is selected, and the ones already printed.
		 *
		 * @param {object} e        Event.
		 * @param {string} selector A CSS selector.
		 */
		updateChart: function( e, selector ) {
			var $canvas;

			selector = selector || '.imagify-consumption-chart';
			$canvas  = $( selector );

			w.imagify.drawMeAChart( $canvas );

			$canvas.closest( '.imagify-datas-list' ).siblings( '.imagify-datas-details' ).hide();
		},

		// Bulk optimization =======================================================================

		/**
		 * Insert the bulk actions in the <select> tag.
		 */
		insertBulkActionTags: function() {
			var bulkActions = '<option value="imagify-bulk-optimize">' + w.imagifyFiles.labels.bulkActionsOptimize + '</option>';

			if ( w.imagifyFiles.backupOption || $( '.file-has-backup' ).length ) {
				// If the backup option is enabled, or if we have items that can be restored.
				bulkActions += '<option value="imagify-bulk-restore">' + w.imagifyFiles.labels.bulkActionsRestore + '</option>';
			}

			$( '.bulkactions select[name="action"] option:first-child, .bulkactions select[name="action2"] option:first-child' ).after( bulkActions );
		},

		/**
		 * Process one of these actions: bulk restore, bulk optimize, or bulk refresh-status.
		 *
		 * @param {object} e Event.
		 */
		processBulkAction: function( e ) {
			var value = $( this ).prev( 'select' ).val(),
				action;

			if ( 'imagify-bulk-optimize' !== value && 'imagify-bulk-restore' !== value && 'imagify-bulk-refresh-status' !== value ) {
				return;
			}

			e.preventDefault();

			action = value.replace( 'imagify-bulk-', '' );

			$( 'input[name="bulk_select[]"]:checked' ).closest( 'tr' ).find( '.button-imagify-' + action ).each( function ( index, el ) {
				setTimeout( function() {
					$( el ).trigger( 'click.imagify' );
				}, index * 500 );
			} );
		},

		// Optimization ============================================================================

		/**
		 * Process one of these actions: optimize, re-optimize, restore, or refresh-status.
		 *
		 * @param {object} e Event.
		 */
		processOptimization: function( e ) {
			var $button   = $( this ),
				$row      = $button.closest( 'tr' ),
				$checkbox = $row.find( '.check-column [type="checkbox"]' ),
				id        = imagify.filesList.sanitizeId( $checkbox.val() ),
				context   = w.imagifyFiles.context,
				$parent, href, processingTemplate;

			e.preventDefault();

			if ( imagify.filesList.isItemLocked( context, id ) ) {
				return;
			}

			imagify.filesList.lockItem( context, id );

			href               = $button.attr( 'href' );
			processingTemplate = w.imagify.template( 'imagify-button-processing' );
			$parent            = $button.closest( '.column-actions, .column-status' );

			$parent.html( processingTemplate( {
				label: $button.data( 'processing-label' )
			} ) );

			$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
				.done( function( r ) {
					if ( ! r.success ) {
						if ( r.data && r.data.row ) {
							$row.html( '<td class="colspanchange" colspan="' + $row.children().length + '">' + r.data.row + '</td>' );
						} else {
							$parent.html( r.data );
						}

						$row.find( '.check-column [type="checkbox"]' ).prop( 'checked', false );

						imagify.filesList.unlockItem( context, id );
						return;
					}

					if ( r.data && r.data.columns ) {
						// The work is done.
						w.imagify.filesList.displayProcessResult( context, id, r.data.columns );
					} else {
						// Still processing in background: we're waiting for the result by poking Imagifybeat.
						// Set the Imagifybeat interval to 15 seconds.
						w.imagify.beat.interval( 15 );
					}
				} );
		},

		// Imagifybeat =============================================================================

		/**
		 * Send the media IDs and their status to Imagifybeat.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addToImagifybeat: function ( e, data ) {
			var $boxes = $( '.wp-list-table.imagify-files .check-column [name="bulk_select[]"]' );

			if ( ! $boxes.length ) {
				return;
			}

			data[ w.imagifyFiles.imagifybeatID ] = {};

			$boxes.each( function() {
				var id      = w.imagify.filesList.sanitizeId( this.value ),
					context = w.imagifyFiles.context,
					locked  = w.imagify.filesList.isItemLocked( context, id ) ? 1 : 0;

				data[ w.imagifyFiles.imagifybeatID ][ context ] = data[ w.imagifyFiles.imagifybeatID ][ context ] || {};
				data[ w.imagifyFiles.imagifybeatID ][ context ][ '_' + id ] = locked;
			} );
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processImagifybeat: function ( e, data ) {
			if ( typeof data[ w.imagifyFiles.imagifybeatID ] === 'undefined' ) {
				return;
			}

			$.each( data[ w.imagifyFiles.imagifybeatID ], function( contextId, columns ) {
				var context, id;

				context = $.trim( contextId ).match( /^(.+)_(\d+)$/ );

				if ( ! context ) {
					return;
				}

				id      = w.imagify.filesList.sanitizeId( context[2] );
				context = w.imagify.filesList.sanitizeContext( context[1] );

				if ( context !== w.imagifyFiles.context ) {
					return;
				}

				w.imagify.filesList.displayProcessResult( context, id, columns );
			} );
		},

		// DOM manipulation tools ==================================================================

		/**
		 * Display a successful process result.
		 *
		 * @param {string} context The media context.
		 * @param {int}    id      The media ID.
		 * @param {string} columns A list of HTML, keyed by column name.
		 */
		displayProcessResult: function( context, id, columns ) {
			var $row = w.imagify.filesList.getContainers( id );

			$.each( columns, function( k, v ) {
				$row.children( '.column-' + k ).html( v );
			} );

			$row.find( '.check-column [type="checkbox"]' ).prop( 'checked', false );

			w.imagify.filesList.unlockItem( context, id );

			if ( ! w.imagify.filesList.working.length ) {
				// Work is done.
				// Reset Imagifybeat interval.
				w.imagify.beat.resetInterval();
			}
		},

		/**
		 * Get all containers matching the given id.
		 *
		 * @param  {int} id The media ID.
		 * @return {object} A jQuery collection.
		 */
		getContainers: function( id ) {
			return $( '.wp-list-table.imagify-files .check-column [name="bulk_select[]"][value="' + id + '"]' ).closest( 'tr' );
		},

		// Sanitization ============================================================================

		/**
		 * Sanitize a media ID.
		 *
		 * @param  {int|string} id A media ID.
		 * @return {int}
		 */
		sanitizeId: function( id ) {
			return parseInt( id, 10 );
		},

		/**
		 * Sanitize a media context.
		 *
		 * @param  {string} context A media context.
		 * @return {string}
		 */
		sanitizeContext: function( context ) {
			context = context.replace( '/[^a-z0-9_-]/gi', '' ).toLowerCase();
			return context ? context : 'wp';
		},

		// Locks ===================================================================================

		/**
		 * Lock an item.
		 *
		 * @param {string} context The media context.
		 * @param {int}    id      The media ID.
		 */
		lockItem: function( context, id ) {
			if ( ! this.isItemLocked( context, id ) ) {
				this.working.push( context + '_' + id );
			}
		},

		/**
		 * Unlock an item.
		 *
		 * @param {string} context The media context.
		 * @param {int}    id      The media ID.
		 */
		unlockItem: function( context, id ) {
			var name = context + '_' + id,
				i    = _.indexOf( this.working, name );

			if ( i > -1 ) {
				this.working.splice( i, 1 );
			}
		},

		/**
		 * Tell if an item is locked.
		 *
		 * @param  {string} context The media context.
		 * @param  {int}    id      The media ID.
		 * @return {bool}
		 */
		isItemLocked: function( context, id ) {
			return _.indexOf( this.working, context + '_' + id ) > -1;
		}
	};

	w.imagify.filesList.init();

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
