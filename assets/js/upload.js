/* globals ajaxurl: false, console: false, imagifyUpload: true, Chart: false */

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
	/**
	 * Add a "Imagify'em all" in the select list.
	 */
	var bulk_opt, get_var, check_modal;

	bulk_opt = '<option value="imagify-bulk-upload">' + imagifyUpload.bulkActionsLabels.optimize + '</option>';

	if ( imagifyUpload.backup_option || $( '.attachment-has-backup' ).length ) {
		// If the backup option is enabled, or if we have items that can be restored.
		bulk_opt += '<option value="imagify-bulk-restore">' + imagifyUpload.bulkActionsLabels.restore + '</option>';
	}

	$( '.bulkactions select[name="action"]' ).find( 'option:last-child' ).before( bulk_opt );
	$( '.bulkactions select[name="action2"]' ).find( 'option:last-child' ).before( bulk_opt );

	/**
	 * Mini chart.
	 *
	 * @param {element} canvas
	 */
	function draw_me_a_chart( canvas ) {
		canvas.each( function() {
			var the_value    = parseInt( $( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() ),
				overviewData = [
					{
						value: the_value,
						color: '#00B3D3'
					},
					{
						value: 100 - the_value,
						color: '#D8D8D8'
					}
				];

			new Chart( $( this )[0].getContext( '2d' ) ).Doughnut( overviewData, {
				segmentStrokeColor : '#FFF',
				segmentStrokeWidth : 1,
				animateRotate      : true,
				tooltipEvents      : []
			} );
		} );
	}

	/**
	 * Process optimization for all selected images.
	 */
	$( '#doaction' )
		.add( '#doaction2' )
		.on( 'click', function( e ) {
			var value = $( this ).prev( 'select' ).val().split( '-' ),
				action, ids;

			if ( value[0] !== 'imagify' ) {
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

			draw_me_a_chart( $parent.find( '.imagify-chart-container' ).find( 'canvas' ) );
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
	 * Some usefull functions to help us with media modal.
	 */
	get_var = function ( param ) {
		var vars = {};

		w.location.href.replace( /[?&]+([^=&]+)=?([^&]*)?/gi, function( m, key, value ) {
			vars[ key ] = undefined !== value ? value : '';
		} );

		if ( param ) {
			return vars[ param ] ? vars[ param ] : null;
		}

		return vars;
	};

	check_modal = function() {
		var tempTimer = setInterval( function() {
			var $details = $( '.media-modal .imagify-datas-details' );

			if ( $details.length ) {
				$details.hide();
				draw_me_a_chart( $( '#imagify-consumption-chart' ) );
				clearInterval( tempTimer );
				tempTimer = null;
			}
		}, 20 );
	};

	/**
	 * Intercept the right moment if media details is clicked (mode grid).
	 * Bear Feint.
	 */
	$( '.upload-php' ).find( '.media-frame.mode-grid' ).on( 'click', '.attachment', function() {
		check_modal();
	} );

	// On page load in upload.php check if item param exists.
	if ( $( '.upload-php' ).length && get_var( 'item' ) ) {
		check_modal();
	}

	// On media clicked.
	$( '#insert-media-button' ).on( 'click.imagify', function() {
		var waitContent = setInterval( function() {
			var $attachments = $( '.media-frame-content .attachments' );

			if ( $attachments.length ) {
				$attachments.on( 'click.imagify', '.attachment', function() {
					check_modal();
				} );
				clearInterval( waitContent );
				waitContent = null;
			}
		}, 100);
	} );

	draw_me_a_chart( $( '.imagify-chart-container' ).find( 'canvas' ) );

} )(jQuery, document, window);
