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
		var $_this = $( this );

		if ( $_this.is( ':checked' ) ) {
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
			function() {},
			function() {
				$_this.prop( 'checked', true );
			}
		);
	} );

} )(jQuery, document, window);
