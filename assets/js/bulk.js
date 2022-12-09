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
		// Set to true to stop the whole thing.
		processIsStopped:     false,
		// Global stats.
		globalOptimizedCount: 0,
		globalGain:           0,
		globalOriginalSize:   0,
		globalOptimizedSize:  0,
		/**
		 * Folder types used in the page.
		 *
		 * @var {object} {
		 *     An object of objects. The keys are like: {groupID|context}.
		 *
		 *     @type {string} groupID The group ID.
		 *     @type {string} context The context.
		 * }
		 */
		folderTypesData:      {},

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

			$( '#imagify-bulk-action' )
				.on( 'click.imagify', this.maybeLaunchAllProcesses );

			// Optimization events.
			$( w )
				.on( 'processQueue.imagify', this.processQueue )
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

			if ( imagifyBulk.optimizing ) {
				// Fasten Imagifybeat: 1 tick every 15 seconds, and disable suspend.
				w.imagify.beat.interval( 15 );
				w.imagify.beat.disableSuspend();
			}
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

			if ( item && Number.isInteger( item.level ) ) {
				url += '&optimization_level=' + item.level;
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
						context: $this.data( 'context' ),
						level:   $this.find( '.imagify-cell-level [name="level[' + $this.data( 'group-id' ) + ']"]:checked' ).val()
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
			w.imagify.bulk.processIsStopped = true;

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

				w.imagify.bulk.processIsStopped = true;

				return true;
			}

			if ( imagifyBulk.editorMissing ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.editorMissing
					} );
				}

				w.imagify.bulk.processIsStopped = true;

				return true;
			}

			if ( imagifyBulk.extHttpBlocked ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.extHttpBlocked
					} );
				}

				w.imagify.bulk.processIsStopped = true;

				return true;
			}

			if ( imagifyBulk.apiDown ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						html: imagifyBulk.labels.apiDown
					} );
				}

				w.imagify.bulk.processIsStopped = true;

				return true;
			}

			if ( ! imagifyBulk.keyIsValid ) {
				if ( displayErrorMessage ) {
					w.imagify.bulk.displayError( {
						title: imagifyBulk.labels.invalidAPIKeyTitle,
						type:  'info'
					} );
				}

				w.imagify.bulk.processIsStopped = true;

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

				w.imagify.bulk.processIsStopped = true;

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
		 * Display the share box.
		 */
		displayShareBox: function () {
			var $complete, globalSaved;

			if ( ! this.globalGain || this.folderTypesQueue.length ) {
				this.globalOptimizedCount = 0;
				this.globalGain           = 0;
				this.globalOriginalSize   = 0;
				this.globalOptimizedSize  = 0;
				return;
			}

			globalSaved = this.globalOriginalSize - this.globalOptimizedSize;

			$complete = $( '.imagify-row-complete' );
			$complete.find( '.imagify-ac-rt-total-images' ).html( this.globalOptimizedCount );
			$complete.find( '.imagify-ac-rt-total-gain' ).html( w.imagify.humanSize( globalSaved, 1 ) );
			$complete.find( '.imagify-ac-rt-total-original' ).html( w.imagify.humanSize( this.globalOriginalSize, 1 ) );
			$complete.find( '.imagify-ac-chart' ).attr( 'data-percent', Math.round( this.globalGain ) );

			// Chart.
			this.drawShareChart();

			$complete.addClass( 'done' ).imagifyShow();

			$( 'html, body' ).animate( {
				scrollTop: $complete.offset().top
			}, 200 );

			// Reset the stats.
			this.globalOptimizedCount = 0;
			this.globalGain           = 0;
			this.globalOriginalSize   = 0;
			this.globalOptimizedSize  = 0;
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

			if ( imagifyBulk.optimizing ) {
				$( '#imagify-bulk-action' ).prop( 'disabled', true );

				return;
			}

			// Enable or disable the Optimization button.
			if ( $( '.imagify-bulk-table [name="group[]"]:checked' ).length ) {
				$( '#imagify-bulk-action' ).prop( 'disabled', false );
			} else {
				$( '#imagify-bulk-action' ).prop( 'disabled', true );
			}
		},

		/*
		 * Maybe display a modal, then launch all processes.
		 */
		maybeLaunchAllProcesses: function () {
			var $infosModal;

			if ( $( this ).prop('disabled') ) {
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
				$button = $( '#imagify-bulk-action' );

			// Disable the button.
			$button.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'rotate' );

			// Hide the "Complete" message.
			$( '.imagify-row-complete' ).imagifyHide( 200, function() {
				$( this ).removeClass( 'done' );
			} );

			// Make sure to reset properties.
			this.folderTypesQueue     = [];
			this.status               = {};
			this.displayedWaitMessage = false;
			this.processIsStopped     = false;
			this.globalOptimizedCount = 0;
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
			var $row, $table, $progressBar, $progress;

			if ( w.imagify.bulk.processIsStopped ) {
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

			w.imagify.bulk.folderTypesQueue.forEach( function( item ) {
				// Start async process for current context
				$.get( w.imagify.bulk.getAjaxUrl( 'bulkProcess', item ) )
					.done( function( response ) {
						var errorMessage;

						swal.close();

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
						if ( response.success ) {
							$row         = $( '#cb-select-' + item.groupID ).closest( '.imagify-row-folder-type' );
							$table       = $row.closest( '.imagify-bulk-table' );
							$progressBar = $table.find( '.imagify-row-progress' );
							$progress    = $progressBar.find( '.bar' );

							$row.find( '.imagify-cell-checkbox-loader' ).removeClass( 'hidden' ).attr( 'aria-hidden', 'false' );
							$row.find( '.imagify-cell-checkbox-box' ).addClass( 'hidden' ).attr( 'aria-hidden', 'true' );

							// Reset and display the progress bar.
							$progress.css( 'width', '0%' ).find( '.percent' ).text( '0%' );
							$progressBar.slideDown().attr( 'aria-hidden', 'false' );
						}
					} )
					.fail( function() {
						// Error.
						w.imagify.bulk.stopProcess( 'get-unoptimized-images', item );
					} );
			} );
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

			// Reset the queue.
			w.imagify.bulk.folderTypesQueue = [];

			// Display the share box.
			w.imagify.bulk.displayShareBox();

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
					if ( ! typeStatus.isError ) {
						noImages = false;
					} else if ( 'no-images' !== typeStatus.id && typeStatus.isError ) {
						hasError = typeStatus.id;
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
					if ( Object.prototype.hasOwnProperty.call( imagifyBulk.labels.nothingToDoText, w.imagify.bulk.imagifyAction ) ) {
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

			// Reset the progress bars.
			$tables.find( '.imagify-row-progress' ).slideUp().attr( 'aria-hidden', 'true' ).find( '.bar' ).removeAttr( 'style' ).find( '.percent' ).text( '0%' );

			$tables.find( '.imagify-cell-checkbox-loader' ).each( function() {
				$(this).addClass( 'hidden' ).attr( 'aria-hidden', 'true' );
			} );

			$tables.find( '.imagify-cell-checkbox-box' ).each( function() {
				$(this).removeClass( 'hidden' ).attr( 'aria-hidden', 'false' );
			} );

			// Enable (or not) the main button.
			if ( $( '.imagify-bulk-table [name="group[]"]:checked' ).length ) {
				$( '#imagify-bulk-action' ).prop( 'disabled', false ).find( '.dashicons' ).removeClass( 'rotate' );
			} else {
				$( '#imagify-bulk-action' ).find( '.dashicons' ).removeClass( 'rotate' );
			}
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
			data[ imagifyBulk.imagifybeatIDs.queue ] = Object.values( w.imagify.bulk.getFolderTypes() );
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processQueueImagifybeat: function ( e, data ) {
			var queue, $row, $progress, $bar;

			if ( typeof data[ imagifyBulk.imagifybeatIDs.queue ] !== 'undefined' ) {
				queue = data[ imagifyBulk.imagifybeatIDs.queue ];

				if ( false !== queue.result ) {
					w.imagify.bulk.globalOriginalSize = queue.result.original_size;
					w.imagify.bulk.globalOptimizedSize = queue.result.optimized_size;
					w.imagify.bulk.globalOptimizedCount = queue.result.total;
					w.imagify.bulk.globalGain = w.imagify.bulk.globalOptimizedSize * 100 / w.imagify.bulk.globalOriginalSize;
				}

				if ( ! w.imagify.bulk.processIsStopped && w.imagify.bulk.hasBlockingError( true ) ) {
					$( w ).trigger( 'queueEmpty.imagify' );
					return;
				}

				if ( Object.prototype.hasOwnProperty.call( queue, 'groups_data' ) ) {
					Object.entries( queue.groups_data ).forEach( function( item ) {
						$row = $( '[data-context=' + item[0] + ']' );

						$row.children( '.imagify-cell-count-optimized' ).first().html( item[1]['count-optimized'] );
						$row.children( '.imagify-cell-count-errors' ).first().html( item[1]['count-errors'] );
						$row.children( '.imagify-cell-optimized-size-size' ).first().html( item[1]['optimized-size'] );
						$row.children( '.imagify-cell-original-size-size' ).first().html( item[1]['original-size'] );
					} );
				}

				if ( 0 === queue.remaining ) {
					$( w ).trigger( 'queueEmpty.imagify' );
					return;
				}

				$progress = $( '.imagify-row-progress' );
				$bar      = $progress.find( '.bar' );

				$bar.css( 'width', queue.percentage + '%' ).find( '.percent' ).html( queue.percentage + '%' );
				$progress.slideDown().attr( 'aria-hidden', 'false' );
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
