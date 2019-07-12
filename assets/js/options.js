window.imagify = window.imagify || {};

(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names
	/*
	 * Process an API key check validity.
	 */
	var busy = false,
		xhr  = false;

	$( '#imagify-settings #api_key' ).on( 'blur.imagify', function() {
		var obj   = $( this ),
			value = obj.val();

		if ( $.trim( value ) === '' ) {
			return false;
		}

		if ( $( '#check_api_key' ).val() === value ) {
			$( '#imagify-check-api-container' ).html( '<span class="dashicons dashicons-yes"></span> ' + imagifyOptions.labels.ValidApiKeyText );
			return false;
		}

		if ( true === busy ) {
			xhr.abort();
		} else {
			$( '#imagify-check-api-container' ).remove();
			obj.after( '<span id="imagify-check-api-container"><span class="imagify-spinner"></span>' + imagifyOptions.labels.waitApiKeyCheckText + '</span>' );
		}

		busy = true;

		xhr = $.get( ajaxurl + w.imagify.concat + 'action=imagify_check_api_key_validity&api_key=' + obj.val() + '&imagifycheckapikeynonce=' + $( '#imagifycheckapikeynonce' ).val() )
			.done( function( response ) {
				if ( ! response.success ) {
					$( '#imagify-check-api-container' ).html( '<span class="dashicons dashicons-no"></span> ' + response.data );
				} else {
					// Success, the API key is valid.
					$( '#imagify-check-api-container' ).remove();
					swal( {
						title:       imagifyOptions.labels.ApiKeyCheckSuccessTitle,
						html:        imagifyOptions.labels.ApiKeyCheckSuccessText,
						type:        'success',
						padding:     0,
						customClass: 'imagify-sweet-alert'
					} ).then( function() {
						location.reload();
					} );
				}

				busy = false;
			} );
	} );

	/**
	 * Check the boxes by clicking "labels" (aria-describedby items).
	 */
	$( '.imagify-options-line' ).css( 'cursor', 'pointer' ).on( 'click.imagify', function( e ) {
		if ( 'INPUT' === e.target.nodeName ) {
			return;
		}
		$( 'input[aria-describedby="' + $( this ).attr( 'id' ) + '"]' ).trigger( 'click.imagify' );
	} );

	$( '.imagify-settings th span' ).on( 'click.imagify', function() {
		var $input = $( this ).parent().next( 'td' ).find( ':checkbox' );

		if ( 1 === $input.length ) {
			$input.trigger( 'click.imagify' );
		}
	} );

	/**
	 * Auto check on options-line input value change.
	 */
	$( '.imagify-options-line' ).find( 'input' ).on( 'change.imagify focus.imagify', function() {
		var $checkbox;

		if ( 'checkbox' === this.type && ! this.checked ) {
			return;
		}

		$checkbox = $( this ).closest( '.imagify-options-line' ).prev( 'label' ).prev( ':checkbox' );

		if ( $checkbox.length && ! $checkbox[0].checked ) {
			$checkbox.prop( 'checked', true );
		}
	} );

	/**
	 * Imagify Backup alert.
	 */
	$( '[name="imagify_settings[backup]"]' ).on( 'change.imagify', function() {
		var $_this         = $( this ),
			$backupMessage = $_this.siblings( '#backup-dir-is-writable' ),
			params         = {
				'action':   'imagify_check_backup_dir_is_writable',
				'_wpnonce': $backupMessage.data( 'nonce' )
			};

		if ( $_this.is( ':checked' ) ) {
			$.getJSON( ajaxurl, params )
				.done( function( r ) {
					if ( $.isPlainObject( r ) && r.success ) {
						if ( r.data.is_writable ) {
							// Hide the error message.
							$backupMessage.addClass( 'hidden' );
						} else {
							// Show the error message.
							$backupMessage.removeClass( 'hidden' );
						}
					}
				} );
			return;
		}

		// Are you sure? No backup?
		swal( {
			title:            imagifyOptions.labels.noBackupTitle,
			html:             imagifyOptions.labels.noBackupText,
			type:             'warning',
			customClass:      'imagify-sweet-alert',
			padding:          0,
			showCancelButton: true,
			cancelButtonText: imagifySwal.labels.cancelButtonText,
			reverseButtons:   true
		} ).then(
			function() {
				// Leave it unchecked, hide the error message.
				$backupMessage.addClass( 'hidden' );
			},
			function() {
				// Re-check.
				$_this.prop( 'checked', true );
			}
		);
	} );

	/**
	 * Fade CDN URL field.
	 */
	$( '[name="imagify_settings[display_webp_method]"]' ).on( 'change.imagify init.imagify', function( e ) {
		if ( 'picture' === e.target.value ) {
			$( e.target ).closest( '.imagify-radio-group' ).next( '.imagify-options-line' ).removeClass( 'imagify-faded' );
		} else {
			$( e.target ).closest( '.imagify-radio-group' ).next( '.imagify-options-line' ).addClass( 'imagify-faded' );
		}
	} ).filter( ':checked' ).trigger( 'init.imagify' );

} )(jQuery, document, window);


