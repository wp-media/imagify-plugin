(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var bulkOpt;
	/**
	 * Add a "Imagify'em all" in the select list.
	 */
	bulkOpt = '<option value="imagify-bulk-upload">' + imagifyLibrary.labels.bulkActionsOptimize + '</option>';

	if ( $( '.button-imagify-optimize-missing-sizes' ).length ) {
		// If we have items that have missing sizes.
		bulkOpt += '<option value="imagify-bulk-optimize_missing_sizes">' + imagifyLibrary.labels.bulkActionsOptimizeMissingSizes + '</option>';
	}

	if ( imagifyLibrary.backupOption || $( '.attachment-has-backup' ).length ) {
		// If the backup option is enabled, or if we have items that can be restored.
		bulkOpt += '<option value="imagify-bulk-restore">' + imagifyLibrary.labels.bulkActionsRestore + '</option>';
	}

	$( '.bulkactions select[name="action"] option:last-child' ).before( bulkOpt );
	$( '.bulkactions select[name="action2"] option:last-child' ).before( bulkOpt );
	$( '#bulkaction option:last-child' ).after( bulkOpt );

	/**
	 * Process optimization for all selected images.
	 */
	$( '#doaction' )
		.add( '#doaction2' )
		.add( '#bulkaction + [name="showThickbox"]' )
		.on( 'click', function( e ) {
			var value = $( this ).prev( 'select' ).val().split( '-' ),
				action, ids;

			if ( 'imagify' !== value[0] ) {
				return;
			}

			e.preventDefault();

			action = value[2];
			ids    = $( 'input[name^="media"]:checked, input[name^="doaction"]:checked' ).map( function() {
				return this.value;
			} ).get();

			ids.forEach( function( id, index ) {
				setTimeout( function() {
					$( '#imagify-' + action + '-' + id ).trigger( 'click' );
				}, index * 300 );
			} );
		} );

} )(jQuery, document, window);
