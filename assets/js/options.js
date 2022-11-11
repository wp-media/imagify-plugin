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

		if ( $button.prop('disabled') ) {
			return;
		}

		$button.prop( 'disabled', true ).next( 'img' ).attr( 'aria-hidden', 'false' );

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
				$button.prop( 'disabled', false ).next( 'img' ).attr( 'aria-hidden', 'true' );
			} );
	} );

	// Clicking a folder icon in the modal: fetch the folder's sub-folders and files, then display them.
	$( d ).on( 'click.imagify', '#imagify-folders-tree [data-folder]', function() {
		var $button  = $( this ),
			$tree    = $button.nextAll( '.imagify-folders-sub-tree' ),
			selected = [];

		if ( $button.prop('disabled') || $button.siblings( ':checkbox' ).is( ':checked' ) ) {
			return;
		}

		$button.prop( 'disabled', true ).addClass( 'imagify-loading' );

		if ( $tree.length ) {
			if ( $button.hasClass( 'imagify-is-open' ) ) {
				$tree.addClass( 'hidden' );
				$button.removeClass(' imagify-is-open' );
			} else {
				$tree.removeClass( 'hidden' );
				$button.addClass( 'imagify-is-open' );
			}
			$button.prop( 'disabled', false ).removeClass( 'imagify-loading' );
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
				$button.prop( 'disabled', false ).removeClass( 'imagify-loading' );
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


// Generate missing WebP versions ==================================================================
/* eslint-disable no-underscore-dangle, consistent-this */
(function(w, d, $, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	if ( ! imagifyOptions.bulk ) {
		return;
	}

	w.imagify.optionsBulk = {
		// Properties ==============================================================================
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
		processIsStopped: false,
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
			var processed, progress;
			this.$missingWebpElement = $('.generate-missing-webp');
			this.$missingWebpMessage = $('.generate-missing-webp p');

			this.$button       = $( '#imagify-generate-webp-versions' );
			this.$progressWrap = this.$button.siblings( '.imagify-progress' );
			this.$progressBar  = this.$progressWrap.find( '.bar' );
			this.$progressText = this.$progressBar.find( '.percent' );

			// Enable/Disable the button when the "Convert to WebP" checkbox is checked/unchecked.
			$( '#imagify_convert_to_webp' )
				.on( 'change.imagify init.imagify', { imagifyOptionsBulk: this }, this.toggleButton )
				.trigger( 'init.imagify' );

			// Launch optimization.
			this.$button.on( 'click.imagify', { imagifyOptionsBulk: this }, this.maybeLaunchMissingWebpProcess );

			// Imagifybeat for optimization queue.
			$( d )
				.on( 'imagifybeat-send', { imagifyOptionsBulk: this }, this.addQueueImagifybeat )
				.on( 'imagifybeat-tick', { imagifyOptionsBulk: this }, this.processQueueImagifybeat )
			// Imagifybeat for requirements.
				.on( 'imagifybeat-send', this.addRequirementsImagifybeat )
				.on( 'imagifybeat-tick', { imagifyOptionsBulk: this }, this.processRequirementsImagifybeat );

			if ( false !== imagifyOptions.bulk.progress_webp.total && false !== imagifyOptions.bulk.progress_webp.remaining ) {
				// Reset properties.
				w.imagify.optionsBulk.error            = false;
				w.imagify.optionsBulk.working          = true;
				w.imagify.optionsBulk.processIsStopped = false;

				// Disable the button.
				this.$button.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'rotate' );

				// Fasten Imagifybeat: 1 tick every 15 seconds, and disable suspend.
				w.imagify.beat.interval( 15 );
				w.imagify.beat.disableSuspend();

				this.$missingWebpMessage.hide().attr('aria-hidden', 'true');

				processed = imagifyOptions.bulk.progress_webp.total - imagifyOptions.bulk.progress_webp.remaining;
				progress = Math.floor( processed / imagifyOptions.bulk.progress_webp.total * 100 );
				this.$progressBar.css( 'width', progress + '%' );
				this.$progressText.text( processed + '/' + imagifyOptions.bulk.progress_webp.total );

				this.$progressWrap.slideDown().attr( 'aria-hidden', 'false' ).removeClass( 'hidden' );
			}
		},

		// Event callbacks =========================================================================

		/**
		 * Enable/Disable the button when the "Convert to WebP" checkbox is checked/unchecked.
		 *
		 * @param {object} e Event object.
		 */
		toggleButton: function ( e ) {
			if ( ! this.checked ) {
				e.data.imagifyOptionsBulk.$button.prop( 'disabled', true );
			} else {
				e.data.imagifyOptionsBulk.$button.prop( 'disabled', false );
			}
		},

		/*
		 * Maybe launch the missing WebP generation process.
		 *
		 * @param {object} e Event object.
		 */
		maybeLaunchMissingWebpProcess: function ( e ) {
			if ( ! e.data.imagifyOptionsBulk || e.data.imagifyOptionsBulk.working ) {
				return;
			}

			if ( e.data.imagifyOptionsBulk.hasBlockingError( true ) ) {
				return;
			}

			// Reset properties.
			e.data.imagifyOptionsBulk.error            = false;
			e.data.imagifyOptionsBulk.working          = true;
			e.data.imagifyOptionsBulk.processIsStopped = false;

			// Disable the button.
			e.data.imagifyOptionsBulk.$button.prop( 'disabled', true ).find( '.dashicons' ).addClass( 'rotate' );

			// Fasten Imagifybeat: 1 tick every 15 seconds, and disable suspend.
			w.imagify.beat.interval( 15 );
			w.imagify.beat.disableSuspend();

			// Launch missing WebP generation process
			e.data.imagifyOptionsBulk.launchProcess();
		},

		// Imagifybeat =============================================================================

		/**
		 * Add a Imagifybeat ID on "imagifybeat-send" event to sync the optimization queue.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		addQueueImagifybeat: function ( e, data ) {
			data[ imagifyOptions.bulk.imagifybeatIDs.progress ] = imagifyOptions.bulk.contexts;
		},

		/**
		 * Listen for the custom event "imagifybeat-tick" on $(document).
		 * It allows to update various data periodically.
		 *
		 * @param {object} e    Event object.
		 * @param {object} data Object containing all Imagifybeat IDs.
		 */
		processQueueImagifybeat: function ( e, data ) {
			var images_status, processed, progress;

			if ( e.data.imagifyOptionsBulk && typeof data[ imagifyOptions.bulk.imagifybeatIDs.progress ] === 'undefined' ) {
				return;
			}

			if ( e.data.imagifyOptionsBulk.processIsStopped ) {
				e.data.imagifyOptionsBulk.processFinished();
				return;
			}

			images_status = data[ imagifyOptions.bulk.imagifybeatIDs.progress ];

			if ( images_status.remaining === 0 ) {
				e.data.imagifyOptionsBulk.processFinished();
				return;
			}

			processed = images_status.total - images_status.remaining;
			progress = Math.floor( processed / images_status.total * 100 );
			e.data.imagifyOptionsBulk.$progressBar.css( 'width', progress + '%' );
			e.data.imagifyOptionsBulk.$progressText.text( processed + '/' + images_status.total );
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
		 * launch the missing WebP generation process.
		 */
		launchProcess: function () {
			var _this;

			if ( this.processIsStopped ) {
				return;
			}

			_this = this;

			$.get( this.getAjaxUrl( 'MissingWebp', imagifyOptions.bulk.contexts ) )
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
						if ( ! _this.error ) {
							_this.stopProcess( errorMessage );
						}
						return;
					}

					if ( 0 === response.data.total ) {
						// No media to process.
						_this.stopProcess( 'no-images' );

						return;
					}

					_this.$missingWebpMessage.hide().attr('aria-hidden', 'true');

					// Reset and display the progress bar.
					_this.$progressText.text( '0' + ( response.data.total ? '/' + response.data.total : '' ) );
					_this.$progressWrap.slideDown().attr( 'aria-hidden', 'false' ).removeClass( 'hidden' );
				} )
				.fail( function() {
					// Error.
					if ( ! _this.error ) {
						_this.stopProcess( 'get-unoptimized-images' );
					}
				} );
		},

		/*
		 * End.
		 */
		processFinished: function () {
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
				else if ( 'get-unoptimized-images' === this.error ) {
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
			this.working          = false;
			this.processIsStopped = false;

			// Reset Imagifybeat interval and enable suspend.
			w.imagify.beat.resetInterval();
			w.imagify.beat.enableSuspend();

			// Reset the progress bar.
			this.$progressWrap.slideUp().attr( 'aria-hidden', 'true' ).addClass( 'hidden' );
			this.$progressText.text( '0' );
			this.$missingWebpElement.hide().attr('aria-hidden', 'true');
			this.$button.find( '.dashicons' ).removeClass( 'rotate' );
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
		 * @param  {array} context The contexts.
		 * @return {string}
		 */
		getAjaxUrl: function ( action, contexts ) {
			var url;

			url  = ajaxurl + w.imagify.concat + '_wpnonce=' + imagifyOptions.bulk.ajaxNonce;
			url += '&action=' + imagifyOptions.bulk.ajaxActions[ action ];
			url += '&context=' + contexts.join('_');

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

			this.processFinished();
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
