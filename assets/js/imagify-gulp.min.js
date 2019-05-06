/**
 * Library that handles the bulk optimization processes.
 *
 * @requires jQuery
 */
window.imagify = window.imagify || {};

/* eslint-disable no-underscore-dangle, consistent-this */
(function($, d, w) {

	/**
	 * Construct the optimizer.
	 *
	 * @param {object} settings {
	 *     Optimizer settings:
	 *
	 *     @type {string} groupID         Group ID, like 'library' or 'custom-folders'.
	 *     @type {string} context         Context within this group, like 'wp' or 'custom-folders' (yes, again).
	 *     @type {int}    level           Optimization level: 0 to 2.
	 *     @type {int}    bufferSize      Number of parallel optimizations: usually 4.
	 *     @type {string} ajaxUrl         URL to request to optimize.
	 *     @type {object} files           Files to optimize: media ID as key (prefixed with an underscore), file URL as value.
	 *     @type {string} defaultThumb    A default thumbnail URL.
	 *     @type {string} doneEvent       Name of the event to listen to know when optimizations end.
	 *     @type {array}  imageExtensions A list of supported image extensions (only images).
	 * }
	 */
	w.imagify.Optimizer = function ( settings ) {
		// Settings.
		this.groupID      = settings.groupID;
		this.context      = settings.context;
		this.level        = settings.level;
		this.bufferSize   = settings.bufferSize || 1;
		this.ajaxUrl      = settings.ajaxUrl;
		this.files        = settings.files;
		this.defaultThumb = settings.defaultThumb;
		this.doneEvent    = settings.doneEvent;

		if ( settings.imageExtensions ) {
			this.imageExtensions = settings.imageExtensions;
		} else {
			this.imageExtensions = [ 'jpg', 'jpeg', 'jpe', 'png', 'gif' ];
		}

		/**
		 * An array of media IDs (prefixed with an underscore).
		 */
		this.prefixedMediaIDs = Object.keys( this.files );
		/**
		 * An array of medias currently being optimized: {
		 *     @type {int}    mediaID   The media ID.
		 *     @type {string} filename  The file name.
		 *     @type {string} thumbnail The file thumbnail URL.
		 * }
		 */
		this.currentItems = [];

		// Internal counters.
		this.totalMedia     = this.prefixedMediaIDs.length;
		this.processedMedia = 0;

		// Global stats.
		this.globalOriginalSize  = 0;
		this.globalOptimizedSize = 0;
		this.globalGain          = 0;
		this.globalPercent       = 0;

		// Callbacks.
		this._before = function () {};
		this._each   = function () {};
		this._done   = function () {};

		// Listen to the "optimization done" event.
		if ( this.totalMedia && this.doneEvent ) {
			$( w ).on( this.doneEvent, { _this: this }, this.processedCallback );
		}
	};

	/**
	 * Callback to trigger before each media optimization.
	 *
	 * @param  {callable} fnc A callback.
	 * @return this
	 */
	w.imagify.Optimizer.prototype.before = function( fnc ) {
		this._before = fnc;
		return this;
	};

	/**
	 * Callback to trigger after each media optimization.
	 *
	 * @param  {callable} fnc A callback.
	 * @return this
	 */
	w.imagify.Optimizer.prototype.each = function( fnc ) {
		this._each = fnc;
		return this;
	};

	/**
	 * Callback to trigger all media optimizations have been done.
	 *
	 * @param  {callable} fnc A callback.
	 * @return this
	 */
	w.imagify.Optimizer.prototype.done = function( fnc ) {
		this._done = fnc;
		return this;
	};

	/**
	 * Launch optimizations.
	 *
	 * @return this
	 */
	w.imagify.Optimizer.prototype.run = function() {
		var chunkLength = this.prefixedMediaIDs.length > this.bufferSize ? this.bufferSize : this.prefixedMediaIDs.length,
			i;

		for ( i = 0; i < chunkLength; i++ ) {
			this.processNext();
		}

		return this;
	};

	/**
	 * Launch next optimization.
	 *
	 * @return this
	 */
	w.imagify.Optimizer.prototype.processNext = function() {
		if ( this.prefixedMediaIDs.length ) {
			this.process( this.prefixedMediaIDs.shift() );
		}

		return this;
	};

	/**
	 * Launch an optimization.
	 *
	 * @param  {string} prefixedId A media ID, prefixed with an underscore.
	 * @return this
	 */
	w.imagify.Optimizer.prototype.process = function( prefixedId ) {
		var _this     = this,
			fileURL   = this.files[ prefixedId ],
			data      = {
				mediaID:    parseInt( prefixedId.toString().substr( 1 ), 10 ),
				filename:  this.files[ prefixedId ].split( '/' ).pop(),
				thumbnail: this.defaultThumb
			},
			extension = data.filename.split( '.' ).pop().toLowerCase(),
			regexp    = new RegExp( '^' + this.imageExtensions.join( '|' ).toLowerCase() + '$' ),
			image;

		if ( ! extension.match( regexp ) ) {
			// Not an image.
			this.currentItems.push( data );
			this._before( data );
			this.send( data );
			return this;
		}

		// Create a thumbnail and send the ajax request.
		image = new Image();

		image.onerror = function () {
			_this.currentItems.push( data );
			_this._before( data );
			_this.send( data );
		};

		image.onload = function () {
			var maxWidth    = 33,
				maxHeight   = 33,
				imageWidth  = image.width,
				imageHeight = image.height,
				newHeight   = 0,
				newWidth    = 0,
				topOffset   = 0,
				leftOffset  = 0,
				canvas      = null,
				ctx         = null;

			if ( imageWidth < imageHeight ) {
				// Portrait.
				newWidth  = maxWidth;
				newHeight = newWidth * imageHeight / imageWidth;
				topOffset = ( maxHeight - newHeight ) / 2;
			} else {
				// Landscape.
				newHeight  = maxHeight;
				newWidth   = newHeight * imageWidth / imageHeight;
				leftOffset = ( maxWidth - newWidth ) / 2;
			}

			canvas = d.createElement( 'canvas' );

			canvas.width  = maxWidth;
			canvas.height = maxHeight;

			ctx = canvas.getContext( '2d' );
			ctx.drawImage( this, leftOffset, topOffset, newWidth, newHeight );

			try {
				data.thumbnail = canvas.toDataURL( 'image/jpeg' );
			} catch ( e ) {
				data.thumbnail = _this.defaultThumb;
			}

			canvas = null;
			ctx    = null;
			image  = null;

			_this.currentItems.push( data );
			_this._before( data );
			_this.send( data );
		};

		image.src = fileURL;

		return this;
	};

	/**
	 * Do the ajax request.
	 *
	 * @param  {object} data {
	 *     The data:
	 *
	 *     @type {int}    mediaID   The media ID.
	 *     @type {string} filename  The file name.
	 *     @type {string} thumbnail The file thumbnail URL.
	 * }
	 * @return this
	 */
	w.imagify.Optimizer.prototype.send = function( data ) {
		var _this           = this,
			defaultResponse = {
				success:   false,
				mediaID:   data.mediaID,
				groupID:   this.groupID,
				context:   this.context,
				filename:  data.filename,
				thumbnail: data.thumbnail,
				status:    'error',
				error:     ''
			};

		$.post( {
			url:      this.ajaxUrl,
			data:     {
				media_id:           data.mediaID,
				context:            this.context,
				optimization_level: this.level
			},
			dataType: 'json'
		} )
			.done( function( response ) {
				if ( response.success ) {
					return;
				}

				defaultResponse.error = response.data.error;

				_this.processed( defaultResponse );
			} )
			.fail( function( jqXHR ) {
				defaultResponse.error = jqXHR.statusText;

				_this.processed( defaultResponse );
			} );

		return this;
	};

	/**
	 * Callback triggered when an optimization is complete.
	 *
	 * @param {object} e    jQuery's Event object.
	 * @param {object} item {
	 *     The response:
	 *
	 *     @type {int}    mediaID The media ID.
	 *     @type {string} context The context.
	 * }
	 */
	w.imagify.Optimizer.prototype.processedCallback = function( e, item ) {
		var _this = e.data._this;

		if ( item.context !== _this.context ) {
			return;
		}

		if ( ! item.mediaID || typeof _this.files[ '_' + item.mediaID ] === 'undefined' ) {
			return;
		}

		item.groupID = _this.groupID;

		if ( ! _this.currentItems.length ) {
			// Trouble.
			_this.processed( item );
			return;
		}

		$.each( _this.currentItems, function( i, mediaData ) {
			if ( item.mediaID === mediaData.mediaID ) {
				item.filename  = mediaData.filename;
				item.thumbnail = mediaData.thumbnail;
				return false;
			}
		} );

		_this.processed( item );
	};

	/**
	 * After a media has been processed.
	 *
	 * @param  {object} response {
	 *     The response:
	 *
	 *     @type {bool}   success   Whether the optimization succeeded or not ("already optimized" is a success).
	 *     @type {int}    mediaID   The media ID.
	 *     @type {string} groupID   The group ID.
	 *     @type {string} context   The context.
	 *     @type {string} filename  The file name.
	 *     @type {string} thumbnail The file thumbnail URL.
	 *     @type {string} status    The status, like 'optimized', 'already-optimized', 'over-quota', 'error'.
	 *     @type {string} error     The error message.
	 * }
	 * @return this
	 */
	w.imagify.Optimizer.prototype.processed = function( response ) {
		var currentItems = this.currentItems;

		if ( currentItems.length ) {
			// Remove this media from the "current" list.
			$.each( currentItems, function( i, mediaData ) {
				if ( response.mediaID === mediaData.mediaID ) {
					currentItems.splice( i, 1 );
					return false;
				}
			} );

			this.currentItems = currentItems;
		}

		// Update stats.
		if ( response.success && 'already-optimized' !== response.status ) {
			this.globalOriginalSize  += response.originalOverallSize;
			this.globalOptimizedSize += response.newOverallSize;
			this.globalGain          += response.overallSaving;
			this.globalPercent        = ( 100 - this.globalOptimizedSize / this.globalOptimizedSize * 100 ).toFixed( 2 );
		}

		++this.processedMedia;
		response.progress = Math.floor( this.processedMedia / this.totalMedia * 100 );

		this._each( response );

		if ( this.prefixedMediaIDs.length ) {
			this.processNext();
		} else if ( this.totalMedia === this.processedMedia ) {
			this._done( {
				globalOriginalSize:  this.globalOriginalSize,
				globalOptimizedSize: this.globalOptimizedSize,
				globalGain:          this.globalGain
			} );
		}

		return this;
	};

	/**
	 * Stop the process.
	 *
	 * @return this
	 */
	w.imagify.Optimizer.prototype.stopProcess = function() {
		this.files            = {};
		this.prefixedMediaIDs = [];
		this.currentItems     = [];

		if ( this.doneEvent ) {
			$( w ).off( this.doneEvent, this.processedCallback );
		}

		return this;
	};

} )(jQuery, document, window);
