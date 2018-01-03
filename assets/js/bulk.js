(function( $ ) {

	// Custom jQuery functions =====================================================================
	/**
	 * Hide element(s).
	 *
	 * @return {element} The jQuery element(s).
	 */
	$.fn.imagifyHide = function( duration ) {
		if ( duration && duration > 0 ) {
			this.hide( duration, function() {
				$( this ).addClass( 'hidden' ).css( 'display', '' );
			} );
		} else {
			this.addClass( 'hidden' );
		}
		return this.attr( 'aria-hidden', 'true' );
	};

	/**
	 * Show element(s).
	 *
	 * @return {element} The jQuery element(s).
	 */
	$.fn.imagifyShow = function( duration ) {
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
					labels: [
						imagifyBulk.labels.overviewChartLabels.unoptimized,
						imagifyBulk.labels.overviewChartLabels.optimized,
						imagifyBulk.labels.overviewChartLabels.error
					],
					datasets: [{
						data: [
							imagifyBulk.totalUnoptimizedAttachments,
							imagifyBulk.totalOptimizedAttachments,
							imagifyBulk.totalErrorsAttachments
						],
						backgroundColor: [ '#D9E4EB', '#46B1CE', '#2E3242' ],
						borderWidth:     0
					}]
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
		queue:               [],
		stopOptimization:    false,
		globalGain:          0,
		globalOriginalSize:  0,
		globalOptimizedSize: 0,

		// Methods =================================================================================

		/*
		 * Init.
		 */
		init: function () {
			// Overview chart.
			this.drawOverviewChart();

			// Buttons.
			$( '.imagify-view-optimization-details' ).on( 'click.imagify open.imagify close.imagify', this.toggleOptimizationDetails );
			$( '#imagify-bulk-action' ).on( 'click.imagify', this.launchAllProcesses );
			$( '.imagify-share-networks a' ).on( 'click.imagify', this.share );

			// Optimization events.
			$( w )
				.on( 'processQueue.imagify', this.processQueue )
				.on( 'optimizeFiles.imagify', this.optimizeFiles )
				.on( 'queueEmpty.imagify', this.queueEmpty );

			// Heartbeat.
			$( d )
				.on( 'heartbeat-send', this.addHeartbeat )
				.on( 'heartbeat-tick', this.processHeartbeat );
		},

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
					this.charts.overview.donut.data.datasets[0].data = data;
					this.charts.overview.donut.update();
				}
				return;
			}

			// Create new donut.
			initData = $.extend( {}, this.charts.overview.data );

			if ( data.length ) {
				initData.datasets[0].data = data;
			}

			this.charts.overview.donut = new Chart( this.charts.overview.canvas, {
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
				donuts[ this.id ] = new Chart( this, {
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
			this.charts.share.donut = new Chart( this.charts.share.canvas, {
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
		 * Display an error message in a modal.
		 * It also triggers the event "queue empty".
		 *
		 * @param {string} title The modal title.
		 * @param {string} text  The modal text.
		 * @param {string} title The modal type ("error", "info").
		 */
		displayError: function ( title, text, type ) {
			title = title || imagifyBulk.labels.error;
			text  = text || '';
			type  = type || 'error';
			this.stopOptimization = true;

			swal( {
				title:       title,
				html:        text,
				type:        type,
				customClass: 'imagify-sweet-alert'
			} );

			$( w ).trigger( 'queueEmpty.imagify' );
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

			$toReplace.remove();
			$row.find( '.imagify-cell-status' ).after( '<td colspan="' + colspan + '">' + text + '</td>' );

			return $row;
		},

		/*
		 * Display one of the 3 group headers.
		 *
		 * @param {string}  state      One of the 3 states: 'resting' (it's the "normal" title), 'fetching', and 'optimizing'.
		 * @param {element} $container jQuery element of the group container.
		 */
		displayGroupHeader: function ( state, $container ) {
			$container = $container.find( '.imagify-bulk-table-title' );
			$container.not( '.imagify-' + state ).imagifyHide();
			$container.filter( '.imagify-' + state ).imagifyShow();
		},

		/*
		 * If the current folder is the last of the group, display the "normal" group header.
		 *
		 * @param {string}  groupId    The folder's group ID.
		 * @param {element} $container jQuery element of the group container.
		 */
		maybeResetGroupHeader: function ( groupId, $table ) {
			var resetHeader = true;

			$.each( this.queue, function( i, otherItem ) {
				if ( otherItem.groupId === groupId ) {
					resetHeader = false;
					return false;
				}
			} );

			if ( resetHeader ) {
				this.displayGroupHeader( 'resting', $table );
			}
		},

		/*
		 * Display one of the 3 "folder" rows.
		 *
		 * @param {string}  state One of the 3 states: 'resting' (it's the "normal" row), 'waiting' (waiting for other optimizations to finish), and 'working'.
		 * @param {element} $row  jQuery element of the "normal" row.
		 */
		displayFolderRow: function ( state, $row ) {
			var $newRow, spinner, text;

			if ( 'resting' === state ) {
				$row.next( '.imagify-row-waiting, .imagify-row-working' ).remove();
				$row.imagifyShow();
				return;
			}

			// This part won't work to display multiple $newRow.
			$newRow = $row.next( '.imagify-row-waiting, .imagify-row-working' );

			if ( 'waiting' === state ) {
				spinner = imagifyBulk.spinnerWaitingUrl;
				text    = imagifyBulk.labels.waitingOtimizationsText;
			} else {
				spinner = imagifyBulk.spinnerWorkingUrl;
				text    = imagifyBulk.labels.imagesOptimizedText.replace( '%s', '<span>0</span>' );
			}

			if ( $newRow.length ) {
				if ( ! $newRow.hasClass( 'imagify-row-' + state ) ) {
					// Should happen when switching from 'waiting' to 'working'.
					$newRow.attr( 'class', 'imagify-row-' + state );
					$newRow.find( '.imagify-cell-checkbox img' ).attr( 'src', spinner );
					$newRow.children( '.imagify-cell-images-optimized' ).html( text );
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

			$newRow.children( '.imagify-cell-checkbox' ).html( '<img alt="" src="' + spinner + '"/>' );
			$newRow.children( '.imagify-cell-title' ).text( $newRow.children( '.imagify-cell-title' ).text() );
			$newRow.children( '.imagify-cell-images-optimized' ).html( text );
			$newRow.children( '.imagify-cell-errors, .imagify-cell-optimized, .imagify-cell-original, .imagify-cell-level' ).text( '' );

			$row.imagifyHide().after( $newRow );
		},

		/*
		 * Display the share box.
		 */
		displayShareBox: function () {
			var text2share = imagifyBulk.labels.textToShare,
				percent, gainHuman, originalSizeHuman,
				$complete;

			if ( ! this.globalGain || this.queue.length ) {
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

		// Event callbacks =========================================================================

		/*
		 * Display/Hide optimization details.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		toggleOptimizationDetails: function ( e ) {
			var $button  = $( this ),
				$details = $button.closest( 'table' ).find( '.imagify-row-optimization-details' ),
				openDetails;

			if ( 'open' === e.type ) {
				openDetails = true;
			} else if ( 'close' === e.type ) {
				openDetails = false;
			} else {
				openDetails = $details.hasClass( 'hidden' );
			}

			if ( openDetails ) {
				$button.addClass( 'close' );
				$details.imagifyShow();
			} else {
				$button.removeClass( 'close' );
				$details.imagifyHide();
			}
		},

		/*
		 * Build the queue and launch all processes.
		 */
		launchAllProcesses: function () {
			var $w          = $( w ),
				$button     = $( this ),
				$checkboxes = $( '.imagify-bulk-table [name="group[]"]:checked' ),
				skip        = true;

			if ( ! $checkboxes.length || $button.attr( 'disabled' ) ) {
				return;
			}

			// Disable the button.
			$button.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'rotate' );

			// Add a message to be displayed when the user wants to quit the page.
			$w.on( 'beforeunload', w.imagify.bulk.getConfirmMessage );

			// Hide the "Complete" message.
			$( '.imagify-row-complete' ).imagifyHide( 200 );

			// Close the optimization details.
			$( '.imagify-view-optimization-details' ).trigger( 'close.imagify' );

			// Make sure to reset global vars.
			w.imagify.bulk.queue   = [];
			w.imagify.bulk.stopOptimization    = false;
			w.imagify.bulk.globalGain          = 0;
			w.imagify.bulk.globalOriginalSize  = 0;
			w.imagify.bulk.globalOptimizedSize = 0;

			$checkboxes.each( function() {
				var $checkbox = $( this ),
					$row      = $checkbox.closest( '.imagify-row-folder-type' ),
					groupId   = $row.closest( 'table' ).data( 'group-id' ),
					type      = $checkbox.val(),
					level     = $row.find( '.imagify-cell-level [name="level[' + type + ']"]' ).val();

				// Build the queue.
				w.imagify.bulk.queue.push( {
					groupId: groupId,
					type:    type,
					level:   undefined === level ? -1 : parseInt( level, 10 )
				} );

				// Display a "waiting" message + spinner into the folder rows.
				if ( skip ) {
					// No need to do that for the first one, we'll display a "working" message into it instead.
					skip = false;
					return true;
				}

				// Display the "waiting" folder row and hide the "normal" one.
				w.imagify.bulk.displayFolderRow( 'waiting', $row );
			} );

			// Process the queue.
			$w.trigger( 'processQueue.imagify' );
		},

		/*
		 * Process the first item in the queue.
		 */
		processQueue: function () {
			var $row, $table, item, url;

			if ( w.imagify.bulk.stopOptimization ) {
				return;
			}

			if ( ! w.imagify.bulk.queue.length ) {
				$( w ).trigger( 'queueEmpty.imagify' );
				return;
			}

			/**
			 * Fetch files for the first folder type in the queue.
			 */
			item   = w.imagify.bulk.queue.shift();
			$row   = $( '#cb-select-' + item.type ).closest( '.imagify-row-folder-type' );
			$table = $row.closest( 'table' );

			// Display the "Fetching" message in the table header.
			w.imagify.bulk.displayGroupHeader( 'fetching', $table );

			// Display the "working" folder row and hide the "normal" one.
			w.imagify.bulk.displayFolderRow( 'working', $row );

			// Fetch image IDs.
			url = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&optimization_level=' + item.level;

			if ( 'library' === item.type ) {
				url += '&action=' + imagifyBulk.ajaxActions.libraryFetch;
			} else {
				url += '&action=' + imagifyBulk.ajaxActions.customFolderFetch + '&folder_type=' + item.type;
			}

			$.get( url )
				.done( function( response ) {
					var swalTitle = imagifyBulk.labels.error,
						swalText  = '';

					if ( w.imagify.bulk.stopOptimization ) {
						return;
					}

					if ( response.success ) {
						// Optimize the files.
						$( w ).trigger( 'optimizeFiles.imagify', [ item, response.data ] );
						return;
					}

					// Display an error message.
					if ( 'invalid-api-key' === response.data.message ) {
						swalTitle = imagifyBulk.labels.invalidAPIKeyTitle;
					}
					else if ( 'over-quota' === response.data.message ) {
						swalTitle = imagifyBulk.labels.overQuotaTitle;
						swalText  = imagifyBulk.labels.overQuotaText;
					}
					else if ( 'no-images' === response.data.message ) {
						if ( w.imagify.bulk.queue.length ) {
							/**
							 * Don't throw an error if not the last in queue.
							 */
							// Uncheck the checkbox.
							$( '#cb-select-' + item.type ).prop( 'checked', false );

							if ( ! w.imagify.bulk.stopOptimization ) {
								// Maybe display the "normal" header.
								w.imagify.bulk.maybeResetGroupHeader( item.groupId, $table );

								$( w ).trigger( 'processQueue.imagify' );
							}

							return;
						}

						swalTitle = imagifyBulk.labels.noAttachmentToOptimizeTitle;
						swalText  = imagifyBulk.labels.noAttachmentToOptimizeText;
					}

					w.imagify.bulk.displayError( swalTitle, swalText, 'info' );
				} )
				.fail( function() {
					// Display an error message.
					w.imagify.bulk.displayError( imagifyBulk.labels.getUnoptimizedImagesErrorTitle, imagifyBulk.labels.getUnoptimizedImagesErrorText );
				} );
		},

		/*
		 * Optimize files.
		 *
		 * @param {object} e     jQuery's Event object.
		 * @param {object} item  Current item (from the queue).
		 * @param {object} files A list of file IDs (key) and URLs (values).
		 */
		optimizeFiles: function ( e, item, files ) {
			var $row             = $( '#cb-select-' + item.type ).closest( '.imagify-row-folder-type' ),
				$workingRow      = $row.next( '.imagify-row-working' ),
				$optimizedCount  = $workingRow.find( '.imagify-cell-images-optimized span' ),
				$errorsCount     = $workingRow.find( '.imagify-cell-errors span' ),
				$table           = $row.closest( 'table' ),
				defaultsTemplate = {
					groupId:              item.groupId,
					id:                   0,
					thumbnail:            '', // Image src.
					filename:             '',
					status:               '',
					icon:                 '',
					label:                '',
					thumbnails:           '',
					original_size_human:  '',
					new_size_human:       '',
					chartSuffix:          '',
					percent_human:        '',
					overall_saving_human: ''
				},
				url, Optimizer, $resultsContainer;

			if ( w.imagify.bulk.stopOptimization ) {
				return;
			}

			// Empty the result details.
			$table.find( '.imagify-row-optimization-details tbody' ).text( '' );

			// Display the "Optimizing" message in the table header.
			w.imagify.bulk.displayGroupHeader( 'optimizing', $table );

			// Optimize the files.
			url = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&optimization_level=' + item.level;

			if ( 'library' === item.type ) {
				url += '&action=' + imagifyBulk.ajaxActions.libraryOptimize;
			} else {
				url += '&action=' + imagifyBulk.ajaxActions.customFolderOptimize + '&folder_type=' + item.type;
			}

			Optimizer = new ImagifyGulp( {
				'buffer_size': imagifyBulk.bufferSize,
				'lib':         url,
				'images':      files,
				'context':     imagifyBulk.ajaxContext
			} );

			$resultsContainer = $table.find( '.imagify-row-optimization-details tbody' );

			// Before the attachment optimization, add a file row displaying the optimization process.
			Optimizer.before( function( data ) {
				var template = w.imagify.template( 'imagify-file-row-' + item.groupId );

				$resultsContainer.prepend( template( $.extend( {}, defaultsTemplate, {
					status:      'compressing',
					icon:        'admin-generic rotate',
					label:       imagifyBulk.labels.optimizing,
					chartSuffix: data.image_id
				}, data ) ) );
			} );

			// After the attachment optimization.
			Optimizer.each( function( data ) {
				var template = w.imagify.template( 'imagify-file-row-' + item.groupId ),
					$fileRow = $( '#' + item.groupId + '-' + data.image );

				// Update thee progress bar.
				$table.find( '.imagify-progress-bar' ).css( 'width', data.progress + '%' ).find( '.percent' ).html( data.progress + '%' );

				if ( data.success ) {
					// Image successfully optimized.
					$fileRow.replaceWith( template( $.extend( {}, defaultsTemplate, {
						status:      'complete',
						icon:        'yes',
						label:       imagifyBulk.labels.complete,
						chartSuffix: data.image
					}, data ) ) );

					w.imagify.bulk.drawFileChart( $( '#' + item.groupId + '-' + data.image ).find( '.imagify-cell-percentage canvas' ) ); // Don't use $fileRow, its DOM is not refreshed with the new values.

					// Update the "working" folder row.
					$optimizedCount.text( parseInt( $optimizedCount.text(), 10 ) + 1 );
					return;
				}

				if ( 'already_optimized' === data.error_code ) {
					// The image was already optimized.
					$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, {
						status:      'complete',
						icon:        'yes',
						label:       imagifyBulk.labels.alreadyOptimized,
						chartSuffix: data.image
					}, data ) ), data.error ) );
					return;
				}

				if ( 'consumed_all_data' === data.error_code ) {
					// No more data, stop optimization.
					if ( ! w.imagify.bulk.stopOptimization ) {
						Optimizer.stopProcess();

						// Display an alert to warn that all data is consumed.
						w.imagify.bulk.displayError( imagifyBulk.labels.overQuotaTitle, imagifyBulk.labels.overQuotaText );
					}
					return;
				}

				// Display the error.
				$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, {
					status:      'error',
					icon:        'dismiss',
					label:       imagifyBulk.labels.error,
					chartSuffix: data.image
				}, data ) ), data.error ) );

				// Update the "working" folder row.
				if ( ! $errorsCount.length ) {
					$errorsCount = $workingRow.find( '.imagify-cell-errors' ).html( imagifyBulk.labels.imagesErrorText.replace( '%s', '<span>1</span>' ) ).find( 'span' );
				} else {
					$errorsCount.text( parseInt( $errorsCount.text(), 10 ) + 1 );
				}
			} );

			// After all image optimizations.
			Optimizer.done( function( data ) {
				var statsUrl;

				// Uncheck the checkbox.
				$( '#cb-select-' + item.type ).prop( 'checked', false );

				if ( data.global_original_size ) {
					w.imagify.bulk.globalGain          += parseInt( data.global_gain, 10 );
					w.imagify.bulk.globalOriginalSize  += parseInt( data.global_original_size, 10 );
					w.imagify.bulk.globalOptimizedSize += parseInt( data.global_optimized_size, 10 );
				}

				if ( w.imagify.bulk.stopOptimization ) {
					return;
				}

				// Update and display the "normal" folder row.
				$row.addClass( 'updating' );

				statsUrl  = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce;
				statsUrl += '&action=' + imagifyBulk.ajaxActions.getFolderData;
				statsUrl += '&folder_type=' + item.type;

				$.get( statsUrl )
					.done( function( response ) {
						if ( response.success ) {
							$.each( response.data, function( dataName, dataHtml ) {
								$row.children( '.imagify-cell-' + dataName ).html( dataHtml );
							} );
						}
						w.imagify.bulk.displayFolderRow( 'resting', $row );
					} )
					.always( function() {
						$row.removeClass( 'updating' );
					} );

				if ( ! w.imagify.bulk.queue.length ) {
					$( w ).trigger( 'queueEmpty.imagify' );
				} else {
					// Maybe display the "normal" header.
					w.imagify.bulk.maybeResetGroupHeader( item.groupId, $table );

					$( w ).trigger( 'processQueue.imagify' );
				}
			} );

			// Run.
			Optimizer.run();
		},

		/*
		 * End.
		 */
		queueEmpty: function () {
			var $tables = $( '.imagify-bulk-table' );

			// Display the share box.
			w.imagify.bulk.displayShareBox();

			// Reset the queue.
			w.imagify.bulk.queue = [];

			// Unlink the message displayed when the user wants to quit the page.
			$( w ).off( 'beforeunload', w.imagify.bulk.getConfirmMessage );

			// Display the "normal" headers.
			w.imagify.bulk.displayGroupHeader( 'resting', $tables );

			// Display the "normal" folder rows (the values of the last one should being updated via ajax, don't display it for now).
			w.imagify.bulk.displayFolderRow( 'resting', $tables.find( '.imagify-row-folder-type' ).not( '.updating' ) );

			// Reset the progress bars.
			$tables.find( '.imagify-progress-bar' ).removeAttr( 'style' ).find( '.percent' ).html( '0%' );

			// Enable the main button.
			$( '#imagify-bulk-action' ).prop( 'disabled', false ).find( '.dashicons' ).removeClass( 'rotate' );
		},

		/**
		 * Add our Heartbeat ID on "heartbeat-send" event.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		addHeartbeat: function ( e, data ) {
			data.imagify_heartbeat = imagifyBulk.heartbeatId;
		},

		/**
		 * Listen for the custom event "heartbeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		processHeartbeat: function ( e, data ) {
			var donutData;

			if ( ! data.imagify_bulk_data ) {
				return;
			}

			data = data.imagify_bulk_data;

			if ( w.imagify.bulk.charts.overview.donut.data ) {
				donutData = w.imagify.bulk.charts.overview.donut.data.datasets[0].data;

				if ( data.unoptimized_attachments === donutData[0] && data.optimized_attachments === donutData[1] && data.errors_attachments === donutData[2] ) {
					return;
				}
			}

			/**
			 * User account.
			 */
			$( '.imagify-unconsumed-percent' ).html( data.unconsumed_quota + '%' );
			$( '.imagify-unconsumed-bar' ).css( 'width', data.unconsumed_quota + '%' );

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
		}
	};

	w.imagify.bulk.init();

} )(jQuery, document, window);
