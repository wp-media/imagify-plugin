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

	w.imagify.modal = {

		working: [],

		/*
		 * Init.
		 */
		init: function () {
			var $document = $( d ),
				$processing;

			// Update the chart in the media modal when a media is selected, and the ones already printed.
			$( w ).on( 'canvasprinted.imagify', this.updateChart ).trigger( 'canvasprinted.imagify' );

			// Toggle slide in custom column.
			$( '.imagify-datas-details' ).hide();

			$document.on( 'click', '.imagify-datas-more-action a', this.toggleSlide );

			// Optimize, restore, etc.
			$document.on( 'click', '.button-imagify-restore, .button-imagify-optimize, .button-imagify-manual-reoptimize, .button-imagify-optimize-missing-sizes, .button-imagify-generate-webp', this.processOptimization );

			$document.on( 'imagifybeat-send', this.addToImagifybeat );
			$document.on( 'imagifybeat-tick', this.processImagifybeat );

			// Some items may be processed in background on page load.
			$processing = $( '.imagify-data-actions-container .button-imagify-processing' );

			if ( $processing.length ) {
				// Some media are already being processed.
				// Lock the items, so we can check their status with Imagifybeat.
				$processing.closest( '.imagify-data-actions-container' ).each( function() {
					var $this   = $( this ),
						id      = w.imagify.modal.sanitizeId( $this.data( 'id' ) ),
						context = w.imagify.modal.sanitizeContext( $this.data( 'context' ) );

					w.imagify.modal.lockItem( context, id );
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

		// Optimization ============================================================================

		/**
		 * Process to one of these actions: restore, optimize, re-optimize, or optimize missing sizes.
		 *
		 * @param {object} e Event.
		 */
		processOptimization: function( e ) {
			var $obj            = $( this ),
				$container      = $obj.parents( '.imagify-data-actions-container' ),
				id              = w.imagify.modal.sanitizeId( $container.data( 'id' ) ),
				context         = w.imagify.modal.sanitizeContext( $container.data( 'context' ) ),
				href, processingTemplate;

			e.preventDefault();

			if ( w.imagify.modal.isItemLocked( context, id ) ) {
				return;
			}

			w.imagify.modal.lockItem( context, id );

			href               = $obj.attr( 'href' );
			processingTemplate = w.imagify.template( 'imagify-button-processing' );

			$container.html( processingTemplate( {
				label: $obj.data( 'processing-label' )
			} ) );

			$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
				.done( function( response ) {
					if ( response.data && response.data.html ) {
						// The work is done.
						w.imagify.modal.displayProcessResult( context, id, response.data.html );
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
			var $containers = $( '.imagify-data-actions-container' );

			if ( ! $containers.length ) {
				return;
			}

			data[ w.imagifyModal.imagifybeatID ] = {};

			$containers.each( function() {
				var $this   = $( this ),
					id      = w.imagify.modal.sanitizeId( $this.data( 'id' ) ),
					context = w.imagify.modal.sanitizeContext( $this.data( 'context' ) ),
					locked  = w.imagify.modal.isItemLocked( context, id ) ? 1 : 0;

				data[ w.imagifyModal.imagifybeatID ][ context ] = data[ w.imagifyModal.imagifybeatID ][ context ] || {};
				data[ w.imagifyModal.imagifybeatID ][ context ][ '_' + id ] = locked;
			} );
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processImagifybeat: function ( e, data ) {
			if ( typeof data[ w.imagifyModal.imagifybeatID ] === 'undefined' ) {
				return;
			}

			$.each( data[ w.imagifyModal.imagifybeatID ], function( contextId, htmlContent ) {
				var context, id;

				context = $.trim( contextId ).match( /^(.+)_(\d+)$/ );

				if ( ! context ) {
					return;
				}

				id      = w.imagify.modal.sanitizeId( context[2] );
				context = w.imagify.modal.sanitizeContext( context[1] );

				w.imagify.modal.displayProcessResult( context, id, htmlContent );
			} );
		},

		// DOM manipulation tools ==================================================================

		/**
		 * Display the process result.
		 *
		 * @param {string} context     The media context.
		 * @param {int}    id          The media ID.
		 * @param {string} htmlContent The HTML to insert.
		 */
		displayProcessResult: function( context, id, htmlContent ) {
			var $containers = w.imagify.modal.getContainers( context, id );

			$containers.html( htmlContent );
			w.imagify.modal.unlockItem( context, id );

			if ( ! w.imagify.modal.working.length ) {
				// Work is done.
				// Open the last container being processed.
				w.imagify.modal.openSlide( $containers );
				// Reset Imagifybeat interval.
				w.imagify.beat.resetInterval();
			}
		},

		/**
		 * Open a slide rapidly.
		 *
		 * @param {object} $containers A jQuery collection.
		 */
		openSlide: function( $containers ) {
			$containers.each( function() {
				var $container = $( this ),
					text       = $container.find( '.imagify-datas-more-action a' ).data( 'close' );

				$container.find( '.imagify-datas-more-action a' ).addClass( 'is-open' ).find( '.the-text' ).text( text );
				$container.find( '.imagify-datas-details' ).show().addClass( 'is-open' );
			} );
		},

		/**
		 * Toggle slide in custom column.
		 *
		 * @param {object} e Event.
		 */
		toggleSlide: function( e ) {
			var $this = $( this );

			e.preventDefault();

			if ( $this.hasClass( 'is-open' ) ) {
				$( $this.attr( 'href' ) ).slideUp( 300 ).removeClass( 'is-open' );
				$this.removeClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'open' ) );
			} else {
				$( $this.attr( 'href' ) ).slideDown( 300 ).addClass( 'is-open' );
				$this.addClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'close' ) );
			}
		},

		/**
		 * Get all containers matching the given context and id.
		 *
		 * @param  {string} context The media context.
		 * @param  {int}    id      The media ID.
		 * @return {object}         A jQuery collection.
		 */
		getContainers: function( context, id ) {
			return $( '.imagify-data-actions-container[data-id="' + id + '"][data-context="' + context + '"]' );
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

	w.imagify.modal.init();

} )(jQuery, document, window);