// Display Imagify User data =======================================================================
(function(w, d, $, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	if ( ! w.imagifyUser ) {
		return;
	}

	$.getJSON( ajaxurl, w.imagifyUser )
		.done( function( r ) {
			if ( $.isPlainObject( r ) && r.success ) {
				r.data.id      = null;
				r.data.plan_id = null;
				r.data.is      = [];

				$.each( r.data, function( k, v ) {
					var htmlClass = '.imagify-user-' + k.replace( /_/g, '-' );

					if ( k.indexOf( 'is_' ) === 0 ) {
						if ( v ) {
							r.data.is.push( htmlClass );
						}
					} else if ( 'is' !== k ) {
						$( htmlClass ).text( v );
					}
				} );

				r.data.is.push( 'best-plan' );

				$( r.data.is.join( ',' ) ).removeClass( 'hidden' );
			}
		} );

} )(window, document, jQuery);


// Files tree for "custom folders" =================================================================
(function(w, d, $, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	if ( ! imagifyOptions.getFilesTree ) {
		return;
	}

	function imagifyInsertFolderRow( value ) {
		var added    = false,
			prevPath = null,
			valueTest, template, $wrap, $rows, $field;

		if ( ! value ) {
			return;
		}

		$wrap  = $( '#imagify-custom-folders-selected' );
		$rows  = $wrap.find( '.imagify-custom-folder-line' );
		$field = $rows.find( '[value="' + value + '"]' );

		if ( $field.length ) {
			// Shouldn't happen.
			return;
		}

		// Path #///# Label.
		value     = value.split( '#///#' );
		valueTest = value[1].replace( /\/+$/,'' ).toLowerCase();
		template  = w.imagify.template( 'imagify-custom-folder' );

		$rows.each( function() {
			var $this         = $( this ),
				thisValueTest = $this.data( 'path' ).replace( /\/+$/,'' ).toLowerCase();

			if ( '' !== thisValueTest && valueTest.indexOf( thisValueTest ) === 0 ) {
				// We try to add a sub-folder of an already selected folder. It shouldn't happen though, since it can't be selected.
				added = true;
				return false;
			} else if ( valueTest < thisValueTest ) {
				$this.before( template( {
					value: value[0],
					label: value[1]
				} ) );
				$rows = $wrap.find( '.imagify-custom-folder-line' );
				added = true;
				return false;
			}
		} );

		if ( ! added ) {
			$wrap.append( template( {
				value: value[0],
				label: value[1]
			} ) );
			$rows = $wrap.find( '.imagify-custom-folder-line' );
		}

		// Remove sub-paths: if 'a/b/' and 'a/b/c/' are in the array, we keep only the "parent" 'a/b/'.
		if ( '' !== valueTest ) {
			$rows.each( function() {
				var $this    = $( this ),
					thisPath = $this.data( 'path' ).toLowerCase();

				if ( null !== prevPath && thisPath.indexOf( prevPath ) === 0 ) {
					$this.find( '.imagify-custom-folders-remove' ).trigger( 'click.imagify' );
				} else {
					prevPath = thisPath;
				}
			} );
		}

		// Display a message.
		$wrap.next( '.hidden' ).removeClass( 'hidden' );
	}

	// Clicking the main button: fetch site's root folders and files, then display them in a modal.
	$( '#imagify-add-custom-folder' ).on( 'click.imagify', function() {
		var $button  = $( this ),
			selected = [],
			$folders;

		if ( $button.attr( 'disabled' ) ) {
			return;
		}

		$button.attr( 'disabled', 'disabled' ).next( 'img' ).attr( 'aria-hidden', 'false' );

		$folders = $( '#imagify-custom-folders-selected' );

		$folders.find( 'input' ).each( function() {
			selected.push( this.value );
		} );

		$.post( imagifyOptions.getFilesTree, {
			folder:   '/',
			selected: selected
		}, null, 'json' )
			.done( function( response ) {
				if ( ! response.success ) {
					swal( {
						title:       imagifyOptions.labels.error,
						html:        response.data || '',
						type:        'error',
						padding:     0,
						customClass: 'imagify-sweet-alert'
					} );
					return;
				}

				swal( {
					title:             imagifyOptions.labels.filesTreeTitle,
					html:              '<div class="imagify-swal-subtitle">' + imagifyOptions.labels.filesTreeSubTitle + '</div><div class="imagify-swal-content"><p class="imagify-folders-information"><i class="dashicons dashicons-info" aria-hidden="true"></i>' + imagifyOptions.labels.cleaningInfo + '</p><ul id="imagify-folders-tree" class="imagify-folders-tree">' + response.data + '</ul></div>',
					type:              '',
					customClass:       'imagify-sweet-alert imagify-swal-has-subtitle  imagify-folders-selection',
					showCancelButton:  true,
					padding:           0,
					confirmButtonText: imagifyOptions.labels.confirmFilesTreeBtn,
					cancelButtonText:  imagifySwal.labels.cancelButtonText,
					reverseButtons:    true
				} ).then( function() {
					var values = $( '#imagify-folders-tree input' ).serializeArray(); // Don't do `$( '#imagify-folders-tree' ).find( 'input' )`, it won't work.

					if ( ! values.length ) {
						return;
					}

					$.each( values, function( i, v ) {
						imagifyInsertFolderRow( v.value );
					} );
				} ).catch( swal.noop );
			} )
			.fail( function() {
				swal( {
					title:       imagifyOptions.labels.error,
					type:        'error',
					customClass: 'imagify-sweet-alert',
					padding:     0
				} );
			} )
			.always( function(){
				$button.removeAttr( 'disabled' ).next( 'img' ).attr( 'aria-hidden', 'true' );
			} );
	} );

	// Clicking a folder icon in the modal: fetch the folder's sub-folders and files, then display them.
	$( d ).on( 'click.imagify', '#imagify-folders-tree [data-folder]', function() {
		var $button  = $( this ),
			$tree    = $button.nextAll( '.imagify-folders-sub-tree' ),
			selected = [];

		if ( $button.attr( 'disabled' ) || $button.siblings( ':checkbox' ).is( ':checked' ) ) {
			return;
		}

		$button.attr( 'disabled', 'disabled' ).addClass( 'imagify-loading' );

		if ( $tree.length ) {
			if ( $button.hasClass( 'imagify-is-open' ) ) {
				$tree.addClass( 'hidden' );
				$button.removeClass(' imagify-is-open' );
			} else {
				$tree.removeClass( 'hidden' );
				$button.addClass( 'imagify-is-open' );
			}
			$button.removeAttr( 'disabled' ).removeClass( 'imagify-loading' );
			return;
		}

		$( '#imagify-custom-folders-selected' ).find( 'input' ).each( function() {
			selected.push( this.value );
		} );

		$.post( imagifyOptions.getFilesTree, {
			folder:   $button.data( 'folder' ),
			selected: selected
		}, null, 'json' )
			.done( function( response ) {
				if ( ! response.success ) {
					swal( {
						title:       imagifyOptions.labels.error,
						html:        response.data || '',
						type:        'error',
						padding:     0,
						customClass: 'imagify-sweet-alert'
					} );
					return;
				}

				$button.addClass( 'imagify-is-open' ).parent().append( '<ul class="imagify-folders-sub-tree">' + response.data + '</ul>' );
			} )
			.fail( function(){
				swal( {
					title:       imagifyOptions.labels.error,
					type:        'error',
					padding:     0,
					customClass: 'imagify-sweet-alert'
				} );
			} )
			.always( function(){
				$button.removeAttr( 'disabled' ).removeClass( 'imagify-loading' );
			} );
	} );

	// Clicking a Remove folder button make it disappear.
	$( '#imagify-custom-folders' ).on( 'click.imagify', '.imagify-custom-folders-remove', function() {
		var $row = $( this ).closest( '.imagify-custom-folder-line' ).addClass( 'imagify-will-remove' );

		w.setTimeout( function() {
			$row.remove();
			// Display a message.
			$( '#imagify-custom-folders-selected' ).siblings( '.imagify-success.hidden' ).removeClass( 'hidden' );
		}, 750 );
	} );

	// Clicking the "add themes to folders" button.
	$( '#imagify-add-themes-to-custom-folder' ).on( 'click.imagify', function() {
		var $this = $( this );

		imagifyInsertFolderRow( $this.data( 'theme' ) );
		imagifyInsertFolderRow( $this.data( 'theme-parent' ) );

		// Remove clicked button.
		$this.replaceWith( '<p>' + imagifyOptions.labels.themesAdded + '</p>' );
	} );

} )(window, document, jQuery);


