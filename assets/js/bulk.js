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
		// Folder types in queue.
		queue:                [],
		// Status of each folder type.
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
		// Heartbeat.
		folderTypes:          [],

		// Methods =================================================================================

		/*
		 * Init.
		 */
		init: function () {
			// Overview chart.
			this.drawOverviewChart();

			this.hasMultipleRows = $( '.imagify-bulk-table [name="group[]"]' ).length > 1;

			// Optimization level selector.
			$( '.imagify-level-selector-button' )
				.on( 'click.imagify', this.openLevelSelectorFromButton );

			$( '.imagify-level-selector-list input' )
				.on( 'change.imagify init.imagify', this.syncLevelSelectorFromRadio )
				.filter( ':checked' )
				.trigger( 'init.imagify' );

			$( d )
				.on( 'keypress.imagify click.imagify', this.closeLevelSelectors );

			// Other buttons/UI.
			$( '.imagify-bulk-table [name="group[]"]' ).on( 'change.imagify init.imagify', this.toggleOptimizationButton ).trigger( 'init.imagify' );
			$( '.imagify-show-table-details' ).on( 'click.imagify open.imagify close.imagify', this.toggleOptimizationDetails );
			$( '#imagify-bulk-action' ).on( 'click.imagify', this.maybeLaunchAllProcesses );
			$( '.imagify-share-networks a' ).on( 'click.imagify', this.share );

			// Optimization events.
			$( w )
				.on( 'processQueue.imagify', this.processQueue )
				.on( 'optimizeFiles.imagify', this.optimizeFiles )
				.on( 'queueEmpty.imagify', this.queueEmpty );

			if ( imagifyBulk.ajaxActions.getStats && $( '.imagify-bulk-table [data-group-id="library"][data-context="wp"]' ).length ) {
				// If large library.
				imagifyBulk.heartbeatId = false;
			}

			if ( imagifyBulk.heartbeatId ) {
				$( d )
					.on( 'heartbeat-send', this.addHeartbeat )
					.on( 'heartbeat-tick', this.processHeartbeat );
			}

			// Heartbeat for requirements.
			$( d )
				.on( 'heartbeat-send', this.addRequirementsHeartbeat )
				.on( 'heartbeat-tick', this.processRequirementsHeartbeat );
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
		},

		/*
		 * Get the URL used for ajax requests.
		 *
		 * @param  {string} action An ajax action, or part of it.
		 * @param  {object} item   The current item.
		 * @return {string}
		 */
		getAjaxUrl: function ( action, item ) {
			var camelGroupID = item.groupId.replace( /[\s_-](\S)/g, function( c, l ) {
				return l.toUpperCase();
			} );

			action = action.replace( '%GROUP_ID%', camelGroupID );
			action = imagifyBulk.ajaxActions[ action ];

			return ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&optimization_level=' + item.level + '&action=' + action + '&folder_type=' + item.groupId;
		},

		/**
		 * Get folder types used in the page.
		 *
		 * @return {array}
		 */
		getFolderTypes: function () {
			if ( ! w.imagify.bulk.folderTypes.length ) {
				$( '.imagify-row-folder-type' ).each( function() {
					var $this   = $( this ),
						groupID = $this.data( 'group-id' );

					if ( 'library' === groupID ) {
						groupID += '|' + $this.data( 'context' );
					}

					w.imagify.bulk.folderTypes.push( groupID );
				} );
			}

			return w.imagify.bulk.folderTypes;
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

			w.imagify.bulk.status[ item.groupId ] = {
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

			spinnerTemplate = w.imagify.template( 'imagify-spinner' );
			$newRow.children( '.imagify-cell-checkbox' ).html( spinnerTemplate() ).find( 'svg' ).attr( 'fill', spinnerColor );
			$newRow.children( '.imagify-cell-title' ).html( '<span class="imagify-cell-label">' + $newRow.children( '.imagify-cell-title' ).text() + '</span>' );
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

		/**
		 * Print optimization stats.
		 *
		 * @param {object} data Object containing all Heartbeat IDs.
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
		 * Optimization level selector: on button click, open the dropdown and focus the current radio input.
		 * The dropdown must be open or the focus event won't be triggered.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		openLevelSelectorFromButton: function ( e ) {
			var $list = $( '#' + $( this ).attr( 'aria-controls' ) );
			// Stop click event from bubbling: this will allow to close the selector list if anything else id clicked.
			e.stopPropagation();
			// Close other lists.
			$( '.imagify-level-selector-list' ).not( $list ).attr( 'aria-hidden', 'true' );
			// Open the corresponding list and focus the radio.
			$list.attr( 'aria-hidden', 'false' ).find( ':checked' ).trigger( 'focus.imagify' );
		},

		/*
		 * Optimization level selector: on radio change, make the row "current" and update the button text.
		 */
		syncLevelSelectorFromRadio: function () {
			var $row = $( this ).closest( '.imagify-level-choice' );
			// Update rows attributes.
			$row.addClass( 'imagify-current-level' ).attr( 'aria-current', 'true' ).siblings( '.imagify-level-choice' ).removeClass( 'imagify-current-level' ).attr( 'aria-current', 'false' );
			// Change the button text.
			$row.closest( '.imagify-level-selector' ).find( '.imagify-current-level-info' ).html( $row.find( 'label' ).html() );
		},

		/*
		 * Optimization level selector: on Escape or Enter kaystroke, close the dropdown.
		 *
		 * @param {object} e jQuery's Event object.
		 */
		closeLevelSelectors: function ( e ) {
			if ( 'keypress' === e.type && 27 !== e.keyCode && 13 !== e.keyCode ) {
				return;
			}
			w.imagify.bulk.closeLevelSelector( $( '.imagify-level-selector-list[aria-hidden="false"]' ) );
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

			if ( $infosModal.length ) {
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
					var $row    = $( '.imagify-bulk-table [name="group[]"]:checked' ).first().closest( '.imagify-row-folder-type' ),
						groupId = $row.data( 'group-id' ),
						context = $row.data( 'context' );

					$.get( ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyBulk.ajaxNonce + '&action=' + imagifyBulk.ajaxActions.bulkInfoSeen + '&folder_type=' + groupId + '&context=' + context );
					$infosModal.remove();

					w.imagify.bulk.launchAllProcesses();
				} ).catch( swal.noop );
			} else {
				w.imagify.bulk.launchAllProcesses();
			}
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
			this.queue                = [];
			this.status               = {};
			this.displayedWaitMessage = false;
			this.processIsStopped     = false;
			this.globalGain           = 0;
			this.globalOriginalSize   = 0;
			this.globalOptimizedSize  = 0;

			$( '.imagify-bulk-table [name="group[]"]:checked' ).each( function() {
				var $checkbox = $( this ),
					$row      = $checkbox.closest( '.imagify-row-folder-type' ),
					groupId   = $row.data( 'group-id' ),
					context   = $row.data( 'context' ),
					level     = $row.find( '.imagify-cell-level [name="level[' + groupId + ']"]:checked' ).val();

				// Build the queue.
				w.imagify.bulk.queue.push( {
					groupId: groupId,
					context: context,
					level:   undefined === level ? -1 : parseInt( level, 10 )
				} );

				// Set the status.
				w.imagify.bulk.status[ groupId ] = {
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

			if ( ! w.imagify.bulk.queue.length ) {
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
			item = w.imagify.bulk.queue.shift();
			$row = $( '#cb-select-' + item.groupId ).closest( '.imagify-row-folder-type' );

			// Update status.
			w.imagify.bulk.status[ item.groupId ].id = 'fetching';

			// Display the "working" folder row and hide the "normal" one.
			w.imagify.bulk.displayFolderRow( 'working', $row );

			// Fetch image IDs.
			$.get( w.imagify.bulk.getAjaxUrl( '%GROUP_ID%Fetch', item ) )
				.done( function( response ) {
					if ( w.imagify.bulk.processIsStopped ) {
						return;
					}

					swal.close();

					// Success.
					if ( response.success && ( $.isArray( response.data ) || $.isPlainObject( response.data ) ) ) { // Array if empty, object otherwize.
						if ( ! $.isEmptyObject( response.data ) ) {
							// Optimize the files.
							$( w ).trigger( 'optimizeFiles.imagify', [ item, response.data ] );
							return;
						}

						// No images.
						w.imagify.bulk.status[ item.groupId ].id = 'no-images';

						if ( ! w.imagify.bulk.processIsStopped ) {
							if ( w.imagify.bulk.hasMultipleRows ) {
								$( '#cb-select-' + item.groupId ).prop( 'checked', false );
							}

							if ( ! w.imagify.bulk.queue.length ) {
								$( w ).trigger( 'queueEmpty.imagify' );
								return;
							}

							// Reset the folder row.
							w.imagify.bulk.displayFolderRow( 'resting', $row );

							$( w ).trigger( 'processQueue.imagify' );
						}
						return;
					}

					// Error.
					w.imagify.bulk.stopProcess( response.data.message, item );
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
		 * @param {object} item  Current item (from the queue).
		 * @param {object} files A list of file IDs (key) and URLs (values).
		 */
		optimizeFiles: function ( e, item, files ) {
			var $row             = $( '#cb-select-' + item.groupId ).closest( '.imagify-row-folder-type' ),
				$workingRow      = $row.next( '.imagify-row-working' ),
				$optimizedCount  = $workingRow.find( '.imagify-cell-images-optimized span' ),
				optimizedCount   = parseInt( $optimizedCount.text(), 10 ),
				$errorsCount     = $workingRow.find( '.imagify-cell-errors span' ),
				errorsCount      = parseInt( $errorsCount.text(), 10 ),
				$table           = $row.closest( '.imagify-bulk-table' ),
				$progressBar     = $table.find( '.imagify-row-progress' ),
				$progress        = $progressBar.find( '.bar' ),
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
				Optimizer, $resultsContainer;

			if ( w.imagify.bulk.processIsStopped ) {
				return;
			}

			// Update folder status.
			w.imagify.bulk.status[ item.groupId ].id = 'optimizing';

			// Fill in the result table header.
			$table.find( '.imagify-bulk-table-details thead' ).html( $( '#tmpl-imagify-file-header-' + item.groupId ).html() );

			// Empty the result table body.
			$resultsContainer = $table.find( '.imagify-bulk-table-details tbody' ).text( '' );

			// Reset and display the progress bar.
			$progress.css( 'width', '0%' ).find( '.percent' ).text( '0%' );
			$progressBar.slideDown().attr( 'aria-hidden', 'false' );

			// Optimize the files.
			Optimizer = new ImagifyGulp( {
				'buffer_size': imagifyBulk.bufferSizes[ item.context ] || 1,
				'lib':         w.imagify.bulk.getAjaxUrl( '%GROUP_ID%Optimize', item ),
				'images':      files,
				'context':     item.context
			} );

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

				// Update the progress bar.
				$progress.css( 'width', data.progress + '%' ).find( '.percent' ).html( data.progress + '%' );

				if ( data.success ) {
					// Image successfully optimized.
					$fileRow.replaceWith( template( $.extend( {}, defaultsTemplate, {
						status:      'complete',
						icon:        'yes',
						label:       imagifyBulk.labels.complete,
						chartSuffix: data.image
					}, data ) ) );

					w.imagify.bulk.drawFileChart( $( '#' + item.groupId + '-' + data.image ).find( '.imagify-cell-percentage canvas' ) ); // Don't use $fileRow, its DOM is not refreshed with the new values.

					// Update the optimized images counter.
					optimizedCount += 1;
					$optimizedCount.text( optimizedCount );
					return;
				}

				if ( 'already-optimized' === data.error_code ) {
					// The image was already optimized.
					$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, {
						status:      'complete',
						icon:        'yes',
						label:       imagifyBulk.labels.alreadyOptimized,
						chartSuffix: data.image
					}, data ) ), data.error ) );

					// Update the optimized images counter.
					optimizedCount += 1;
					$optimizedCount.text( optimizedCount );
					return;
				}

				// Display the error in the file row.
				$fileRow.replaceWith( w.imagify.bulk.displayErrorInRow( template( $.extend( {}, defaultsTemplate, {
					status:      'error',
					icon:        'dismiss',
					label:       imagifyBulk.labels.error,
					chartSuffix: data.image
				}, data ) ), data.error || data ) );

				// Update the "working" folder row.
				if ( ! $errorsCount.length ) {
					errorsCount  = 1;
					$errorsCount = $workingRow.find( '.imagify-cell-errors' ).html( imagifyBulk.labels.imagesErrorText.replace( '%s', '<span>1</span>' ) ).find( 'span' );
				} else {
					errorsCount += 1;
					$errorsCount.text( errorsCount );
				}

				if ( 'over-quota' === data.error_code ) {
					// No more data, stop everything.
					Optimizer.stopProcess();
					w.imagify.bulk.stopProcess( data.error_code, item );
				}
			} );

			// After all image optimizations.
			Optimizer.done( function( data ) {
				// Uncheck the checkbox.
				if ( w.imagify.bulk.hasMultipleRows ) {
					$( '#cb-select-' + item.groupId ).prop( 'checked', false );
				}

				if ( data.global_original_size ) {
					w.imagify.bulk.globalGain          += parseInt( data.global_gain, 10 );
					w.imagify.bulk.globalOriginalSize  += parseInt( data.global_original_size, 10 );
					w.imagify.bulk.globalOptimizedSize += parseInt( data.global_optimized_size, 10 );
				}

				if ( w.imagify.bulk.processIsStopped ) {
					return;
				}

				// Update folder type status.
				if ( ! w.imagify.bulk.status[ item.groupId ].isError ) {
					w.imagify.bulk.status[ item.groupId ].id = 'done';
				}

				// Update the folder row.
				$row.addClass( 'updating' );

				$.get( w.imagify.bulk.getAjaxUrl( 'getFolderData', item ) )
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

						if ( ! w.imagify.bulk.queue.length ) {
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
				noImages  = true;

			// Display the share box.
			w.imagify.bulk.displayShareBox();

			// Reset the queue.
			w.imagify.bulk.queue = [];

			// Fetch and display generic stats if heartbeat is disabled.
			if ( ! imagifyBulk.heartbeatId ) {
				$.get( ajaxurl, {
					_wpnonce: imagifyBulk.ajaxNonce,
					action:   imagifyBulk.ajaxActions.getStats,
					types:    w.imagify.bulk.getFolderTypes()
				} )
					.done( function( response ) {
						if ( response.success ) {
							w.imagify.bulk.updateStats( response.data );
						}
					} );
			}

			// Maybe display error.
			if ( ! $.isEmptyObject( w.imagify.bulk.status ) ) {
				$.each( w.imagify.bulk.status, function( groupId, typeStatus ) {
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
					w.imagify.bulk.displayError( {
						title: imagifyBulk.labels.noAttachmentToOptimizeTitle,
						html:  imagifyBulk.labels.noAttachmentToOptimizeText,
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
		 * Add our Heartbeat ID on "heartbeat-send" event.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		addHeartbeat: function ( e, data ) {
			data.imagify_ids = data.imagify_ids || {};
			data.imagify_ids[ imagifyBulk.heartbeatId ] = 1;

			data.imagify_types = w.imagify.bulk.getFolderTypes();
		},

		/**
		 * Listen for the custom event "heartbeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		processHeartbeat: function ( e, data ) {
			if ( data.imagify_bulk_data ) {
				w.imagify.bulk.updateStats( data.imagify_bulk_data );
			}
		},

		/**
		 * Add our Heartbeat ID for requirements on "heartbeat-send" event.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		addRequirementsHeartbeat: function ( e, data ) {
			data.imagify_ids = data.imagify_ids || {};
			data.imagify_ids[ imagifyBulk.reqsHeartbeatId ] = 1;
		},

		/**
		 * Listen for the custom event "heartbeat-tick" on $(document).
		 * It allows to update requirements status periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Heartbeat IDs.
		 */
		processRequirementsHeartbeat: function ( e, data ) {
			if ( ! data.imagify_bulk_requirements ) {
				return;
			}

			data = data.imagify_bulk_requirements;

			imagifyBulk.curlMissing    = data.curl_missing;
			imagifyBulk.editorMissing  = data.editor_missing;
			imagifyBulk.extHttpBlocked = data.external_http_blocked;
			imagifyBulk.apiDown        = data.api_down;
			imagifyBulk.keyIsValid     = data.key_is_valid;
			imagifyBulk.isOverQuota    = data.is_over_quota;
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
