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
			}
		},
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
		contexts:             [],

		// Methods =================================================================================

		/*
		 * Init.
		 */
		init: function () {
			var $document = $( d );

			// Overview chart.
			this.drawOverviewChart();

			this.hasMultipleRows = $( '.imagify-bulk-table [name="group[]"]' ).length > 1;

			// Other buttons/UI.
			$( '.imagify-bulk-table [name="group[]"]' )
				.on( 'change.imagify init.imagify', this.toggleOptimizationButton )
				.trigger( 'init.imagify' );

			$( '#imagify-bulk-action' )
				.on( 'click.imagify', this.maybeLaunchAllProcesses );

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
				$button = $( '#imagify-bulk-action' ),
				skip    = true;

			// Disable the button.
			$button.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'rotate' );

			// Hide the "Complete" message.
			$( '.imagify-row-complete' ).imagifyHide( 200, function() {
				$( this ).removeClass( 'done' );
			} );

			// Make sure to reset properties.
			this.status               = {};
			this.displayedWaitMessage = false;
			this.processIsStopped     = false;
			this.globalGain           = 0;
			this.globalOriginalSize   = 0;
			this.globalOptimizedSize  = 0;

			$( '.imagify-bulk-table [name="group[]"]:checked' ).each( function() {
				var $checkbox = $( this ),
					$row      = $checkbox.closest( '.imagify-row-folder-type' ),
					groupID   = $row.data( 'group-id' );

				w.imagify.bulk.contexts.push( $row.data( 'context' ) );

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

			// Update status.
			w.imagify.bulk.status[ item.groupID ].id = 'fetching';

			// Fetch image IDs.
			$.get( w.imagify.bulk.getAjaxUrl( 'bulkProcess', w.imagify.bulk.contexts ) )
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
						w.imagify.bulk.stopProcess( errorMessage );
						return;
					}

					// Success.
					if ( ! $.isEmptyObject( response.data ) ) {
						return;
					}
				} )
				.fail( function() {
					// Error.
					w.imagify.bulk.stopProcess( 'get-unoptimized-images' );
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
				$progressBar, $progress,
				optimizedCount, errorsCount, Optimizer;

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
				doneEvent:    'mediaProcessed.imagify'
			} );

			// After each media optimization.
			Optimizer.each( function( data ) {
				if ( w.imagify.bulk.processIsStopped ) {
					return;
				}

				// Update the progress bar.
				$progress.css( 'width', data.progress + '%' ).find( '.percent' ).html( data.progress + '%' );

				if ( data.success ) {
					// Update the optimized images counter.
					if ( 'optimize' === w.imagify.bulk.imagifyAction ) {
						optimizedCount += 1;
						$optimizedCount.text( optimizedCount );
					}
					return;
				}

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

			// Reset the progress bars.
			$tables.find( '.imagify-row-progress' ).slideUp().attr( 'aria-hidden', 'true' ).find( '.bar' ).removeAttr( 'style' ).find( '.percent' ).text( '0%' );

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
		}
	};

	w.imagify.bulk.init();

} )(jQuery, document, window);