// Generate missing webp versions ==================================================================
/* eslint-disable no-underscore-dangle, consistent-this */
(function(w, d, $, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	if ( ! imagifyOptions.bulk ) {
		return;
	}

	w.imagify.optionsBulk = {
		// Properties ==============================================================================
		/**
		 * When media IDs have been fetched for a context (or tried, with error), the context is removed from this list.
		 *
		 * @var {array} fetchQueue An array of contexts.
		 */
		fetchQueue:       [],
		/**
		 * Contexts in queue.
		 *
		 * @var {array} queue An array of objects: {
		 *     @type {string} context     The context, like 'wp'.
		 *     @type {string} optimizeURL The URL to ping to optimize a file.
		 *     @type {array}  mediaIDs    A list of media IDs.
		 * }
		 */
		queue:            [],
		/**
		 * List of medias being processed.
		 *
		 * @var {array} processingQueue An array of objects: {
		 *     @type {string} context The context, like 'wp'.
		 *     @type {int}    mediaID The media ID.
		 * }
		 */
		processingQueue:  [],
		/**
		 * Stores the first error ID or message that is occurring when fetching media IDs.
		 *
		 * @var {string|bool} error False if no error.
		 */
		fetchError:       false,
		/**
		 * The current error ID or message.
		 *
		 * @var {string|bool} error False if no error.
		 */
		error:            false,
		/**
		 * Set to true at the beginning of the process.
		 *
		 * @var {bool} working
		 */
		working:          false,
		/**
		 * Set to true to stop the whole thing.
		 *
		 * @var {bool} processIsStopped
		 */
		processIsStopped: true,
		/**
		 * Total number of processed media.
		 *
		 * @var {int}
		 */
		processedMedia:   0,
		/**
		 * Total number of media to process.
		 *
		 * @var {int}
		 */
		totalMedia:       0,
		/**
		 * The button.
		 *
		 * @var {jQuery}
		 */
		$button:          null,
		/**
		 * The progress bar wrapper.
		 *
		 * @var {jQuery}
		 */
		$progressWrap:    null,
		/**
		 * The progress bar.
		 *
		 * @var {jQuery}
		 */
		$progressBar:     null,
		/**
		 * The progress bar text (the %).
		 *
		 * @var {jQuery}
		 */
		$progressText:    null,

		// Methods =================================================================================

		/*
		 * Init.
		 */
		init: function () {
			this.$button       = $( '#imagify-generate-webp-versions' );
			this.$progressWrap = this.$button.siblings( '.imagify-progress' );
			this.$progressBar  = this.$progressWrap.find( '.bar' );
			this.$progressText = this.$progressBar.find( '.percent' );

			// Enable/Disable the button when the "Convert to webp" checkbox is checked/unchecked.
			$( '#imagify_convert_to_webp' )
				.on( 'change.imagify init.imagify', { imagifyOptionsBulk: this }, this.toggleButton )
				.trigger( 'init.imagify' );

			// Launch optimization.
			this.$button.on( 'click.imagify', { imagifyOptionsBulk: this }, this.maybeLaunchAllProcesses );

			// Imagifybeat for optimization queue.
			$( d )
				.on( 'imagifybeat-send', { imagifyOptionsBulk: this }, this.addQueueImagifybeat )
				.on( 'imagifybeat-tick', { imagifyOptionsBulk: this }, this.processQueueImagifybeat )
			// Imagifybeat for requirements.
				.on( 'imagifybeat-send', this.addRequirementsImagifybeat )
				.on( 'imagifybeat-tick', { imagifyOptionsBulk: this }, this.processRequirementsImagifybeat );
		},

		// Event callbacks =========================================================================

		/**
		 * Enable/Disable the button when the "Convert to webp" checkbox is checked/unchecked.
		 *
		 * @param {object} e Event object.
		 */
		toggleButton: function ( e ) {
			if ( ! this.checked ) {
				e.data.imagifyOptionsBulk.$button.attr( 'disabled', 'disabled' );
			} else {
				e.data.imagifyOptionsBulk.$button.removeAttr( 'disabled' );
			}
		},

		/*
		 * Build the queue and launch all processes.
		 *
		 * @param {object} e Event object.
		 */
		maybeLaunchAllProcesses: function ( e ) {
			if ( ! e.data.imagifyOptionsBulk || e.data.imagifyOptionsBulk.working ) {
				return;
			}

			if ( e.data.imagifyOptionsBulk.hasBlockingError( true ) ) {
				return;
			}

			// Reset properties.
			e.data.imagifyOptionsBulk.fetchQueue       = imagifyOptions.bulk.contexts.slice();
			e.data.imagifyOptionsBulk.queue            = [];
			e.data.imagifyOptionsBulk.processingQueue  = [];
			e.data.imagifyOptionsBulk.fetchError       = false;
			e.data.imagifyOptionsBulk.error            = false;
			e.data.imagifyOptionsBulk.working          = true;
			e.data.imagifyOptionsBulk.processIsStopped = false;
			e.data.imagifyOptionsBulk.processedMedia   = 0;
			e.data.imagifyOptionsBulk.totalMedia       = 0;

			// Disable the button.
			e.data.imagifyOptionsBulk.$button.attr( 'disabled', 'disabled' ).find( '.dashicons' ).addClass( 'rotate' );

			// Add a message to be displayed when the user wants to quit the page.
			$( w ).on( 'beforeunload.imagify', e.data.imagifyOptionsBulk.getConfirmMessage );

			// Fasten Imagifybeat: 1 tick every 15 seconds, and disable suspend.
			w.imagify.beat.interval( 15 );
			w.imagify.beat.disableSuspend();

			// Fetch IDs of media to optimize.
			e.data.imagifyOptionsBulk.fetchIDs();
		},

		/*
		 * Get the message displayed to the user when (s)he leaves the page.
		 *
		 * @return {string}
		 */
		getConfirmMessage: function () {
			return imagifyOptions.bulk.labels.processing;
		},

		// Imagifybeat =============================================================================

		/**
		 * Add a Imagifybeat ID on "imagifybeat-send" event to sync the optimization queue.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addQueueImagifybeat: function ( e, data ) {
			if ( e.data.imagifyOptionsBulk && e.data.imagifyOptionsBulk.processingQueue.length ) {
				data[ imagifyOptions.bulk.imagifybeatIDs.queue ] = e.data.imagifyOptionsBulk.processingQueue;
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
			if ( e.data.imagifyOptionsBulk && typeof data[ imagifyOptions.bulk.imagifybeatIDs.queue ] !== 'undefined' ) {
				$.each( data[ imagifyOptions.bulk.imagifybeatIDs.queue ], function ( i, mediaData ) {
					e.data.imagifyOptionsBulk.mediaProcessed( mediaData );
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
			data[ imagifyOptions.bulk.imagifybeatIDs.requirements ] = 1;
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update requirements status periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processRequirementsImagifybeat: function ( e, data ) {
			if ( e.data.imagifyOptionsBulk && typeof data[ imagifyOptions.bulk.imagifybeatIDs.requirements ] === 'undefined' ) {
				return;
			}

			data = data[ imagifyOptions.bulk.imagifybeatIDs.requirements ];

			imagifyOptions.bulk.curlMissing    = data.curl_missing;
			imagifyOptions.bulk.editorMissing  = data.editor_missing;
			imagifyOptions.bulk.extHttpBlocked = data.external_http_blocked;
			imagifyOptions.bulk.apiDown        = data.api_down;
			imagifyOptions.bulk.keyIsValid     = data.key_is_valid;
			imagifyOptions.bulk.isOverQuota    = data.is_over_quota;
		},

		// Optimization ============================================================================

		/*
		 * Fetch IDs of media to optimize.
		 */
		fetchIDs: function () {
			var _this, context;

			if ( this.processIsStopped ) {
				return;
			}

			if ( ! this.fetchQueue.length ) {
				// No more IDs to fetch.
				if ( this.queue.length ) {
					// We have files to process.
					// Reset and display the progress bar.
					this.$progressBar.removeAttr( 'style' );
					this.$progressText.text( '0' + ( this.totalMedia ? '/' + this.totalMedia : '' ) );
					this.$progressWrap.slideDown().attr( 'aria-hidden', 'false' );

					this.processQueue();
					return;
				}

				if ( ! this.fetchError ) {
					// No files to process.
					this.fetchError = 'no-images';
				}

				// Error, or no files to process.
				this.stopProcess( this.fetchError );
				this.fetchError = false;
				return;
			}

			// Fetch IDs for the next context.
			_this   = this;
			context = this.fetchQueue.shift();

			$.get( this.getAjaxUrl( 'getMediaIds', context ) )
				.done( function( response ) {
					var errorMessage;

					if ( _this.processIsStopped ) {
						return;
					}

					if ( response.data && response.data.message ) {
						errorMessage = response.data.message;
					} else {
						errorMessage = imagifyOptions.bulk.ajaxErrorText;
					}

					if ( ! response.success ) {
						// Error.
						if ( ! _this.fetchError ) {
							_this.fetchError = errorMessage;
						}
						return;
					}

					if ( ! $.isArray( response.data ) ) {
						// Error: should be an array.
						if ( ! _this.fetchError ) {
							_this.fetchError = errorMessage;
						}
						return;
					}

					if ( ! response.data.length ) {
						// No media to process.
						return;
					}

					// Success.
					_this.totalMedia += response.data.length;
					_this.queue.push( {
						context:     context,
						optimizeURL: _this.getAjaxUrl( 'bulkProcess', context ),
						mediaIDs:    response.data
					} );
				} )
				.fail( function() {
					// Error.
					if ( ! _this.fetchError ) {
						_this.fetchError = 'get-unoptimized-images';
					}
				} )
				.always( function() {
					// Fetch IDs for the next context.
					_this.fetchIDs();
				} );
		},

		/*
		 * Fill the processing queue until the buffer size is reached.
		 */
		processQueue: function () {
			var _this = this;

			if ( this.processIsStopped ) {
				return;
			}

			if ( ! this.queue.length && ! this.processingQueue.length ) {
				return;
			}

			// Optimize the files.
			$.each( this.queue, function ( i, item ) {
				if ( _this.processingQueue.length >= imagifyOptions.bulk.bufferSize ) {
					return false;
				}

				$.each( item.mediaIDs, function () {
					_this.processMedia( {
						context:     item.context,
						mediaID:     item.mediaIDs.shift(),
						optimizeURL: item.optimizeURL
					} );

					if ( ! item.mediaIDs.length ) {
						_this.queue.shift();
					}

					if ( _this.processingQueue.length >= imagifyOptions.bulk.bufferSize ) {
						return false;
					}
				} );
			} );
		},

		/*
		 * Process the next media.
		 *
		 * @param {object} item {
		 *     @type {string} context     The context, like 'wp'.
		 *     @type {int}    mediaID     The media ID.
		 *     @type {string} optimizeURL The URL to ping to optimize the media.
		 * }
		 */
		processMedia: function ( item ) {
			var _this           = this,
				defaultResponse = {
					context: item.context,
					mediaID: item.mediaID
				};

			this.processingQueue.push( {
				context: item.context,
				mediaID: item.mediaID
			} );

			$.post( {
				url:      item.optimizeURL,
				data:     {
					media_id: item.mediaID,
					context:  item.context
				},
				dataType: 'json'
			} )
				.done( function( response ) {
					if ( response.success ) {
						// Processing.
						return;
					}

					// Error.
					_this.mediaProcessed( defaultResponse );
				} )
				.fail( function() {
					// Error.
					_this.mediaProcessed( defaultResponse );
				} );
		},

		/**
		 * After a media has been processed.
		 *
		 * @param {object} response {
		 *     The response:
		 *
		 *     @type {int}    mediaID The media ID.
		 *     @type {string} context The context.
		 * }
		 */
		mediaProcessed: function( response ) {
			var _this = this;

			if ( this.processIsStopped ) {
				return;
			}

			// Remove this media from the "being processed" list.
			$.each( this.processingQueue, function( i, mediaData ) {
				if ( response.context === mediaData.context && response.mediaID === mediaData.mediaID ) {
					_this.processingQueue.splice( i, 1 );
					return false;
				}
			} );

			++this.processedMedia;

			// Update the progress bar.
			response.progress = Math.floor( this.processedMedia / this.totalMedia * 100 );
			this.$progressBar.css( 'width', response.progress + '%' );
			this.$progressText.text( this.processedMedia + '/' + this.totalMedia );

			if ( this.queue.length || this.processingQueue.length ) {
				this.processQueue();
			} else if ( this.totalMedia === this.processedMedia ) {
				this.queueEmpty();
			}
		},

		/*
		 * End.
		 */
		queueEmpty: function () {
			var errorArgs = {};

			// Maybe display an error.
			if ( false !== this.error ) {
				if ( 'invalid-api-key' === this.error ) {
					errorArgs = {
						title: imagifyOptions.bulk.labels.invalidAPIKeyTitle,
						type:  'info'
					};
				}
				else if ( 'over-quota' === this.error ) {
					errorArgs = {
						title:             imagifyOptions.bulk.labels.overQuotaTitle,
						html:              $( '#tmpl-imagify-overquota-alert' ).html(),
						type:              'info',
						customClass:       'imagify-swal-has-subtitle imagify-swal-error-header',
						showConfirmButton: false
					};
				}
				else if ( 'get-unoptimized-images' === this.error || 'consumed-all-data' === this.error ) {
					errorArgs = {
						title: imagifyOptions.bulk.labels.getUnoptimizedImagesErrorTitle,
						html:  imagifyOptions.bulk.labels.getUnoptimizedImagesErrorText,
						type:  'info'
					};
				}
				else if ( 'no-images' === this.error ) {
					errorArgs = {
						title: imagifyOptions.bulk.labels.nothingToDoTitle,
						html:  imagifyOptions.bulk.labels.nothingToDoText,
						type:  'info'
					};
				}
				else if ( 'no-backup' === this.error ) {
					errorArgs = {
						title: imagifyOptions.bulk.labels.nothingToDoTitle,
						html:  imagifyOptions.bulk.labels.nothingToDoNoBackupText,
						type:  'info'
					};
				} else {
					errorArgs = {
						title: imagifyOptions.bulk.labels.error,
						html:  this.error,
						type:  'info'
					};
				}

				this.displayError( errorArgs );

				// Reset the error.
				this.error = false;
			}

			// Reset.
			this.fetchQueue       = [];
			this.queue            = [];
			this.processingQueue  = [];
			this.fetchError       = false;
			this.working          = false;
			this.processIsStopped = false;
			this.processedMedia   = 0;
			this.totalMedia       = 0;

			// Reset Imagifybeat interval and enable suspend.
			w.imagify.beat.resetInterval();
			w.imagify.beat.enableSuspend();

			// Unlink the message displayed when the user wants to quit the page.
			$( w ).off( 'beforeunload.imagify', this.getConfirmMessage );

			// Reset the progress bar.
			this.$progressWrap.slideUp().attr( 'aria-hidden', 'true' );
			this.$progressBar.removeAttr( 'style' );
			this.$progressText.text( '0' );

			// Enable the button.
			this.$button.removeAttr( 'disabled' ).find( '.dashicons' ).removeClass( 'rotate' );
		},

		// Tools ===================================================================================

		/*
		 * Tell if we have a blocking error. Can also display an error message in a swal.
		 *
		 * @param  {bool} displayErrorMessage False to not display any error message.
		 * @return {bool}
		 */
		hasBlockingError: function ( displayErrorMessage ) {
			displayErrorMessage = undefined !== displayErrorMessage && displayErrorMessage;

			if ( imagifyOptions.bulk.curlMissing ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						html: imagifyOptions.bulk.labels.curlMissing
					} );
				}
				return true;
			}

			if ( imagifyOptions.bulk.editorMissing ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						html: imagifyOptions.bulk.labels.editorMissing
					} );
				}
				return true;
			}

			if ( imagifyOptions.bulk.extHttpBlocked ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						html: imagifyOptions.bulk.labels.extHttpBlocked
					} );
				}
				return true;
			}

			if ( imagifyOptions.bulk.apiDown ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						html: imagifyOptions.bulk.labels.apiDown
					} );
				}
				return true;
			}

			if ( ! imagifyOptions.bulk.keyIsValid ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						title: imagifyOptions.bulk.labels.invalidAPIKeyTitle,
						type:  'info'
					} );
				}
				return true;
			}

			if ( imagifyOptions.bulk.isOverQuota ) {
				if ( displayErrorMessage ) {
					this.displayError( {
						title:             imagifyOptions.bulk.labels.overQuotaTitle,
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

			args.title        = args.title || imagifyOptions.bulk.labels.error;
			args.customClass += ' imagify-sweet-alert';

			swal( args ).catch( swal.noop );
		},

		/*
		 * Get the URL used for ajax requests.
		 *
		 * @param  {string} action  An ajax action, or part of it.
		 * @param  {string} context The context.
		 * @return {string}
		 */
		getAjaxUrl: function ( action, context ) {
			var url;

			url  = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyOptions.bulk.ajaxNonce;
			url += '&action=' + imagifyOptions.bulk.ajaxActions[ action ];
			url += '&context=' + context;
			url += '&imagify_action=generate_webp';

			return url;
		},

		/*
		 * Stop everything and set an error.
		 *
		 * @param {string} errorId An error ID.
		 */
		stopProcess: function ( errorId ) {
			this.processIsStopped = true;

			this.error = errorId;

			this.queueEmpty();
		}
	};

	w.imagify.optionsBulk.init();

} )(window, document, jQuery);
/* eslint-enable no-underscore-dangle, consistent-this */


