/* globals ajaxurl: false, console: false, imagify: true, imagifyAdmin: true, imagifyOptions: true, swal: false */

window.imagify = window.imagify || {
	concat: ajaxurl.indexOf( '?' ) > 0 ? '&' : '?',
	log:    function( content ) {
		if ( undefined !== console ) {
			console.log( content );
		}
	},
	info:   function( content ) {
		if ( undefined !== console ) {
			console.info( content );
		}
	}
};

(function($, d, w, undefined) {
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
			$( '#imagify-check-api-container' ).html( '<span class="dashicons dashicons-yes"></span> ' + imagifyAdmin.labels.ValidApiKeyText );
			return false;
		}

		if ( true === busy ) {
			xhr.abort();
		} else {
			$( '#imagify-check-api-container' ).remove();
			obj.after( '<span id="imagify-check-api-container"><span class="imagify-spinner"></span>' + imagifyAdmin.labels.waitApiKeyCheckText + '</span>' );
		}

		busy = true;

		xhr = $.get( ajaxurl + imagify.concat + 'action=imagify_check_api_key_validity&api_key=' + obj.val() + '&imagifycheckapikeynonce=' + $( '#imagifycheckapikeynonce' ).val() )
		.done( function( response ) {
			if ( ! response.success ) {
				$( '#imagify-check-api-container' ).html( '<span class="dashicons dashicons-no"></span> ' + response.data );
			} else {
				$( '#imagify-check-api-container' ).remove();
				swal( {
					title:       imagifyAdmin.labels.ApiKeyCheckSuccessTitle,
					html:        imagifyAdmin.labels.ApiKeyCheckSuccessText,
					type:        'success',
					customClass: 'imagify-sweet-alert'
				} ).then(
				function() {
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

		if ( $input.length === 1 ) {
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
	$( '.imagify-settings-section' ).find( '#backup' ).on( 'change', function() {
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

		swal( {
			title:            imagifyOptions.noBackupTitle,
			html:             imagifyOptions.noBackupText,
			type:             'warning',
			customClass:      'imagify-sweet-alert',
			showCancelButton: true,
			cancelButtonText: imagifyAdmin.labels.swalCancel,
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


// "Select all" checkboxes =========================================================================
(function( w, d, $, undefined ) {

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
	$( '.imagify-check-group .imagify-row-check' ).on( 'click', function() {
		var $group     = $( this ).closest( '.imagify-check-group' ),
			allChecked = 0 === $group.find( '.imagify-row-check' ).filter( ':visible:enabled' ).not( ':checked' ).length;

		// Toggle "check all" checkboxes.
		$group.find( '.imagify-toggle-check' ).prop( 'checked', allChecked );
	} )
	.first().trigger( 'change.imagify' );

	$( '.imagify-check-group .imagify-toggle-check' ).on( 'click.wp-toggle-checkboxes', function( e ) {
		var $this          = $( this ),
			$wrap          = $this.closest( '.imagify-check-group' ),
			controlChecked = $this.prop( 'checked' ),
			toggle         = e.shiftKey || $this.data( 'wp-toggle' );

		$wrap.find( '.imagify-toggle-check' )
			.prop( 'checked', function() {
				var $this = $( this );

				if ( $this.is( ':hidden,:disabled' ) ) {
					return false;
				}

				if ( toggle ) {
					return ! $this.prop( 'checked' );
				}

				return controlChecked ? true : false;
			} );

		$wrap.find( '.imagify-row-check' )
			.prop( 'checked', function() {
				if ( toggle ) {
					return false;
				}

				return controlChecked ? true : false;
			} );
	} );

} )(window, document, jQuery);
