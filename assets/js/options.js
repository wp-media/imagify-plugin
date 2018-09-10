(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names
	/*
	 * Process an API key check validity.
	 */
	var busy = false,
		xhr  = false;

	$( '#imagify-settings #api_key' ).on( 'blur', function() {
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
	$( '.imagify-options-line' ).css( 'cursor', 'pointer' ).on( 'click', function( e ) {
		if ( 'INPUT' === e.target.nodeName ) {
			return;
		}
		$( 'input[aria-describedby="' + $( this ).attr( 'id' ) + '"]' ).trigger( 'click' );
		return false;
	} );

	$( '.imagify-settings th span' ).on( 'click', function() {
		var $input = $( this ).parent().next( 'td' ).find( 'input:checkbox' );

		if ( 1 === $input.length ) {
			$input.trigger( 'click' );
		}
	} );

	/**
	 * Auto check on options-line input value change.
	 */
	$( '.imagify-options-line' ).find( 'input' ).on( 'change focus', function() {
		var $checkbox = $( this ).closest( '.imagify-options-line' ).prev( 'label' ).prev( 'input' );

		if ( ! $checkbox[0].checked ) {
			$checkbox.prop( 'checked', true );
		}
	} );

	/**
	 * Imagify Backup alert.
	 */
	$( '.imagify-settings-section' ).find( '#imagify_backup' ).on( 'change', function() {
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