// "Select all" checkboxes =========================================================================
(function(w, d, $, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

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

	// Check all checkboxes.
	$( '.imagify-select-all' ).on( 'click.imagify', function() {
		var $_this   = $(this),
			action   = $_this.data( 'action' ),
			$btns    = $_this.closest( '.imagify-select-all-buttons' ),
			$group   = $btns.prev( '.imagify-check-group' ),
			inactive = 'imagify-is-inactive';

		if ( $_this.hasClass( inactive ) ) {
			return false;
		}

		$btns.find( '.imagify-select-all' ).removeClass( inactive ).attr( 'aria-disabled', 'false' );
		$_this.addClass( inactive ).attr( 'aria-disabled', 'true' );

		$group.find( '.imagify-row-check' )
			.prop( 'checked', function() {
				var $this = $( this );

				if ( $this.is( ':hidden,:disabled' ) ) {
					return false;
				}

				if ( action === 'select' ) {
					return true;
				}

				return false;
			} );

	} );

	// Change buttons status on checkboxes interation.
	$( '.imagify-check-group .imagify-row-check' ).on( 'change.imagify', function() {
		var $group      = $( this ).closest( '.imagify-check-group' ),
			$checks     = $group.find( '.imagify-row-check' ),
			could_be    = $checks.filter( ':visible:enabled' ).length,
			are_checked = $checks.filter( ':visible:enabled:checked' ).length,
			$btns       = $group.next( '.imagify-select-all-buttons' ),
			inactive    = 'imagify-is-inactive';

		// Toggle status of "check all" buttons.
		if ( are_checked === 0 ) {
			$btns.find( '[data-action="unselect"]' ).addClass( inactive ).attr( 'aria-disabled', 'true' );
		}
		if ( are_checked === could_be ) {
			$btns.find( '[data-action="select"]' ).addClass( inactive ).attr( 'aria-disabled', 'true' );
		}
		if ( are_checked !== could_be && are_checked > 0 ) {
			$btns.find( '.imagify-select-all' ).removeClass( inactive ).attr( 'aria-disabled', 'false' );
		}
	} );

} )(window, document, jQuery);
