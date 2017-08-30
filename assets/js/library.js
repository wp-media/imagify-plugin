(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	var bulkOpt;

	if ( w.imagifyUpload ) {
		/**
		 * Add a "Imagify'em all" in the select list.
		 */
		bulkOpt = '<option value="imagify-bulk-upload">' + imagifyUpload.bulkActionsLabels.optimize + '</option>';

		if ( imagifyUpload.backup_option || $( '.attachment-has-backup' ).length ) {
			// If the backup option is enabled, or if we have items that can be restored.
			bulkOpt += '<option value="imagify-bulk-restore">' + imagifyUpload.bulkActionsLabels.restore + '</option>';
		}

		$( '.bulkactions select[name="action"] option:last-child' ).before( bulkOpt );
		$( '.bulkactions select[name="action2"] option:last-child' ).before( bulkOpt );
	}

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

} )(jQuery, document, window);
