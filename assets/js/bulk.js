window.imagify = window.imagify || {};

(function( $, undefined ) { // eslint-disable-line no-shadow, no-shadow-restricted-names

	var jqPropHookChecked = $.propHooks.checked;

	// Force `.prop()` to trigger a `change` event.
	$.propHooks.checked = {
		set: function( elem, value, name ) {
			var ret;

			if ( undefined === jqPropHookChecked ) {
				ret = ( elem[ name ] = value );
			} else {
				ret = jqPropHookChecked( elem, value, name );
			}

			$( elem ).trigger( 'change.imagify' );

			return ret;
		}
	};

	// Custom jQuery functions =====================================================================
	/**
	 * Hide element(s).
	 *
	 * @param  {int}      duration A duration in ms.
	 * @param  {function} callback A callback to execute once the element is hidden.
	 * @return {element}  The jQuery element(s).
	 */
	$.fn.imagifyHide = function( duration, callback ) {
		if ( duration && duration > 0 ) {
			this.hide( duration, function() {
				$( this ).addClass( 'hidden' ).css( 'display', '' );

				if ( undefined !== callback ) {
					callback();
				}
			} );
		} else {
			this.addClass( 'hidden' );

			if ( undefined !== callback ) {
				callback();
			}
		}

		return this.attr( 'aria-hidden', 'true' );
	};

	/**
	 * Show element(s).
	 *
	 * @param  {int}      duration A duration in ms.
	 * @param  {function} callback A callback to execute before starting to display the element.
	 * @return {element} The jQuery element(s).
	 */
	$.fn.imagifyShow = function( duration, callback ) {
		if ( undefined !== callback ) {
			callback();
		}

		if ( duration && duration > 0 ) {
			this.show( duration, function() {
				$( this ).removeClass( 'hidden' ).css( 'display', '' );
			} );
		} else {
			this.removeClass( 'hidden' );
		}

		return this.attr( 'aria-hidden', 'false' );
	};

}( jQuery ));


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	w.imagify.bulk = {

		// Properties ==============================================================================
		charts: {
			overview: {
				canvas: false,
				donut:  false,
				data:   {
					// Order: unoptimized, optimized, error.
					labels: [
						imagifyBulk.labels.overviewChartLabels.unoptimized,
						imagifyBulk.labels.overviewChartLabels.optimized,
						imagifyBulk.labels.overviewChartLabels.error
					],
					datasets: [ {
						data:            [],
						backgroundColor: [ '#10121A', '#46B1CE', '#C51162' ],
						borderWidth:     0
					} ]
				}
			},
			files: {
				donuts: {}
			},
			share: {
				canvas: false,
				donut:  false
			}
		},
		/**
		 * Folder types in queue.
		 * An array of objects: {
		 *     @type {string} groupID The group ID, like 'library'.
		 *     @type {string} context The context, like 'wp'.
		 *     @type {int}    level   The optimization level: 0, 1, or 2.
		 * }
		 */
		folderTypesQueue:     [],
		/**
		 * Status of each folder type. Type IDs are used as keys.
		 * Each object contains: {
		 *     @type {bool}   isError Tell if the status is considered as an error.
		 *     @type {string} id      ID of the status, like 'waiting', 'fetching', or 'optimizing'.
		 * }
		 */
		status:               {},
		// Tell if the message displayed when retrieving the image IDs has been shown once.
		displayedWaitMessage: false,
		// Tell how many rows are available.
		hasMultipleRows:      true,
		// The action to perform (like 'optimize').
		imagifyAction:        '',
		// Set to true to stop the whole thing.
		processIsStopped:     false,
		// List of medias being processed.
		processingMedia:      [],
		// Global stats.
		globalGain:           0,
		globalOriginalSize:   0,
		globalOptimizedSize:  0,
		/**
		 * Folder types used in the page.
		 *
		 * @var {object} {
		 *     An object of objects. The keys are like: {groupID|context}.
		 *
		 *     @type {string} groupID     The group ID.
		 *     @type {string} context     The context.
		 *     @type {int}    level       The optimization.
		 *     @type {string} optimizeURL The URL to ping to optimize a file.
		 *     @type {array}  mediaIDs    A list of file IDs.
		 *     @type {object} files       File IDs as keys (prefixed by an underscore), File URLs as values.
		 * }
		 */
		folderTypesData:      {},
		// Default thumbnail.
		defaultThumb:         'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACEAAAAhBAMAAAClyt9cAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABtQTFRFR3BMjo6Oh4eHqqqq4uLigoKC+fn5fn5+fHx8SBBv5wAAAAF0Uk5TAEDm2GYAAABWSURBVCjPY2BgEBIEAyERBhgQUoKAIAc0EXXjEDQRRTNzBzRdBokhaGoM2CQc0NQwJJegqWFgM3VAUSNsbGwugCKiqBQUpIAiAgICoyIDKyIIB0JAEQA54jRBweNV0AAAAABJRU5ErkJggg==',

		// Methods =================================================================================

		/*
		 * Init.
		 */
		init: function () {
			var $document = $( d );

			// Overview chart.
			this.drawOverviewChart();

			this.hasMultipleRows = $( '.imagify-bulk-table [name="group[]"]' ).length > 1;

			// Selectors (like the level selectors).
			$( '.imagify-selector-button' )
				.on( 'click.imagify', this.openSelectorFromButton );

			$( '.imagify-selector-list input' )
				.on( 'change.imagify init.imagify', this.syncSelectorFromRadio )
				.filter( ':checked' )
				.trigger( 'init.imagify' );

			$document
				.on( 'keypress.imagify click.imagify', this.closeSelectors );

			// Other buttons/UI.
			$( '.imagify-bulk-table [name="group[]"]' )
				.on( 'change.imagify init.imagify', this.toggleOptimizationButton )
				.trigger( 'init.imagify' );

			$( '.imagify-show-table-details' )
				.on( 'click.imagify open.imagify close.imagify', this.toggleOptimizationDetails );

			$( '#imagify-bulk-action' )
				.on( 'click.imagify', this.maybeLaunchAllProcesses );

			$( '.imagify-share-networks a' )
				.on( 'click.imagify', this.share );

			// Optimization events.
			$( w )
				.on( 'processQueue.imagify', this.processQueue )
				.on( 'optimizeFiles.imagify', this.optimizeFiles )
				.on( 'queueEmpty.imagify', this.queueEmpty );

			if ( imagifyBulk.ajaxActions.getStats && $( '.imagify-bulk-table [data-group-id="library"][data-context="wp"]' ).length ) {
				// On large WP library, don't request stats periodically, only when everything is done.
				imagifyBulk.imagifybeatIDs.stats = false;
			}

			if ( imagifyBulk.imagifybeatIDs.stats ) {
				// Imagifybeat for stats.
				$document
					.on( 'imagifybeat-send', this.addStatsImagifybeat )
					.on( 'imagifybeat-tick', this.processStatsImagifybeat );
			}

			// Imagifybeat for optimization queue.
			$document
				.on( 'imagifybeat-send', this.addQueueImagifybeat )
				.on( 'imagifybeat-tick', this.processQueueImagifybeat );

			// Imagifybeat for requirements.
			$document
				.on( 'imagifybeat-send', this.addRequirementsImagifybeat )
				.on( 'imagifybeat-tick', this.processRequirementsImagifybeat );
		},

		/*
		 * Get the URL used for ajax requests.
		 *
		 * @param  {string} action An ajax action, or part of it.
		 * @param  {object} item   The current item.
		 * @return {string}
		 */
		getAjaxUrl: function ( action, item ) {
			var url = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&action=' + imagifyBulk.ajaxActions[ action ];

			if ( item && item.context ) {
				url += '&context=' + item.context;
			}

			if ( 'getMediaIds' === action || 'bulkProcess' === action ) {
				url += '&imagify_action=' + w.imagify.bulk.imagifyAction;
			}

			return url;
		},

		/**
		 * Get folder types used in the page.
		 *
		 * @see    this.folderTypesData
		 * @return {object}
		 */
		getFolderTypes: function () {
			if ( ! $.isEmptyObject( w.imagify.bulk.folderTypesData ) ) {
				return w.imagify.bulk.folderTypesData;
			}

			$( '.imagify-row-folder-type' ).each( function() {
				var $this = $( this ),
					data  = {
						groupID: $this.data( 'group-id' ),
						context: $this.data( 'context' )
					},
					key   = data.groupID + '|' + data.context;

				w.imagify.bulk.folderTypesData[ key ] = data;
			} );

			return w.imagify.bulk.folderTypesData;
		},

		/*
		 * Get the message displayed to the user when (s)he leaves the page.
		 *
		 * @return {string}
		 */
		getConfirmMessage: function () {
			return imagifyBulk.labels.processing;
		},

		/*
		 * Close the given optimization level selector.
		 *
		 * @param {object} $lists A jQuery object.
		 * @param {int}    timer  Timer in ms to close the selector.
		 */
		closeLevelSelector: function ( $lists, timer ) {
			if ( ! $lists || ! $lists.length ) {
				return;
			}

			if ( undefined !== timer && timer > 0 ) {
				w.setTimeout( function() {
					w.imagify.bulk.closeLevelSelector( $lists );
				}, timer );
				return;
			}

			$lists.attr( 'aria-hidden', 'true' );
		},

		/*
		 * Stop everything and update the current item status as an error.
		 *
		 * @param {string} errorId An error ID.
		 * @param {object} item    The current item.
		 */
		stopProcess: function ( errorId, item ) {
			this.processIsStopped = true;

			w.imagify.bulk.status[ item.groupID ] = {
				isError: true,
				id:      errorId
			};

			$( w ).trigger( 'queueEmpty.imagify' );
		},

		/*
		 * Tell if we have a blocking error. Can also display an error message in a swal.
		 *
		 * @param  {bool} displayErrorMessage False to not display any error message.
		 * @return {bool}
		 */
		hasBlockingError: function ( displayErrorMessage ) {
			displayErrorMessage = undefined !== displayErrorMessage && displayErrorMessage;

			if ( imagifyBulk.curlMissing ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.curlMissing
					} );
				}
				return true;
			}

			if ( imagifyBulk.editorMissing ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.editorMissing
					} );
				}
				return true;
			}

			if ( imagifyBulk.extHttpBlocked ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.extHttpBlocked
					} );
				}
				return true;
			}

			if ( imagifyBulk.apiDown ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.apiDown
					} );
				}
				return true;
			}

			if ( ! imagifyBulk.keyIsValid ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						title: imagifyBulk.labels.invalidAPIKeyTitle,
						type:  'info'
					} );
				}
				return true;
			}

			if ( imagifyBulk.isOverQuota ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						title:             imagifyBulk.labels.overQuotaTitle,
						html:              $( '#tmpl-imagify-overquota-alert' ).html(),
						type:              'info',
						customClass:       'imagify-swal-has-subtitle imagify-swal-error-header',
						showConfirmButton: false
					} );
				}
				return true;
			}

			return false;
		},

		/*
		 * Display an error message in a modal.
		 *
		 * @param {string} title The modal title.
		 * @param {string} text  The modal text.
		 * @param {object} args  Other less common args.
		 */
		displayError: function ( title, text, args ) {
			var def = {
				title:             '',
				html:              '',
				type:              'error',
				customClass:       '',
				width:             620,
				padding:           0,
				showCloseButton:   true,
				showConfirmButton: true
			};

			if ( $.isPlainObject( title ) ) {
				args = $.extend( {}, def, title );
			} else {
				args = args || {};
				args = $.extend( {}, def, {
					title: title || '',
					html:  text  || ''
				}, args );
			}

			args.title        = args.title || imagifyBulk.labels.error;
			args.customClass += ' imagify-sweet-alert';

			swal( args ).catch( swal.noop );
		},

		/*
		 * Display an error message in a file row.
		 *
		 * @param  {function} $row The row template.
		 * @param  {string}   text The error text.
		 * @return {element}       The row jQuery element.
		 */
		displayErrorInRow: function ( $row, text ) {
			var $toReplace, colspan;

			$row       = $( $row() );
			$toReplace = $row.find( '.imagify-cell-status ~ td' );
			colspan    = $toReplace.length;
			text       = text || '';

			$toReplace.remove();
			$row.find( '.imagify-cell-status' ).after( '<td colspan="' + colspan + '">' + text + '</td>' );

			return $row;
		},

		/*
		 * Display one of the 3 "folder" rows.
		 *
		 * @param {string}  state One of the 3 states: 'resting' (it's the "normal" row), 'waiting' (waiting for other optimizations to finish), and 'working'.
		 * @param {element} $row  jQuery element of the "normal" row.
		 */
		displayFolderRow: function ( state, $row ) {
			var $newRow, spinnerTemplate, spinnerColor, text;

			if ( 'resting' === state ) {
				$row.next( '.imagify-row-waiting, .imagify-row-working' ).remove();
				$row.imagifyShow();
				return;
			}

			// This part won't work to display multiple $newRow.
			$newRow = $row.next( '.imagify-row-waiting, .imagify-row-working' );

			if ( 'waiting' === state ) {
				spinnerColor = '#d2d3d6';
				text         = imagifyBulk.labels.waitingOtimizationsText;
			} else {
				spinnerColor = '#40b1d0';
				text         = imagifyBulk.labels.imagesOptimizedText.replace( '%s', '<span>0</span>' );
			}

			if ( $newRow.length ) {
				if ( ! $newRow.hasClass( 'imagify-row-' + state ) ) {
					// Should happen when switching from 'waiting' to 'working'.
					$newRow.attr( 'class', 'imagify-row-' + state );
					$newRow.find( '.imagify-cell-checkbox svg' ).attr( 'fill', spinnerColor );
					$newRow.children( '.imagify-cell-count-optimized' ).html( text );
				}

				$row.imagifyHide();
				$newRow.imagifyShow();
				return;
			}

			// Build the new row, based on a clone of the original one.
			$newRow = $row.clone().attr( {
				'class':       'imagify-row-' + state,
				'aria-hidden': 'false'
			} );

			spinnerTemplate = w.imagify.template( 'imagify-spinner' );
			$newRow.children( '.imagify-cell-checkbox' ).html( spinnerTemplate() ).find( 'svg' ).attr( 'fill', spinnerColor );
			$newRow.children( '.imagify-cell-title' ).html( '<span class="imagify-cell-label">' + $newRow.children( '.imagify-cell-title' ).text() + '</span>' );
			$newRow.children( '.imagify-cell-count-optimized' ).html( text );
			$newRow.children( '.imagify-cell-count-errors, .imagify-cell-optimized-size, .imagify-cell-original-size, .imagify-cell-level' ).text( '' );

			$row.imagifyHide().after( $newRow );
		},

		/*
		 * Display the share box.
		 */
		displayShareBox: function () {
			var text2share = imagifyBulk.labels.textToShare,
				percent, gainHuman, originalSizeHuman,
				$complete;

			if ( ! this.globalGain || this.folderTypesQueue.length ) {
				this.globalGain          = 0;
				this.globalOriginalSize  = 0;
				this.globalOptimizedSize = 0;
				return;
			}

			percent           = ( 100 - 100 * ( this.globalOptimizedSize / this.globalOriginalSize ) ).toFixed( 2 );
			gainHuman         = w.imagify.humanSize( this.globalGain, 1 );
			originalSizeHuman = w.imagify.humanSize( this.globalOriginalSize, 1 );

			text2share = text2share.replace( '%1$s', gainHuman );
			text2share = text2share.replace( '%2$s', originalSizeHuman );
			text2share = encodeURIComponent( text2share );

			$complete = $( '.imagify-row-complete' );
			$complete.find( '.imagify-ac-rt-total-gain' ).html( gainHuman );
			$complete.find( '.imagify-ac-rt-total-original' ).html( originalSizeHuman );
			$complete.find( '.imagify-ac-chart' ).attr( 'data-percent', percent );
			$complete.find( '.imagify-sn-twitter' ).attr( 'href', imagifyBulk.labels.twitterShareURL + '&amp;text=' + text2share );

			// Chart.
			this.drawShareChart();

			$complete.addClass( 'done' ).imagifyShow();

			$( 'html, body' ).animate( {
				scrollTop: $complete.offset().top
			}, 200 );

			// Reset the stats.
			this.globalGain          = 0;
			this.globalOriginalSize  = 0;
			this.globalOptimizedSize = 0;
		},

		/**
		 * Print optimization stats.
		 *
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		updateStats: function ( data ) {
			var donutData;

			if ( ! data || ! $.isPlainObject( data ) ) {
				return;
			}

			if ( w.imagify.bulk.charts.overview.donut.data ) {
				donutData = w.imagify.bulk.charts.overview.donut.data.datasets[0].data;

				if ( data.unoptimized_attachments === donutData[0] && data.optimized_attachments === donutData[1] && data.errors_attachments === donutData[2] ) {
					return;
				}
			}

			/**
			 * User account.
			 */
			data.unconsumed_quota = data.unconsumed_quota.toFixed( 1 ); // A mystery where a float rounded on php side is not rounded here anymore. JavaScript is fun, it always surprises you in a manner you didn't expect.
			$( '.imagify-meteo-icon' ).html( data.quota_icon );
			$( '.imagify-unconsumed-percent' ).html( data.unconsumed_quota + '%' );
			$( '.imagify-unconsumed-bar' ).css( 'width', data.unconsumed_quota + '%' ).parent().attr( 'class', data.quota_class );

			/**
			 * Global chart.
			 */
			$( '#imagify-overview-chart-percent' ).html( data.optimized_attachments_percent + '<span>%</span>' );
			$( '.imagify-total-percent' ).html( data.optimized_attachments_percent + '%' );

			w.imagify.bulk.drawOverviewChart( [
				data.unoptimized_attachments,
				data.optimized_attachments,
				data.errors_attachments
			] );

			/**
			 * Stats block.
			 */
			// The total optimized images.
			$( '#imagify-total-optimized-attachments' ).html( data.already_optimized_attachments );

			// The original bar.
			$( '#imagify-original-bar' ).find( '.imagify-barnb' ).html( data.original_human );

			// The optimized bar.
			$( '#imagify-optimized-bar' ).css( 'width', ( 100 - data.optimized_percent ) + '%' ).find( '.imagify-barnb' ).html( data.optimized_human );

			// The Percent data.
			$( '#imagify-total-optimized-attachments-pct' ).html( data.optimized_percent + '%' );
		},

		// Event callbacks =========================================================================

		/*
		 * Selector (like optimization level selector): on button click, open the dropdown and focus the current radio input.
		 * The dropdown must be open or the focus event won't be triggered.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		openSelectorFromButton: function ( e ) {
			var $list = $( '#' + $( this ).attr( 'aria-controls' ) );
			// Stop click event from bubbling: this will allow to close the selector list if anything else id clicked.
			e.stopPropagation();
			// Close other lists.
			$( '.imagify-selector-list' ).not( $list ).attr( 'aria-hidden', 'true' );
			// Open the corresponding list and focus the radio.
			$list.attr( 'aria-hidden', 'false' ).find( ':checked' ).trigger( 'focus.imagify' );
		},

		/*
		 * Selector: on radio change, make the row "current" and update the button text.
		 */
		syncSelectorFromRadio: function () {
			var $row = $( this ).closest( '.imagify-selector-choice' );
			// Update rows attributes.
			$row.addClass( 'imagify-selector-current-value' ).attr( 'aria-current', 'true' ).siblings( '.imagify-selector-choice' ).removeClass( 'imagify-selector-current-value' ).attr( 'aria-current', 'false' );
			// Change the button text.
			$row.closest( '.imagify-selector-list' ).siblings( '.imagify-selector-button' ).find( '.imagify-selector-current-value-info' ).html( $row.find( 'label' ).html() );
		},

		/*
		 * Selector: on Escape or Enter kaystroke, close the dropdown.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		closeSelectors: function ( e ) {
			if ( 'keypress' === e.type && 27 !== e.keyCode && 13 !== e.keyCode ) {
				return;
			}
			w.imagify.bulk.closeLevelSelector( $( '.imagify-selector-list[aria-hidden="false"]' ) );
		},

		/*
		 * Enable or disable the Optimization button depending on the checked checkboxes.
		 * Also, if there is only 1 checkbox in the page, don't allow it to be unchecked.
		 */
		toggleOptimizationButton: function () {
			// Prevent uncheck if there is only one checkbox.
			if ( ! w.imagify.bulk.hasMultipleRows && ! this.checked ) {
				$( this ).prop( 'checked', true );
				return;
			}

			// Enable or disable the Optimization button.
			if ( $( '.imagify-bulk-table [name="group[]"]:checked' ).length ) {
				$( '#imagify-bulk-action' ).removeAttr( 'disabled' );
			} else {
				$( '#imagify-bulk-action' ).attr( 'disabled', 'disabled' );
			}
		},

		/*
		 * Display/Hide optimization details.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		toggleOptimizationDetails: function ( e ) {
			var $button  = $( this ),
				$details = $button.closest( '.imagify-bulk-table' ).find( '.imagify-bulk-table-details' ),
				openDetails;

			if ( 'open' === e.type ) {
				openDetails = true;
			} else if ( 'close' === e.type ) {
				openDetails = false;
			} else {
				openDetails = $details.hasClass( 'hidden' );
			}

			if ( openDetails ) {
				$button.html( $button.data( 'label-hide' ) + '<span class="dashicons dashicons-no-alt"></span>' );
				$details.imagifyShow();
			} else {
				$button.html( $button.data( 'label-show' ) + '<span class="dashicons dashicons-menu"></span>' );
				$details.imagifyHide();
			}
		},

		/*
		 * Maybe display a modal, then launch all processes.
		 */
		maybeLaunchAllProcesses: function () {
			var $infosModal;

			if ( $( this ).attr( 'disabled' ) ) {
				return;
			}

			if ( ! $( '.imagify-bulk-table [name="group[]"]:checked' ).length ) {
				return;
			}

			if ( w.imagify.bulk.hasBlockingError( true ) ) {
				return;
			}

			$infosModal = $( '#tmpl-imagify-bulk-infos' );

			if ( ! $infosModal.length ) {
				w.imagify.bulk.launchAllProcesses();
				return;
			}

			// Swal Information before loading the optimize process.
			swal( {
				title:             imagifyBulk.labels.bulkInfoTitle,
				html:              $infosModal.html(),
				type:              '',
				customClass:       'imagify-sweet-alert imagify-swal-has-subtitle imagify-before-bulk-infos',
				showCancelButton:  true,
				padding:           0,
				width:             554,
				confirmButtonText: imagifyBulk.labels.confirmBulk,
				cancelButtonText:  imagifySwal.labels.cancelButtonText,
				reverseButtons:    true
			} ).then( function() {
				var $row = $( '.imagify-bulk-table [name="group[]"]:checked' ).first().closest( '.imagify-row-folder-type' );

				$.get( w.imagify.bulk.getAjaxUrl( 'bulkInfoSeen', {
					context: $row.data( 'context' )
				} ) );

				$infosModal.remove();

				w.imagify.bulk.launchAllProcesses();
			} ).catch( swal.noop );
		},

		/*
		 * Build the queue and launch all processes.
		 */
		launchAllProcesses: function () {
			var $w      = $( w ),
				$button = $( '#imagify-bulk-action' ),
				skip    = true;

			// Disable the button.
			$button.attr( 'disabled', 'disabled' ).find( '.dashicons' ).addClass( 'rotate' );

			// Add a message to be displayed when the user wants to quit the page.
			$w.on( 'beforeunload', this.getConfirmMessage );

			// Hide the "Complete" message.
			$( '.imagify-row-complete' ).imagifyHide( 200, function() {
				$( this ).removeClass( 'done' );
			} );

			// Close the optimization details.
			$( '.imagify-show-table-details' ).trigger( 'close.imagify' );

			// Make sure to reset properties.
			this.folderTypesQueue     = [];
			this.status               = {};
			this.displayedWaitMessage = false;
			this.processIsStopped     = false;
			this.imagifyAction        = 'optimize';
			this.globalGain           = 0;
			this.globalOriginalSize   = 0;
			this.globalOptimizedSize  = 0;

			$( '.imagify-bulk-table [name="group[]"]:checked' ).each( function() {
				var $checkbox = $( this ),
					$row      = $checkbox.closest( '.imagify-row-folder-type' ),
					groupID   = $row.data( 'group-id' ),
					context   = $row.data( 'context' ),
					level     = $row.find( '.imagify-cell-level [name="level[' + groupID + ']"]:checked' ).val();

				// Build the queue.
				w.imagify.bulk.folderTypesQueue.push( {
					groupID: groupID,
					context: context,
					level:   undefined === level ? -1 : parseInt( level, 10 )
				} );

				// Set the status.
				w.imagify.bulk.status[ groupID ] = {
					isError: false,
					id:      'waiting'
				};

				// Display a "waiting" message + spinner into the folder rows.
				if ( skip ) {
					// No need to do that for the first one, we'll display a "working" row instead.
					skip = false;
					return true;
				}

				// Display the "waiting" folder row and hide the "normal" one.
				w.imagify.bulk.displayFolderRow( 'waiting', $row );
			} );

			// Fasten Imagifybeat: 1 tick every 15 seconds, and disable suspend.
			w.imagify.beat.interval( 15 );
			w.imagify.beat.disableSuspend();

			// Process the queue.
			$w.trigger( 'processQueue.imagify' );
		},

		/*
		 * Process the first item in the queue.
		 */
		processQueue: function () {
			var $row, item;

			if ( w.imagify.bulk.processIsStopped ) {
				return;
			}

			if ( ! w.imagify.bulk.folderTypesQueue.length ) {
				$( w ).trigger( 'queueEmpty.imagify' );
				return;
			}

			if ( ! w.imagify.bulk.displayedWaitMessage ) {
				// Display an alert to wait.
				swal( {
					title:             imagifyBulk.labels.waitTitle,
					html:              imagifyBulk.labels.waitText,
					showConfirmButton: false,
					padding:           0,
					imageUrl:          imagifyBulk.waitImageUrl,
					customClass:       'imagify-sweet-alert'
				} ).catch( swal.noop );
				w.imagify.bulk.displayedWaitMessage = true;
			}

			/**
			 * Fetch files for the first folder type in the queue.
			 */
			item = w.imagify.bulk.folderTypesQueue.shift();
			$row = $( '#cb-select-' + item.groupID ).closest( '.imagify-row-folder-type' );

			// Update status.
			w.imagify.bulk.status[ item.groupID ].id = 'fetching';

			// Display the "working" folder row and hide the "normal" one.
			w.imagify.bulk.displayFolderRow( 'working', $row );

			// Fetch image IDs.
			$.get( w.imagify.bulk.getAjaxUrl( 'getMediaIds', item ) )
				.done( function( response ) {
					var errorMessage;

					swal.close();

					if ( w.imagify.bulk.processIsStopped ) {
						return;
					}

					if ( response.data && response.data.message ) {
						errorMessage = response.data.message;
					} else {
						errorMessage = imagifyBulk.ajaxErrorText;
					}

					if ( ! response.success ) {
						// Error.
						w.imagify.bulk.stopProcess( errorMessage, item );
						return;
					}

					if ( ! response.data || ! ( $.isPlainObject( response.data ) || $.isArray( response.data ) ) ) {
						// Error: should be an array if empty, or an object otherwize.
						w.imagify.bulk.stopProcess( errorMessage, item );
						return;
					}

					// Success.
					if ( ! $.isEmptyObject( response.data ) ) {
						// Optimize the files.
						$( w ).trigger( 'optimizeFiles.imagify', [ item, response.data ] );
						return;
					}

					// No images.
					w.imagify.bulk.status[ item.groupID ].id = 'no-images';

					if ( w.imagify.bulk.hasMultipleRows ) {
						$( '#cb-select-' + item.groupID ).prop( 'checked', false );
					}

					if ( ! w.imagify.bulk.folderTypesQueue.length ) {
						$( w ).trigger( 'queueEmpty.imagify' );
						return;
					}

					// Reset the folder row.
					w.imagify.bulk.displayFolderRow( 'resting', $row );

					$( w ).trigger( 'processQueue.imagify' );
				} )
				.fail( function() {
					// Error.
					w.imagify.bulk.stopProcess( 'get-unoptimized-images', item );
				} );
		},

		/*
		 * Optimize files.
		 *
		 * @param {object} e     jQuery's Event object.
		 * @param {object} item  Current folder type (from the queue).
		 * @param {object} files A list of file IDs (keys, the IDs are prefixed by an underscore) and URLs (values).
		 */
		optimizeFiles: function ( e, item, files ) {
			var $row, $workingRow, $optimizedCount, $errorsCount, $table,
				$progressBar, $progress, $resultsContainer,
				optimizedCount, errorsCount, Optimizer,
				defaultsTemplate = {
					groupID:            item.groupID,
					mediaID:            0,
					thumbnail:          '', // Preview thumbnail src.
					filename:           '',
					status:             '',
					icon:               '',
					label:              '',
					thumbnailsCount:    '',
					originalSizeHuman:  '',
					newSizeHuman:       '',
					percentHuman:       '',
					overallSavingHuman: ''
				};

			if ( w.imagify.bulk.processIsStopped ) {
				return;
			}

			$row         = $( '#cb-select-' + item.groupID ).closest( '.imagify-row-folder-type' );
			$workingRow  = $row.next( '.imagify-row-working' );
			$errorsCount = $workingRow.find( '.imagify-cell-count-errors span' );
			errorsCount  = parseInt( $errorsCount.text(), 10 );
			$table       = $row.closest( '.imagify-bulk-table' );
			$progressBar = $table.find( '.imagify-row-progress' );
			$progress    = $progressBar.find( '.bar' );

			if ( 'optimize' === w.imagify.bulk.imagifyAction ) {
				$optimizedCount = $workingRow.find( '.imagify-cell-count-optimized span' );
				optimizedCount  = parseInt( $optimizedCount.text(), 10 );
			}

			// Update folder status.
			w.imagify.bulk.status[ item.groupID ].id = 'optimizing';

			// Fill in the result table header.
			$table.find( '.imagify-bulk-table-details thead' ).html( $( '#tmpl-imagify-file-header-' + item.groupID ).html() );

			// Empty the result table body.
			$resultsContainer = $table.find( '.imagify-bulk-table-details tbody' ).text( '' );

			// Reset and display the progress bar.
			$progress.css( 'width', '0%' ).find( '.percent' ).text( '0%' );
			$progressBar.slideDown().attr( 'aria-hidden', 'false' );

			// Optimize the files.
			Optimizer = new w.imagify.Optimizer( {
				groupID:      item.groupID,
				context:      item.context,
				level:        item.level,
				bufferSize:   imagifyBulk.bufferSizes[ item.context ],
				ajaxUrl:      w.imagify.bulk.getAjaxUrl( 'bulkProcess', item ),
				files:        files,
				defaultThumb: w.imagify.bulk.defaultThumb,
				doneEvent:    'mediaProcessed.imagify'
			} );

			// Before each media optimization, add a file row displaying the optimization process.
			Optimizer.before( function( data ) {
				var template;

				if ( w.imagify.bulk.processIsStopped ) {
					return;
				}

				template = w.imagify.template( 'imagify-file-row-' + item.groupID );

				w.imagify.bulk.processingMedia.push( {
					context: item.context,
					mediaID: data.mediaID
				} );

				$resultsContainer.prepend( template( $.extend( {}, defaultsTemplate, data, {
					status: 'compressing',
					icon:   'admin-generic rotate',
					label:  imagifyBulk.labels.optimizing
				} ) ) );
			} );

			// After each media optimization.
			Optimizer.each( function( data ) {
				var template, $fileRow;

				if ( w.imagify.bulk.processIsStopped ) {
					return;
				}

				template = w.imagify.template( 'imagify-file-row-' + item.groupID );
				$fileRow = $( '#' + item.groupID + '-' + data.mediaID );

				$.each( w.imagify.bulk.processingMedia, function( i, v ) {
					if ( v.context !== item.context || v.mediaID !== data.mediaID ) {
						return true;
					}

					w.imagify.bulk.processingMedia.splice( i, 1 );
					return false;
				} );

				// Update the progress bar.
				$progress.css( 'width', data.progress + '%' ).find( '.percent' ).html( data.progress + '%' );

				if ( data.success ) {
					if ( 'already-optimized' !== data.status ) {
						// Image successfully optimized.
						$fileRow.replaceWith( template( $.extend( {}, defaultsTemplate, data, {
							status: 'complete',
							icon:   'yes',
							label:  imagifyBulk.labels.complete
						} ) ) );

						w.imagify.bulk.drawFileChart( $( '#' + item.groupID + '-' + data.mediaID ).find( '.imagify-cell-percentage canvas' ) ); // Don't use $fileRow, its DOM is not refreshed with the new values.
					} else {
						// The image was already optimized.
						$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, data, {
							status: 'complete',
							icon:   'yes',
							label:  imagifyBulk.labels.alreadyOptimized
						} ) ), data.error ) );
					}

					// Update the optimized images counter.
					if ( 'optimize' === w.imagify.bulk.imagifyAction ) {
						optimizedCount += 1;
						$optimizedCount.text( optimizedCount );
					}
					return;
				}

				// Display the error in the file row.
				$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, data, {
					status: 'error',
					icon:   'dismiss',
					label:  imagifyBulk.labels.error
				} ) ), data.error || data ) );

				// Update the "working" folder row.
				if ( ! $errorsCount.length ) {
					errorsCount  = 1;
					$errorsCount = $workingRow.find( '.imagify-cell-count-errors' ).html( imagifyBulk.labels.imagesErrorText.replace( '%s', '<span>1</span>' ) ).find( 'span' );
				} else {
					errorsCount += 1;
					$errorsCount.text( errorsCount );
				}

				if ( 'over-quota' === data.status ) {
					// No more data, stop everything.
					Optimizer.stopProcess();
					w.imagify.bulk.stopProcess( data.status, item );
				}
			} );

			// After all image optimizations.
			Optimizer.done( function( data ) {
				// Uncheck the checkbox.
				if ( w.imagify.bulk.hasMultipleRows ) {
					$( '#cb-select-' + item.groupID ).prop( 'checked', false );
				}

				if ( data.globalOriginalSize ) {
					w.imagify.bulk.globalGain          += parseInt( data.globalGain, 10 );
					w.imagify.bulk.globalOriginalSize  += parseInt( data.globalOriginalSize, 10 );
					w.imagify.bulk.globalOptimizedSize += parseInt( data.globalOptimizedSize, 10 );
				}

				if ( w.imagify.bulk.processIsStopped ) {
					return;
				}

				// Reset Imagifybeat interval and enable suspend.
				w.imagify.beat.resetInterval();
				w.imagify.beat.enableSuspend();

				// Update folder type status.
				if ( ! $.isEmptyObject( w.imagify.bulk.status ) && ! w.imagify.bulk.status[ item.groupID ].isError ) {
					w.imagify.bulk.status[ item.groupID ].id = 'done';
				}

				// Update the folder row.
				$row.addClass( 'updating' );

				$.get( w.imagify.bulk.getAjaxUrl( 'getFolderData', item ) )
					.done( function( response ) {
						if ( w.imagify.bulk.processIsStopped ) {
							return;
						}

						if ( response.success ) {
							$.each( response.data, function( dataName, dataHtml ) {
								$row.children( '.imagify-cell-' + dataName ).html( dataHtml );
							} );
						}

						w.imagify.bulk.displayFolderRow( 'resting', $row );
					} )
					.always( function() {
						if ( w.imagify.bulk.processIsStopped ) {
							return;
						}

						$row.removeClass( 'updating' );

						if ( ! w.imagify.bulk.folderTypesQueue.length ) {
							$( w ).trigger( 'queueEmpty.imagify' );
						} else {
							$( w ).trigger( 'processQueue.imagify' );
						}
					} );
			} );

			// Run.
			Optimizer.run();
		},

		/*
		 * End.
		 */
		queueEmpty: function () {
			var $tables   = $( '.imagify-bulk-table' ),
				errorArgs = {},
				hasError  = false,
				noImages  = true,
				errorMsg  = '';

			// Reset Imagifybeat interval and enable suspend.
			w.imagify.beat.resetInterval();
			w.imagify.beat.enableSuspend();

			// Display the share box.
			w.imagify.bulk.displayShareBox();

			// Reset the queue.
			w.imagify.bulk.folderTypesQueue = [];

			// Fetch and display generic stats if stats via Imagifybeat are disabled.
			if ( ! imagifyBulk.imagifybeatIDs.stats ) {
				$.get( w.imagify.bulk.getAjaxUrl( 'getStats' ), {
					types: w.imagify.bulk.getFolderTypes()
				} )
					.done( function( response ) {
						if ( response.success ) {
							w.imagify.bulk.updateStats( response.data );
						}
					} );
			}

			// Maybe display error.
			if ( ! $.isEmptyObject( w.imagify.bulk.status ) ) {
				$.each( w.imagify.bulk.status, function( groupID, typeStatus ) {
					if ( typeStatus.isError ) {
						// One error is enough to display a message.
						hasError = typeStatus.id;
						noImages = false;
						return false;
					}
					if ( 'no-images' !== typeStatus.id ) {
						// All groups must have this ID.
						noImages = false;
						return false;
					}
				} );

				if ( hasError ) {
					if ( 'invalid-api-key' === hasError ) {
						errorArgs = {
							title: imagifyBulk.labels.invalidAPIKeyTitle,
							type:  'info'
						};
					}
					else if ( 'over-quota' === hasError ) {
						errorArgs = {
							title:             imagifyBulk.labels.overQuotaTitle,
							html:              $( '#tmpl-imagify-overquota-alert' ).html(),
							type:              'info',
							customClass:       'imagify-swal-has-subtitle imagify-swal-error-header',
							showConfirmButton: false
						};
					}
					else if ( 'get-unoptimized-images' === hasError || 'consumed-all-data' === hasError ) {
						errorArgs = {
							title: imagifyBulk.labels.getUnoptimizedImagesErrorTitle,
							html:  imagifyBulk.labels.getUnoptimizedImagesErrorText,
							type:  'info'
						};
					}
					w.imagify.bulk.displayError( errorArgs );
				}
				else if ( noImages ) {
					if ( imagifyBulk.labels.nothingToDoText.hasOwnProperty( w.imagify.bulk.imagifyAction ) ) {
						errorMsg = imagifyBulk.labels.nothingToDoText[ w.imagify.bulk.imagifyAction ];
					} else {
						errorMsg = imagifyBulk.labels.nothingToDoText.optimize;
					}
					w.imagify.bulk.displayError( {
						title: imagifyBulk.labels.nothingToDoTitle,
						html:  errorMsg,
						type:  'info'
					} );
				}
			}

			// Reset status.
			w.imagify.bulk.status = {};

			// Unlink the message displayed when the user wants to quit the page.
			$( w ).off( 'beforeunload', w.imagify.bulk.getConfirmMessage );

			// Display the "normal" folder rows (the values of the last one should being updated via ajax, don't display it for now).
			w.imagify.bulk.displayFolderRow( 'resting', $tables.find( '.imagify-row-folder-type' ).not( '.updating' ) );

			// Reset the progress bars.
			$tables.find( '.imagify-row-progress' ).slideUp().attr( 'aria-hidden', 'true' ).find( '.bar' ).removeAttr( 'style' ).find( '.percent' ).text( '0%' );

			// Enable (or not) the main button.
			if ( $( '.imagify-bulk-table [name="group[]"]:checked' ).length ) {
				$( '#imagify-bulk-action' ).removeAttr( 'disabled' ).find( '.dashicons' ).removeClass( 'rotate' );
			} else {
				$( '#imagify-bulk-action' ).find( '.dashicons' ).removeClass( 'rotate' );
			}
		},

		/**
		 * Open a popup window when the user clicks on a share link.
		 *
		 * @param {object} e jQuery Event object.
		 */
		share: function ( e ) {
			var width  = 700,
				height = 290,
				clientLeft, clientTop;

			e.preventDefault();

			if ( w.innerWidth ) {
				clientLeft = ( w.innerWidth - width ) / 2;
				clientTop  = ( w.innerHeight - height ) / 2;
			} else {
				clientLeft = ( d.body.clientWidth - width ) / 2;
				clientTop  = ( d.body.clientHeight - height ) / 2;
			}

			w.open( this.href, '', 'status=no, scrollbars=no, menubar=no, top=' + clientTop + ', left=' + clientLeft + ', width=' + width + ', height=' + height );
		},

		// Imagifybeat =============================================================================

		/**
		 * Add a Imagifybeat ID for global stats on "imagifybeat-send" event.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addStatsImagifybeat: function ( e, data ) {
			data[ imagifyBulk.imagifybeatIDs.stats ] = Object.keys( w.imagify.bulk.getFolderTypes() );
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processStatsImagifybeat: function ( e, data ) {
			if ( typeof data[ imagifyBulk.imagifybeatIDs.stats ] !== 'undefined' ) {
				w.imagify.bulk.updateStats( data[ imagifyBulk.imagifybeatIDs.stats ] );
			}
		},

		/**
		 * Add a Imagifybeat ID on "imagifybeat-send" event to sync the optimization queue.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addQueueImagifybeat: function ( e, data ) {
			if ( w.imagify.bulk.processingMedia.length ) {
				data[ imagifyBulk.imagifybeatIDs.queue ] = w.imagify.bulk.processingMedia;
			}
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processQueueImagifybeat: function ( e, data ) {
			if ( typeof data[ imagifyBulk.imagifybeatIDs.queue ] !== 'undefined' ) {
				$.each( data[ imagifyBulk.imagifybeatIDs.queue ], function ( i, mediaData ) {
					$( w ).trigger( 'mediaProcessed.imagify', [ mediaData ] );
				} );
			}
		},

		/**
		 * Add a Imagifybeat ID for requirements on "imagifybeat-send" event.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addRequirementsImagifybeat: function ( e, data ) {
			data[ imagifyBulk.imagifybeatIDs.requirements ] = 1;
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update requirements status periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processRequirementsImagifybeat: function ( e, data ) {
			if ( typeof data[ imagifyBulk.imagifybeatIDs.requirements ] === 'undefined' ) {
				return;
			}

			data = data[ imagifyBulk.imagifybeatIDs.requirements ];

			imagifyBulk.curlMissing    = data.curl_missing;
			imagifyBulk.editorMissing  = data.editor_missing;
			imagifyBulk.extHttpBlocked = data.external_http_blocked;
			imagifyBulk.apiDown        = data.api_down;
			imagifyBulk.keyIsValid     = data.key_is_valid;
			imagifyBulk.isOverQuota    = data.is_over_quota;
		},

		// Charts ==================================================================================

		/**
		 * Overview chart.
		 * Used for the big overview chart.
		 */
		drawOverviewChart: function ( data ) {
			var initData, legend;

			if ( ! this.charts.overview.canvas ) {
				this.charts.overview.canvas = d.getElementById( 'imagify-overview-chart' );

				if ( ! this.charts.overview.canvas ) {
					return;
				}
			}

			data = data && $.isArray( data ) ? data : [];

			if ( this.charts.overview.donut ) {
				// Update existing donut.
				if ( data.length ) {
					if ( data.reduce( function( a, b ) { return a + b; }, 0 ) === 0 ) {
						data[0] = 1;
					}

					this.charts.overview.donut.data.datasets[0].data = data;
					this.charts.overview.donut.update();
				}
				return;
			}

			// Create new donut.
			this.charts.overview.data.datasets[0].data = [
				parseInt( this.charts.overview.canvas.getAttribute( 'data-unoptimized' ), 10 ),
				parseInt( this.charts.overview.canvas.getAttribute( 'data-optimized' ), 10 ),
				parseInt( this.charts.overview.canvas.getAttribute( 'data-errors' ), 10 )
			];
			initData = $.extend( {}, this.charts.overview.data );

			if ( data.length ) {
				initData.datasets[0].data = data;
			}

			if ( initData.datasets[0].data.reduce( function( a, b ) { return a + b; }, 0 ) === 0 ) {
				initData.datasets[0].data[0] = 1;
			}

			this.charts.overview.donut = new w.imagify.Chart( this.charts.overview.canvas, {
				type:    'doughnut',
				data:    initData,
				options: {
					legend: {
						display: false
					},
					events:    [],
					animation: {
						easing: 'easeOutBounce'
					},
					tooltips: {
						displayColors: false,
						callbacks:     {
							label: function( tooltipItem, localData ) {
								return localData.datasets[ tooltipItem.datasetIndex ].data[ tooltipItem.index ];
							}
						}
					},
					responsive:       false,
					cutoutPercentage: 85
				}
			} );

			// Then generate the legend and insert it to your page somewhere.
			legend = '<ul class="imagify-doughnut-legend">';

			$.each( initData.labels, function( i, label ) {
				legend += '<li><span style="background-color:' + initData.datasets[0].backgroundColor[ i ] + '"></span>' + label + '</li>';
			} );

			legend += '</ul>';

			d.getElementById( 'imagify-overview-chart-legend' ).innerHTML = legend;
		},

		/**
		 * Mini chart.
		 * Used for the charts on each file row.
		 *
		 * @param {element} canvas A jQuery canvas element.
		 */
		drawFileChart: function ( canvas ) {
			var donuts = this.charts.files.donuts;

			canvas.each( function() {
				var value = parseInt( $( this ).closest( '.imagify-chart' ).next( '.imagipercent' ).text().replace( '%', '' ), 10 );

				if ( undefined !== donuts[ this.id ] ) {
					// Update existing donut.
					donuts[ this.id ].data.datasets[0].data[0] = value;
					donuts[ this.id ].data.datasets[0].data[1] = 100 - value;
					donuts[ this.id ].update();
					return;
				}

				// Create new donut.
				donuts[ this.id ] = new w.imagify.Chart( this, {
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

			this.charts.files.donuts = donuts;
		},

		/*
		 * Share Chart.
		 * Used for the chart in the share box.
		 */
		drawShareChart: function () {
			var value;

			if ( ! this.charts.share.canvas ) {
				this.charts.share.canvas = d.getElementById( 'imagify-ac-chart' );

				if ( ! this.charts.share.canvas ) {
					return;
				}
			}

			value = parseInt( $( this.charts.share.canvas ).closest( '.imagify-ac-chart' ).attr( 'data-percent' ), 10 );

			if ( this.charts.share.donut ) {
				// Update existing donut.
				this.charts.share.donut.data.datasets[0].data[0] = value;
				this.charts.share.donut.data.datasets[0].data[1] = 100 - value;
				this.charts.share.donut.update();
				return;
			}

			// Create new donut.
			this.charts.share.donut = new w.imagify.Chart( this.charts.share.canvas, {
				type: 'doughnut',
				data: {
					datasets: [{
						data:            [ value, 100 - value ],
						backgroundColor: [ '#40B1D0', '#FFFFFF' ],
						borderWidth:     0
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
					responsive:       false,
					cutoutPercentage: 70
				}
			} );
		}
	};

	w.imagify.bulk.init();

} )(jQuery, document, window);
