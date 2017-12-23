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

	// Charts ======================================================================================
	var overviewCanvas = d.getElementById( 'imagify-overview-chart' ),
		overviewData   = {
			labels: [
				imagifyBulk.labels.overviewChartLabels.unoptimized,
				imagifyBulk.labels.overviewChartLabels.optimized,
				imagifyBulk.labels.overviewChartLabels.error
			],
			datasets: [{
				data:     [
					imagifyBulk.totalUnoptimizedAttachments,
					imagifyBulk.totalOptimizedAttachments,
					imagifyBulk.totalErrorsAttachments
				],
				backgroundColor: [ '#D9E4EB', '#46B1CE', '#2E3242' ],
				borderWidth:     0
			}]
		},
		overviewDoughnut, overviewLegend;

	/**
	 * Mini chart.
	 * Used for the charts on each file row.
	 *
	 * @param {element} canvas A canvas element.
	 */
	function imagifyDrawFileChart( canvas ) {
		var donuts;

		if ( ! this.donuts ) {
			this.donuts = {};
		}

		donuts = this.donuts;

		canvas.each( function() {
			var value = parseInt( $( this ).closest( '.imagify-chart' ).next( '.imagipercent' ).text().replace( '%', '' ), 10 );

			if ( undefined !== donuts[ this.id ] ) {
				donuts[ this.id ].data.datasets[0].data[0] = value;
				donuts[ this.id ].data.datasets[0].data[1] = 100 - value;
				donuts[ this.id ].update();
				return;
			}

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

		this.donuts = donuts;
	}

	/*
	 * Complete Chart.
	 * Used for the big general chart and the one in the share box.
	 *
	 * @param {element} canvas A canvas element.
	 */
	function drawMeCompleteChart( canvas ) {
		var donut = this.donut;

		canvas.each( function() {
			var value = parseInt( $( this ).closest( '.imagify-ac-chart' ).attr( 'data-percent' ), 10 );

			if ( undefined !== donut ) {
				donut.data.datasets[0].data[0] = value;
				donut.data.datasets[0].data[1] = 100 - value;
				donut.update();
				return;
			}

			donut = new Chart( this, {
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
		} );

		this.donut = donut;
	}

	if ( overviewCanvas ) {
		overviewDoughnut = new Chart( overviewCanvas, {
			type:    'doughnut',
			data:    overviewData,
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
						label: function( tooltipItem, data ) {
							return data.datasets[ tooltipItem.datasetIndex ].data[ tooltipItem.index ];
						}
					}
				},
				responsive:       false,
				cutoutPercentage: 85
			}
		} );

		/**
		 * Then you just need to generate the legend.
		 * var overviewLegend = overviewDoughnut.generateLegend();
		 * bugged `segments undefined`?
		 */

		// And append it to your page somewhere.
		overviewLegend = '<ul class="imagify-doughnut-legend">';

		$.each( overviewData.labels, function( i, label ) {
			overviewLegend += '<li><span style="background-color:' + overviewData.datasets[0].backgroundColor[ i ] + '"></span>' + label + '</li>';
		} );

		overviewLegend += '</ul>';

		d.getElementById( 'imagify-overview-chart-legend' ).innerHTML = overviewLegend;
	}

	// Heartbeat ===================================================================================
	$( d ).on( 'heartbeat-send', function( e, data ) {
		data.imagify_heartbeat = imagifyBulk.heartbeatId;
	} );

	/**
	 * Listen for the custom event "heartbeat-tick" on $(document).
	 * It allows to update various data periodically.
	 */
	$( d ).on( 'heartbeat-tick', function( e, data ) {
		var donutData;

		if ( ! data.imagify_bulk_data ) {
			return;
		}

		data      = data.imagify_bulk_data;
		donutData = overviewDoughnut.data.datasets[0].data;

		if ( data.unoptimized_attachments === donutData[0] && data.optimized_attachments === donutData[1] && data.errors_attachments === donutData[2] ) {
			return;
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

		overviewDoughnut.data.datasets[0].data[0] = data.unoptimized_attachments;
		overviewDoughnut.data.datasets[0].data[1] = data.optimized_attachments;
		overviewDoughnut.data.datasets[0].data[2] = data.errors_attachments;
		overviewDoughnut.update();

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
	} );

	// Optimization ================================================================================
	var optimizationQueue   = [],
		stopOptimization    = false,
		globalGain          = 0,
		globalOriginalSize  = 0,
		globalOptimizedSize = 0;

	/*
	 * Get the message displayed to the user when (s)he leaves the page.
	 *
	 * @return {string}
	 */
	function imagifyGetConfirmMessage() {
		return imagifyBulk.labels.processing;
	}

	/*
	 * Display an error message in a modal.
	 * It also triggers the event "queue empty".
	 *
	 * @param {string} title The modal title.
	 * @param {string} text  The modal text.
	 * @param {string} title The modal type ("error", "info").
	 */
	function imagifyDisplayError( title, text, type ) {
		title = title || imagifyBulk.labels.error;
		text  = text || '';
		type  = type || 'error';

		swal( {
			title:       title,
			html:        text,
			type:        type,
			customClass: 'imagify-sweet-alert'
		} );

		$( w ).trigger( 'queueEmpty.imagify' );
	}

	/*
	 * Display an error message in a file row.
	 *
	 * @param  {function} $row The row template.
	 * @param  {string}   text The error text.
	 * @return {element}       The row jQuery element.
	 */
	function imagifyDisplayErrorInRow( $row, text ) {
		var $toReplace, colspan;

		$row       = $( $row() );
		$toReplace = $row.find( '.imagify-cell-status ~ td' );
		colspan    = $toReplace.length;

		$toReplace.remove();
		$row.find( '.imagify-cell-status' ).after( '<td colspan="' + colspan + '">' + text + '</td>' );

		return $row;
	}

	/*
	 * Display one of the 3 group headers.
	 *
	 * @param {string}  state      One of the 3 states: 'resting' (it's the "normal" title), 'fetching', and 'optimizing'.
	 * @param {element} $container jQuery element of the group container.
	 */
	function imagifyDisplayGroupHeader( state, $container ) {
		$container = $container.find( '.imagify-bulk-table-title' );
		$container.not( '.imagify-' + state ).imagifyHide();
		$container.filter( '.imagify-' + state ).imagifyShow();
	}

	/*
	 * If the current folder is the last of the group, display the "normal" group header.
	 *
	 * @param {string}  groupId    The folder's group ID.
	 * @param {element} $container jQuery element of the group container.
	 */
	function imagifyMaybeResetGroupHeader( groupId, $table ) {
		var resetHeader = true;

		$.each( optimizationQueue, function( i, otherItem ) {
			if ( otherItem.groupId === groupId ) {
				resetHeader = false;
				return false;
			}
		} );

		if ( resetHeader ) {
			imagifyDisplayGroupHeader( 'resting', $table );
		}
	}

	/*
	 * Display one of the 3 "folder" rows.
	 *
	 * @param {string}  state One of the 3 states: 'resting' (it's the "normal" row), 'waiting' (waiting for other optimizations to finish), and 'working'.
	 * @param {element} $row  jQuery element of the "normal" row.
	 */
	function imagifyDisplayFolderRow( state, $row ) {
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
	}

	/*
	 * Display the share box.
	 */
	function imagifyDisplayShareBox() {
		var text2share = imagifyBulk.labels.textToShare,
			percent, gainHuman, originalSizeHuman,
			$complete;

		if ( ! globalGain || optimizationQueue.length ) {
			globalGain          = 0;
			globalOriginalSize  = 0;
			globalOptimizedSize = 0;
			return;
		}

		percent           = ( 100 - 100 * ( globalOptimizedSize / globalOriginalSize ) ).toFixed( 2 );
		gainHuman         = w.imagify.humanSize( globalGain, 1 );
		originalSizeHuman = w.imagify.humanSize( globalOriginalSize, 1 );

		text2share = text2share.replace( '%1$s', gainHuman );
		text2share = text2share.replace( '%2$s', originalSizeHuman );
		text2share = encodeURIComponent( text2share );

		$complete = $( '.imagify-row-complete' );
		$complete.find( '.imagify-ac-rt-total-gain' ).html( gainHuman );
		$complete.find( '.imagify-ac-rt-total-original' ).html( originalSizeHuman );
		$complete.find( '.imagify-ac-chart' ).attr( 'data-percent', percent );
		$complete.find( '.imagify-sn-twitter' ).attr( 'href', imagifyBulk.labels.twitterShareURL + '&amp;text=' + text2share );

		// Chart.
		drawMeCompleteChart( $complete.find( '.imagify-ac-chart canvas' ) );

		$complete.addClass( 'done' ).imagifyShow();

		$( 'html, body' ).animate( {
			scrollTop: $complete.offset().top
		}, 200 );

		// Reset the stats.
		globalGain          = 0;
		globalOriginalSize  = 0;
		globalOptimizedSize = 0;
	}

	/*
	 * Display/Hide optimization details.
	 */
	$( '.imagify-view-optimization-details' ).on( 'click.imagify open.imagify close.imagify', function( e ) {
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
	} );

	/*
	 * Launch optimization.
	 */
	$( '#imagify-bulk-action' ).on( 'click.imagify', function() {
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
		$w.on( 'beforeunload', imagifyGetConfirmMessage );

		// Hide the "Complete" message.
		$( '.imagify-row-complete' ).imagifyHide( 200 );

		// Close the optimization details.
		$( '.imagify-view-optimization-details' ).trigger( 'close.imagify' );

		// Make sure to reset global vars.
		optimizationQueue   = [];
		stopOptimization    = false;
		globalGain          = 0;
		globalOriginalSize  = 0;
		globalOptimizedSize = 0;

		$checkboxes.each( function() {
			var $checkbox = $( this ),
				$row      = $checkbox.closest( '.imagify-row-folder-type' ),
				groupId   = $row.closest( 'table' ).data( 'group-id' ),
				type      = $checkbox.val(),
				level     = $row.find( '.imagify-cell-level [name="level[' + type + ']"]' ).val();

			// Build the queue.
			optimizationQueue.push( {
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
			imagifyDisplayFolderRow( 'waiting', $row );
		} );

		// Process the queue.
		$w.trigger( 'processQueue.imagify' );
	} );

	/*
	 * Process the first item in the queue.
	 */
	$( w ).on( 'processQueue.imagify', function() {
		var $row, $table, item, url;

		if ( stopOptimization ) {
			return;
		}

		if ( ! optimizationQueue.length ) {
			$( w ).trigger( 'queueEmpty.imagify' );
			return;
		}

		/**
		 * Fetch files for the first folder type in the queue.
		 */
		item   = optimizationQueue.shift();
		$row   = $( '#cb-select-' + item.type ).closest( '.imagify-row-folder-type' );
		$table = $row.closest( 'table' );

		// Display the "Fetching" message in the table header.
		imagifyDisplayGroupHeader( 'fetching', $table );

		// Display the "working" folder row and hide the "normal" one.
		imagifyDisplayFolderRow( 'working', $row );

		// Fetch image IDs.
		url = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&optimization_level=' + item.level;

		if ( 'library' === item.type ) {
			url += '&action=' + imagifyBulk.ajaxActions.libraryFetch;
		} else {
			url += '&action=' + imagifyBulk.ajaxActions.customFolderFetch + '&folder_type=' + item.type;
		}

		$.get( url )
			.done( function( response ) {
				var swal_title = imagifyBulk.labels.error,
					swal_text  = '';

				if ( stopOptimization ) {
					return;
				}

				if ( response.success ) {
					// Optimize the files.
					$( w ).trigger( 'optimizeFiles.imagify', [ item, response.data ] );
					return;
				}

				// Display an error message.
				if ( 'invalid-api-key' === response.data.message ) {
					swal_title = imagifyBulk.labels.invalidAPIKeyTitle;
				} else if ( 'over-quota' === response.data.message ) {
					swal_title = imagifyBulk.labels.overQuotaTitle;
					swal_text  = imagifyBulk.labels.overQuotaText;
				} else if ( 'no-images' === response.data.message ) {
					if ( optimizationQueue.length ) {
						/**
						 * Don't throw an error if not the last in queue.
						 */
						// Uncheck the checkbox.
						$( '#cb-select-' + item.type ).prop( 'checked', false );

						if ( ! stopOptimization ) {
							// Maybe display the "normal" header.
							imagifyMaybeResetGroupHeader( item.groupId, $table );

							$( w ).trigger( 'processQueue.imagify' );
						}

						return;
					}

					swal_title = imagifyBulk.labels.noAttachmentToOptimizeTitle;
					swal_text  = imagifyBulk.labels.noAttachmentToOptimizeText;
				}

				imagifyDisplayError( swal_title, swal_text, 'info' );
			} )
			.fail( function() {
				// Display an error message.
				imagifyDisplayError( imagifyBulk.labels.getUnoptimizedImagesErrorTitle, imagifyBulk.labels.getUnoptimizedImagesErrorText );
			} );
	} );

	/*
	 * Optimize files.
	 */
	$( w ).on( 'optimizeFiles.imagify', function( e, item, files ) {
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

		if ( stopOptimization ) {
			return;
		}

		// Empty the result details.
		$table.find( '.imagify-row-optimization-details tbody' ).text( '' );

		// Display the "Optimizing" message in the table header.
		imagifyDisplayGroupHeader( 'optimizing', $table );

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

				imagifyDrawFileChart( $( '#' + item.groupId + '-' + data.image ).find( '.imagify-cell-percentage canvas' ) ); // Don't use $fileRow, its DOM is not refreshed with the new values.

				// Update the "working" folder row.
				$optimizedCount.text( parseInt( $optimizedCount.text(), 10 ) + 1 );
				return;
			}

			if ( 'already_optimized' === data.error_code ) {
				// The image was already optimized.
				$fileRow.replaceWith( imagifyDisplayErrorInRow( template( $.extend( {}, defaultsTemplate, {
					status:      'complete',
					icon:        'yes',
					label:       imagifyBulk.labels.alreadyOptimized,
					chartSuffix: data.image
				}, data ) ), data.error ) );
				return;
			}

			if ( 'consumed_all_data' === data.error_code ) {
				// No more data, stop optimization.
				if ( ! stopOptimization ) {
					stopOptimization = true;
					Optimizer.stopProcess();

					// Display an alert to warn that all data is consumed.
					imagifyDisplayError( imagifyBulk.labels.overQuotaTitle, imagifyBulk.labels.overQuotaText );
				}
				return;
			}

			// Display the error.
			$fileRow.replaceWith( imagifyDisplayErrorInRow( template( $.extend( {}, defaultsTemplate, {
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
				globalGain          += parseInt( data.global_gain, 10 );
				globalOriginalSize  += parseInt( data.global_original_size, 10 );
				globalOptimizedSize += parseInt( data.global_optimized_size, 10 );
			}

			if ( stopOptimization ) {
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
					imagifyDisplayFolderRow( 'resting', $row );
				} )
				.always( function() {
					$row.removeClass( 'updating' );
				} );

			if ( ! optimizationQueue.length ) {
				$( w ).trigger( 'queueEmpty.imagify' );
			} else {
				// Maybe display the "normal" header.
				imagifyMaybeResetGroupHeader( item.groupId, $table );

				$( w ).trigger( 'processQueue.imagify' );
			}
		} );

		// Run.
		Optimizer.run();
	} );

	/*
	 * End.
	 */
	$( w ).on( 'queueEmpty.imagify', function() {
		var $tables = $( '.imagify-bulk-table' );

		// Display the share box.
		imagifyDisplayShareBox();

		// Reset the queue.
		optimizationQueue = [];

		// Unlink the message displayed when the user wants to quit the page.
		$( w ).off( 'beforeunload', imagifyGetConfirmMessage );

		// Display the "normal" headers.
		imagifyDisplayGroupHeader( 'resting', $tables );

		// Display the "normal" folder rows (the values of the last one should being updated via ajax, don't display it for now).
		imagifyDisplayFolderRow( 'resting', $tables.find( '.imagify-row-folder-type' ).not( '.updating' ) );

		// Reset the progress bars.
		$tables.find( '.imagify-progress-bar' ).removeAttr( 'style' ).find( '.percent' ).html( '0%' );

		// Enable the main button.
		$( '#imagify-bulk-action' ).prop( 'disabled', false ).find( '.dashicons' ).removeClass( 'rotate' );
	} );

} )(jQuery, document, window);


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	// Share links =================================================================================
	var width  = 700,
		height = 290,
		clientLeft, clientTop;

	if ( w.innerWidth ) {
		clientLeft = ( w.innerWidth - width ) / 2;
		clientTop  = ( w.innerHeight - height ) / 2;
	} else {
		clientLeft = ( d.body.clientWidth - width ) / 2;
		clientTop  = ( d.body.clientHeight - height ) / 2;
	}

	[].forEach.call( d.querySelectorAll( '.imagify-share-networks a' ), function( el ) {
		el.addEventListener( 'click', function( evt ) {
			w.open( this.href, '', 'status=no, scrollbars=no, menubar=no, top=' + clientTop + ', left=' + clientLeft + ', width=' + width + ', height=' + height );
			evt.preventDefault();
		}, false );
	} );

} )(jQuery, document, window);
