/**
 * Mini chart.
 *
 * @param {element} canvas The canvas element.
 */
window.imagify.drawMeAChart = function( canvas ) {
	canvas.each( function() {
		var value = parseInt( jQuery( this ).closest( '.imagify-chart' ).next( '.imagify-chart-value' ).text() );

		new Chart( this, { // eslint-disable-line no-new
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
};


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * Update the charts.
	 */
	$( w ).on( 'canvasprinted.imagify', function( e, selector ) {
		var $canvas;

		selector = selector || '.imagify-consumption-chart';
		$canvas  = $( selector );

		w.imagify.drawMeAChart( $canvas );
	} )
		.trigger( 'canvasprinted.imagify' );

} )(jQuery, document, window);


(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * Handle bulk actions.
	 */
	var bulkActions = '<option value="imagify-bulk-optimize">' + imagifyFiles.labels.bulkActionsOptimize + '</option>';

	if ( imagifyFiles.backupOption || $( '.file-has-backup' ).length ) {
		// If the backup option is enabled, or if we have items that can be restored.
		bulkActions += '<option value="imagify-bulk-restore">' + imagifyFiles.labels.bulkActionsRestore + '</option>';
	}

	$( '.bulkactions select[name="action"] option:first-child, .bulkactions select[name="action2"] option:first-child' ).after( bulkActions );

	/**
	 * Process one of these actions: bulk restore, bulk optimize, or bulk refresh-status.
	 */
	$( '#doaction, #doaction2' )
		.on( 'click.imagify', function( e ) {
			var value = $( this ).prev( 'select' ).val(),
				action, ids;

			if ( 'imagify-bulk-optimize' !== value && 'imagify-bulk-restore' !== value && 'imagify-bulk-refresh-status' !== value ) {
				return;
			}

			e.preventDefault();

			action = value.replace( 'bulk-', '' );
			ids    = $( 'input[name="bulk_select[]"]:checked' ).map( function() {
				return this.value;
			} ).get();

			ids.forEach( function( id, index ) {
				var $button = $( '#' + action + '-' + id );

				if ( ! $button.length ) {
					$button.closest( 'tr' ).find( '#cb-select-' + id ).prop( 'checked', false );
					return;
				}

				setTimeout( function() {
					$button.trigger( 'click.imagify' );
				}, index * 500 );
			} );
		} );

	/**
	 * Process one of these actions: optimize, re-optimize, restore, or refresh-status.
	 */
	$( d ).on( 'click.imagify', '.button-imagify-optimize, .button-imagify-reoptimize, .button-imagify-restore, .button-imagify-refresh-status', function( e ) {
		var $button = $( this ),
			$row    = $button.closest( 'tr' ),
			$parent, href;

		e.preventDefault();

		if ( $row.hasClass( 'working' ) ) {
			return;
		}

		$row.addClass( 'working' );
		$parent = $button.closest( '.column-actions, .column-status' );
		href    = $button.attr( 'href' );

		$parent.html( '<div class="button"><span class="imagify-spinner"></span>' + $button.data( 'waiting-label' ) + '</div>' );

		$.get( href.replace( 'admin-post.php', 'admin-ajax.php' ) )
			.done( function( r ) {
				if ( ! r.success ) {
					if ( r.data.row ) {
						$row.html( '<td class="colspanchange" colspan="' + $row.children().length + '">' + r.data.row + '</td>' );
					} else {
						$parent.html( r.data );
					}
					return;
				}

				$.each( r.data, function( k, v ) {
					$row.children( '.column-' + k ).html( v );
				} );
			} )
			.always( function() {
				$row.removeClass( 'working' ).find( '.check-column [type="checkbox"]' ).prop( 'checked', false );
			} );
	} );

} )(jQuery, document, window);
