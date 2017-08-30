window.imagify = window.imagify || {
	concat: ajaxurl.indexOf( '?' ) > 0 ? '&' : '?',
	log:    function( content ) {
		if ( undefined !== console ) {
			console.log( content ); // eslint-disable-line no-console
		}
	},
	info:   function( content ) {
		if ( undefined !== console ) {
			console.info( content ); // eslint-disable-line no-console
		}
	}
};

(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var bulkOpt;

	/**
	 * Mini chart.
	 *
	 * @param {element} canvas The canvas element.
	 */
	function drawMeAChart( canvas ) {
		canvas.each( function() {
			var $this        = $( this ),
				theValue     = parseInt( $this.closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() ),
				overviewData = [
					{
						value: theValue,
						color: '#00B3D3'
					},
					{
						value: 100 - theValue,
						color: '#D8D8D8'
					}
				];

			new Chart( $this[0].getContext( '2d' ) ).Doughnut( overviewData, { // eslint-disable-line new-cap
				segmentStrokeColor: '#FFF',
				segmentStrokeWidth: 1,
				animateRotate:      true,
				tooltipEvents:      []
			} );
		} );
	}

	/**
	 * Get a URL param (or all of them).
	 *
	 * @param  {string} param Name of the param.
	 * @return {string|null|object} If param argument provided: the param value or null. The entire object otherwise.
	 */
	function getVar( param ) {
		var vars = {};

		w.location.href.replace( /[?&]+([^=&]+)=?([^&]*)?/gi, function( m, key, value ) {
			vars[ key ] = undefined !== value ? value : '';
		} );

		if ( param ) {
			return vars[ param ] ? vars[ param ] : null;
		}

		return vars;
	}

	/**
	 * Update the chart in the media modal.
	 */
	function checkModal() {
		var tempTimer = setInterval( function() {
			var $details = $( '.media-modal .imagify-datas-details' );

			if ( $details.length ) {
				$details.hide();
				drawMeAChart( $( '.media-modal .imagify-consumption-chart' ) );
				clearInterval( tempTimer );
				tempTimer = null;
			}
		}, 20 );
	}

	if ( w.imagifyUpload ) {
		/**
		 * In the medias list, add a "Imagify'em all" in the select list.
		 */
		bulkOpt = '<option value="imagify-bulk-upload">' + imagifyUpload.bulkActionsLabels.optimize + '</option>';

		if ( imagifyUpload.backup_option || $( '.attachment-has-backup' ).length ) {
			// If the backup option is enabled, or if we have items that can be restored.
			bulkOpt += '<option value="imagify-bulk-restore">' + imagifyUpload.bulkActionsLabels.restore + '</option>';
		}

		$( '.bulkactions select[name="action"]' ).find( 'option:last-child' ).before( bulkOpt );
		$( '.bulkactions select[name="action2"]' ).find( 'option:last-child' ).before( bulkOpt );

		/**
		 * Process optimization for all selected images.
		 */
		$( '#doaction' )
			.add( '#doaction2' )
			.on( 'click', function( e ) {
				var value = $( this ).prev( 'select' ).val().split( '-' ),
					action, ids;

				if ( 'imagify' !== value[0] ) {
					return;
				}

				e.preventDefault();

				action = value[2];
				ids    = $( 'input[name^="media"]:checked' ).map( function() {
					return this.value;
				} ).get();

				ids.forEach( function( id, index ) {
					setTimeout( function() {
						$( '#imagify-' + action + '-' + id ).trigger( 'click' );
					}, index * 300 );
				} );
			} );
	}

	/**
	 * Process to one of these actions: restore, optimize or re-optimize.
	 */
	$( d ).on( 'click', '.button-imagify-restore, .button-imagify-manual-upload, .button-imagify-manual-override-upload', function( e ) {
		var $obj    = $( this ),
			$parent = $obj.parents( '.column-imagify_optimized_file, .compat-field-imagify .field' ),
			href    = $obj.attr( 'href' );

		e.preventDefault();

		if ( ! $parent.length ) {
			$parent = $obj.closest( '.column' );
		}

		$parent.html( '<div class="button"><span class="imagify-spinner"></span>' + $obj.data( 'waiting-label' ) + '</div>' );

		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
			.done( function( response ){
				$parent.html( response.data );
				$parent.find( '.imagify-datas-more-action a' ).addClass( 'is-open' ).find( '.the-text' ).text( $parent.find( '.imagify-datas-more-action a' ).data( 'close' ) );
				$parent.find( '.imagify-datas-details' ).addClass( 'is-open' );

				drawMeAChart( $parent.find( '.imagify-consumption-chart' ) );
			} );
	} );

	/**
	 * Toggle slide in custom column.
	 */
	$( '.imagify-datas-details' ).hide();

	$( d ).on( 'click', '.imagify-datas-more-action a', function( e ) {
		var $this = $( this );

		e.preventDefault();

		if ( $this.hasClass( 'is-open' ) ) {
			$( $this.attr( 'href' ) ).slideUp( 300 ).removeClass( 'is-open' );
			$this.removeClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'open' ) );
		} else {
			$( $this.attr( 'href' ) ).slideDown( 300 ).addClass( 'is-open' );
			$this.addClass( 'is-open' ).find( '.the-text' ).text( $this.data( 'close' ) );
		}
	} );

	/**
	 * Update the chart in the media modal.
	 */
	// Intercept the right moment if media details is clicked (mode grid).
	$( '#wp-media-grid' ).on( 'click', '.attachment', checkModal );

	// On page load in upload.php check if item param exists.
	if ( $( '.upload-php' ).length && getVar( 'item' ) ) {
		checkModal();
	}

	// When the "Add Media" button is clicked.
	$( '#insert-media-button, .insert-media, .add_media, .upload-media-button' ).on( 'click.imagify', function() {
		var waitContent = setInterval( function() {
			var $attachments = $( '.media-frame-content .attachments' );

			if ( $attachments.length ) {
				$attachments.on( 'click.imagify', '.attachment', function() {
					checkModal();
				} );
				clearInterval( waitContent );
				waitContent = null;
			}
		}, 100);
	} );

	drawMeAChart( $( '.imagify-consumption-chart' ) );

} )(jQuery, document, window);
