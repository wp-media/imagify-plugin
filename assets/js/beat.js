/**
 * Imagify beat API
 *
 * This is a modified version of WordPress’ Heartbeat (WP 5.2.1).
 * The main difference is that it allows to prevent suspension entirely.
 * It uses the var imagifybeatSettings on init.
 *
 * Custom jQuery events:
 * - imagifybeat-send
 * - imagifybeat-tick
 * - imagifybeat-error
 * - imagifybeat-connection-lost
 * - imagifybeat-connection-restored
 * - imagifybeat-nonces-expired
 *
 * @since 1.9.3
 */

window.imagify = window.imagify || {};

/* eslint-disable no-use-before-define */
(function($, d, w, undefined) { // eslint-disable-line no-unused-vars, no-shadow, no-shadow-restricted-names

	/**
	 * Constructs the Imagifybeat API.
	 *
	 * @since 1.9.3
	 * @constructor
	 *
	 * @return {Imagifybeat} An instance of the Imagifybeat class.
	 */
	var Imagifybeat = function() {
		var $document = $( d ),
			settings  = {
				// Suspend/resume.
				suspend: false,

				// Whether suspending is enabled.
				suspendEnabled: true,

				// Current screen id, defaults to the JS global 'pagenow' when present
				// (in the admin) or 'front'.
				screenId: '',

				// XHR request URL, defaults to the JS global 'ajaxurl' when present.
				url: '',

				// Timestamp, start of the last connection request.
				lastTick: 0,

				// Container for the enqueued items.
				queue: {},

				// Connect interval (in seconds).
				mainInterval: 60,

				// Used when the interval is set to 5 sec. temporarily.
				tempInterval: 0,

				// Used when the interval is reset.
				originalInterval: 0,

				// Used to limit the number of AJAX requests.
				minimalInterval: 0,

				// Used together with tempInterval.
				countdown: 0,

				// Whether a connection is currently in progress.
				connecting: false,

				// Whether a connection error occurred.
				connectionError: false,

				// Used to track non-critical errors.
				errorcount: 0,

				// Whether at least one connection has been completed successfully.
				hasConnected: false,

				// Whether the current browser w is in focus and the user is active.
				hasFocus: true,

				// Timestamp, last time the user was active. Checked every 30 sec.
				userActivity: 0,

				// Flag whether events tracking user activity were set.
				userActivityEvents: false,

				// Timer that keeps track of how long a user has focus.
				checkFocusTimer: 0,

				// Timer that keeps track of how long needs to be waited before connecting to
				// the server again.
				beatTimer: 0
			};

		/**
		 * Sets local variables and events, then starts the beat.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function initialize() {
			var options, hidden, visibilityState, visibilitychange;

			if ( typeof w.pagenow === 'string' ) {
				settings.screenId = w.pagenow;
			}

			if ( typeof w.ajaxurl === 'string' ) {
				settings.url = w.ajaxurl;
			}

			// Pull in options passed from PHP.
			if ( typeof w.imagifybeatSettings === 'object' ) {
				options = w.imagifybeatSettings;

				// The XHR URL can be passed as option when w.ajaxurl is not set.
				if ( ! settings.url && options.ajaxurl ) {
					settings.url = options.ajaxurl;
				}

				/*
				 * The interval can be from 15 to 120 sec. and can be set temporarily to 5 sec.
				 * It can be set in the initial options or changed later through JS and/or
				 * through PHP.
				 */
				if ( options.interval ) {
					settings.mainInterval = options.interval;

					if ( settings.mainInterval < 15 ) {
						settings.mainInterval = 15;
					} else if ( settings.mainInterval > 120 ) {
						settings.mainInterval = 120;
					}
				}

				/*
				 * Used to limit the number of AJAX requests. Overrides all other intervals if
				 * they are shorter. Needed for some hosts that cannot handle frequent requests
				 * and the user may exceed the allocated server CPU time, etc. The minimal
				 * interval can be up to 600 sec. however setting it to longer than 120 sec.
				 * will limit or disable some of the functionality (like post locks). Once set
				 * at initialization, minimalInterval cannot be changed/overridden.
				 */
				if ( options.minimalInterval ) {
					options.minimalInterval  = parseInt( options.minimalInterval, 10 );
					settings.minimalInterval = options.minimalInterval > 0 && options.minimalInterval <= 600 ? options.minimalInterval * 1000 : 0;
				}

				if ( settings.minimalInterval && settings.mainInterval < settings.minimalInterval ) {
					settings.mainInterval = settings.minimalInterval;
				}

				// 'screenId' can be added from settings on the front end where the JS global
				// 'pagenow' is not set.
				if ( ! settings.screenId ) {
					settings.screenId = options.screenId || 'front';
				}

				if ( 'disable' === options.suspension ) {
					disableSuspend();
				}
			}

			// Convert to milliseconds.
			settings.mainInterval     = settings.mainInterval * 1000;
			settings.originalInterval = settings.mainInterval;

			/*
			 * Switch the interval to 120 seconds by using the Page Visibility API.
			 * If the browser doesn't support it (Safari < 7, Android < 4.4, IE < 10), the
			 * interval will be increased to 120 seconds after 5 minutes of mouse and keyboard
			 * inactivity.
			 */
			if ( typeof document.hidden !== 'undefined' ) {
				hidden           = 'hidden';
				visibilitychange = 'visibilitychange';
				visibilityState  = 'visibilityState';
			} else if ( typeof document.msHidden !== 'undefined' ) { // IE10
				hidden           = 'msHidden';
				visibilitychange = 'msvisibilitychange';
				visibilityState  = 'msVisibilityState';
			} else if ( typeof document.webkitHidden !== 'undefined' ) { // Android
				hidden           = 'webkitHidden';
				visibilitychange = 'webkitvisibilitychange';
				visibilityState  = 'webkitVisibilityState';
			}

			if ( hidden ) {
				if ( document[ hidden ] ) {
					settings.hasFocus = false;
				}

				$document.on( visibilitychange + '.imagifybeat', function() {
					if ( 'hidden' === document[ visibilityState ] ) {
						blurred();
						w.clearInterval( settings.checkFocusTimer );
					} else {
						focused();
						if ( document.hasFocus ) {
							settings.checkFocusTimer = w.setInterval( checkFocus, 10000 );
						}
					}
				});
			}

			// Use document.hasFocus() if available.
			if ( document.hasFocus ) {
				settings.checkFocusTimer = w.setInterval( checkFocus, 10000 );
			}

			$( w ).on( 'unload.imagifybeat', function() {
				// Don't connect anymore.
				settings.suspend = true;

				// Abort the last request if not completed.
				if ( settings.xhr && 4 !== settings.xhr.readyState ) {
					settings.xhr.abort();
				}
			} );

			// Check for user activity every 30 seconds.
			w.setInterval( checkUserActivity, 30000 );

			// Start one tick after DOM ready.
			$document.ready( function() {
				settings.lastTick = time();
				scheduleNextTick();
			} );
		}

		/**
		 * Returns the current time according to the browser.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {int} Returns the current time.
		 */
		function time() {
			return (new Date()).getTime();
		}

		/**
		 * Checks if the iframe is from the same origin.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {bool} Returns whether or not the iframe is from the same origin.
		 */
		function isLocalFrame( frame ) {
			var origin, src = frame.src;

			/*
			 * Need to compare strings as WebKit doesn't throw JS errors when iframes have different origin. It throws uncatchable exceptions.
			 */
			if ( src && /^https?:\/\//.test( src ) ) {
				origin = w.location.origin ? w.location.origin : w.location.protocol + '//' + w.location.host;

				if ( src.indexOf( origin ) !== 0 ) {
					return false;
				}
			}

			try {
				if ( frame.contentWindow.document ) {
					return true;
				}
			} catch ( e ) {} // eslint-disable-line no-empty

			return false;
		}

		/**
		 * Checks if the document's focus has changed.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function checkFocus() {
			if ( settings.hasFocus && ! document.hasFocus() ) {
				blurred();
			} else if ( ! settings.hasFocus && document.hasFocus() ) {
				focused();
			}
		}

		/**
		 * Sets error state and fires an event on XHR errors or timeout.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @param  {string} error      The error type passed from the XHR.
		 * @param  {int}    httpStatus The HTTP status code passed from jqXHR (200, 404, 500, etc.).
		 * @return {void}
		 */
		function setErrorState( error, httpStatus ) {
			var trigger;

			if ( error ) {
				switch ( error ) {
					case 'abort':
						// Do nothing.
						break;
					case 'timeout':
						// No response for 30 sec.
						trigger = true;
						break;
					case 'error':
						if ( 503 === httpStatus && settings.hasConnected ) {
							trigger = true;
							break;
						}
						/* falls through */
					case 'parsererror':
					case 'empty':
					case 'unknown':
						settings.errorcount++;

						if ( settings.errorcount > 2 && settings.hasConnected ) {
							trigger = true;
						}

						break;
				}

				if ( trigger && ! hasConnectionError() ) {
					settings.connectionError = true;
					$document.trigger( 'imagifybeat-connection-lost', [ error, httpStatus ] );

					if ( w.wp.hooks ) {
						w.wp.hooks.doAction( 'imagifybeat.connection-lost', error, httpStatus );
					}
				}
			}
		}

		/**
		 * Clears the error state and fires an event if there is a connection error.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function clearErrorState() {
			// Has connected successfully.
			settings.hasConnected = true;

			if ( hasConnectionError() ) {
				settings.errorcount = 0;
				settings.connectionError = false;
				$document.trigger( 'imagifybeat-connection-restored' );

				if ( w.wp.hooks ) {
					w.wp.hooks.doAction( 'imagifybeat.connection-restored' );
				}
			}
		}

		/**
		 * Gathers the data and connects to the server.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function connect() {
			var ajaxData, imagifybeatData;

			// If the connection to the server is slower than the interval,
			// imagifybeat connects as soon as the previous connection's response is received.
			if ( settings.connecting || settings.suspend ) {
				return;
			}

			settings.lastTick = time();

			imagifybeatData = $.extend( {}, settings.queue );
			// Clear the data queue. Anything added after this point will be sent on the next tick.
			settings.queue = {};

			$document.trigger( 'imagifybeat-send', [ imagifybeatData ] );

			if ( w.wp.hooks ) {
				w.wp.hooks.doAction( 'imagifybeat.send', imagifybeatData );
			}

			ajaxData = {
				data:      imagifybeatData,
				interval:  settings.tempInterval ? settings.tempInterval / 1000 : settings.mainInterval / 1000,
				_nonce:    typeof w.imagifybeatSettings === 'object' ? w.imagifybeatSettings.nonce : '',
				action:    'imagifybeat',
				screen_id: settings.screenId,
				has_focus: settings.hasFocus
			};

			if ( 'customize' === settings.screenId  ) {
				ajaxData.wp_customize = 'on';
			}

			settings.connecting = true;
			settings.xhr        = $.ajax( {
				url:      settings.url,
				type:     'post',
				timeout:  60000, // Throw an error if not completed after 60 sec.
				data:     ajaxData,
				dataType: 'json'
			} ).always( function() {
				settings.connecting = false;
				scheduleNextTick();
			} ).done( function( response, textStatus, jqXHR ) {
				var newInterval;

				if ( ! response ) {
					setErrorState( 'empty' );
					return;
				}

				clearErrorState();

				if ( response.nonces_expired ) {
					$document.trigger( 'imagifybeat-nonces-expired' );

					if ( w.wp.hooks ) {
						w.wp.hooks.doAction( 'imagifybeat.nonces-expired' );
					}
				}

				// Change the interval from PHP
				if ( response.imagifybeat_interval ) {
					newInterval = response.imagifybeat_interval;
					delete response.imagifybeat_interval;
				}

				// Update the imagifybeat nonce if set.
				if ( response.imagifybeat_nonce && typeof w.imagifybeatSettings === 'object' ) {
					w.imagifybeatSettings.nonce = response.imagifybeat_nonce;
					delete response.imagifybeat_nonce;
				}

				$document.trigger( 'imagifybeat-tick', [ response, textStatus, jqXHR ] );

				if ( w.wp.hooks ) {
					w.wp.hooks.doAction( 'imagifybeat.tick', response, textStatus, jqXHR );
				}

				// Do this last. Can trigger the next XHR if connection time > 5 sec. and newInterval == 'fast'.
				if ( newInterval ) {
					interval( newInterval );
				}
			} ).fail( function( jqXHR, textStatus, error ) {
				setErrorState( textStatus || 'unknown', jqXHR.status );
				$document.trigger( 'imagifybeat-error', [ jqXHR, textStatus, error ] );

				if ( w.wp.hooks ) {
					w.wp.hooks.doAction( 'imagifybeat.error', jqXHR, textStatus, error );
				}
			} );
		}

		/**
		 * Schedules the next connection.
		 *
		 * Fires immediately if the connection time is longer than the interval.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function scheduleNextTick() {
			var delta  = time() - settings.lastTick,
				interv = settings.mainInterval;

			if ( settings.suspend ) {
				return;
			}

			if ( ! settings.hasFocus && settings.suspendEnabled ) {
				// When no user activity or the window lost focus, increase polling interval to 120 seconds, but only if suspend is enabled.
				interv = 120000; // 120 sec.
			} else if ( settings.countdown > 0 && settings.tempInterval ) {
				interv = settings.tempInterval;
				settings.countdown--;

				if ( settings.countdown < 1 ) {
					settings.tempInterval = 0;
				}
			}

			if ( settings.minimalInterval && interv < settings.minimalInterval ) {
				interv = settings.minimalInterval;
			}

			w.clearTimeout( settings.beatTimer );

			if ( delta < interv ) {
				settings.beatTimer = w.setTimeout(
					function() {
						connect();
					},
					interv - delta
				);
			} else {
				connect();
			}
		}

		/**
		 * Sets the internal state when the browser w becomes hidden or loses focus.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function blurred() {
			settings.hasFocus = false;
		}

		/**
		 * Sets the internal state when the browser w becomes visible or is in focus.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function focused() {
			settings.userActivity = time();

			// Resume if suspended
			settings.suspend = false;

			if ( ! settings.hasFocus ) {
				settings.hasFocus = true;
				scheduleNextTick();
			}
		}

		/**
		 * Runs when the user becomes active after a period of inactivity.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function userIsActive() {
			settings.userActivityEvents = false;
			$document.off( '.imagifybeat-active' );

			$( 'iframe' ).each( function( i, frame ) {
				if ( isLocalFrame( frame ) ) {
					$( frame.contentWindow ).off( '.imagifybeat-active' );
				}
			} );

			focused();
		}

		/**
		 * Checks for user activity.
		 *
		 * Runs every 30 sec. Sets 'hasFocus = true' if user is active and the w is
		 * in the background. Sets 'hasFocus = false' if the user has been inactive
		 * (no mouse or keyboard activity) for 5 min. even when the w has focus.
		 *
		 * @since  1.9.3
		 * @access private
		 *
		 * @return {void}
		 */
		function checkUserActivity() {
			var lastActive = settings.userActivity ? time() - settings.userActivity : 0;

			// Set hasFocus to false when no mouse or keyboard activity for 5 min.
			if ( lastActive > 300000 && settings.hasFocus ) {
				blurred();
			}

			// Suspend after 10 min. of inactivity.
			if ( settings.suspendEnabled && lastActive > 600000 ) {
				settings.suspend = true;
			}

			if ( ! settings.userActivityEvents ) {
				$document.on( 'mouseover.imagifybeat-active keyup.imagifybeat-active touchend.imagifybeat-active', function() {
					userIsActive();
				} );

				$( 'iframe' ).each( function( i, frame ) {
					if ( isLocalFrame( frame ) ) {
						$( frame.contentWindow ).on( 'mouseover.imagifybeat-active keyup.imagifybeat-active touchend.imagifybeat-active', function() {
							userIsActive();
						} );
					}
				} );

				settings.userActivityEvents = true;
			}
		}

		// Public methods.

		/**
		 * Checks whether the w (or any local iframe in it) has focus, or the user
		 * is active.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {bool} True if the w or the user is active.
		 */
		function hasFocus() {
			return settings.hasFocus;
		}

		/**
		 * Checks whether there is a connection error.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {bool} True if a connection error was found.
		 */
		function hasConnectionError() {
			return settings.connectionError;
		}

		/**
		 * Connects as soon as possible regardless of 'hasFocus' state.
		 *
		 * Will not open two concurrent connections. If a connection is in progress,
		 * will connect again immediately after the current connection completes.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {void}
		 */
		function connectNow() {
			settings.lastTick = 0;
			scheduleNextTick();
		}

		/**
		 * Disables suspending.
		 *
		 * Should be used only when Imagifybeat is performing critical tasks like
		 * autosave, post-locking, etc. Using this on many screens may overload the
		 * user's hosting account if several browser ws/tabs are left open for a
		 * long time.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {void}
		 */
		function disableSuspend() {
			settings.suspendEnabled = false;
		}

		/**
		 * Enables suspending.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {void}
		 */
		function enableSuspend() {
			settings.suspendEnabled = true;
		}

		/**
		 * Gets/Sets the interval.
		 *
		 * When setting to 'fast' or 5, the interval is 5 seconds for the next 30 ticks
		 * (for 2 minutes and 30 seconds) by default. In this case the number of 'ticks'
		 * can be passed as second argument. If the window doesn't have focus, the
		 * interval slows down to 2 min.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @param  {string|int} speed Interval: 'fast' or 5, 15, 30, 60, 120. Fast equals 5.
		 * @param  {string}     ticks Tells how many ticks before the interval reverts back. Used with speed = 'fast' or 5.
		 * @return {int}              Current interval in seconds.
		 */
		function interval( speed, ticks ) {
			var newInterval,
				oldInterval = settings.tempInterval ? settings.tempInterval : settings.mainInterval;

			if ( speed ) {
				switch ( speed ) {
					case 'fast':
					case 5:
						newInterval = 5000;
						break;
					case 15:
						newInterval = 15000;
						break;
					case 30:
						newInterval = 30000;
						break;
					case 60:
						newInterval = 60000;
						break;
					case 120:
						newInterval = 120000;
						break;
					case 'long-polling':
						// Allow long polling, (experimental)
						settings.mainInterval = 0;
						return 0;
					default:
						newInterval = settings.originalInterval;
				}

				if ( settings.minimalInterval && newInterval < settings.minimalInterval ) {
					newInterval = settings.minimalInterval;
				}

				if ( 5000 === newInterval ) {
					ticks = parseInt( ticks, 10 ) || 30;
					ticks = ticks < 1 || ticks > 30 ? 30 : ticks;

					settings.countdown    = ticks;
					settings.tempInterval = newInterval;
				} else {
					settings.countdown    = 0;
					settings.tempInterval = 0;
					settings.mainInterval = newInterval;
				}

				// Change the next connection time if new interval has been set.
				// Will connect immediately if the time since the last connection
				// is greater than the new interval.
				if ( newInterval !== oldInterval ) {
					scheduleNextTick();
				}
			}

			return settings.tempInterval ? settings.tempInterval / 1000 : settings.mainInterval / 1000;
		}

		/**
		 * Resets the interval.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @return {int} Current interval in seconds.
		 */
		function resetInterval() {
			return interval( settings.originalInterval );
		}

		/**
		 * Enqueues data to send with the next XHR.
		 *
		 * As the data is send asynchronously, this function doesn't return the XHR
		 * response. To see the response, use the custom jQuery event 'imagifybeat-tick'
		 * on the document, example:
		 *      $(document).on( 'imagifybeat-tick.myname', function( event, data, textStatus, jqXHR ) {
		 *          // code
		 *      });
		 * If the same 'handle' is used more than once, the data is not overwritten when
		 * the third argument is 'true'. Use `imagify.beat.isQueued('handle')` to see if
		 * any data is already queued for that handle.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @param  {string} handle      Unique handle for the data, used in PHP to receive the data.
		 * @param  {mixed}  data        The data to send.
		 * @param  {bool}   noOverwrite Whether to overwrite existing data in the queue.
		 * @return {bool}               True if the data was queued.
		 */
		function enqueue( handle, data, noOverwrite ) {
			if ( handle ) {
				if ( noOverwrite && this.isQueued( handle ) ) {
					return false;
				}

				settings.queue[handle] = data;
				return true;
			}
			return false;
		}

		/**
		 * Checks if data with a particular handle is queued.
		 *
		 * @since 1.9.3
		 *
		 * @param  {string} handle The handle for the data.
		 * @return {bool}          True if the data is queued with this handle.
		 */
		function isQueued( handle ) {
			if ( handle ) {
				return settings.queue.hasOwnProperty( handle );
			}
		}

		/**
		 * Removes data with a particular handle from the queue.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @param {string} handle The handle for the data.
		 */
		function dequeue( handle ) {
			if ( handle ) {
				delete settings.queue[handle];
			}
		}

		/**
		 * Gets data that was enqueued with a particular handle.
		 *
		 * @since    1.9.3
		 * @memberOf imagify.beat.prototype
		 *
		 * @param  {string} handle The handle for the data.
		 * @return {mixed}         The data or undefined.
		 */
		function getQueuedItem( handle ) {
			if ( handle ) {
				return this.isQueued( handle ) ? settings.queue[ handle ] : undefined;
			}
		}

		initialize();

		// Expose public methods.
		return {
			hasFocus:           hasFocus,
			connectNow:         connectNow,
			disableSuspend:     disableSuspend,
			enableSuspend:      enableSuspend,
			interval:           interval,
			resetInterval:      resetInterval,
			hasConnectionError: hasConnectionError,
			enqueue:            enqueue,
			dequeue:            dequeue,
			isQueued:           isQueued,
			getQueuedItem:      getQueuedItem
		};
	};

	/**
	 * Contains the Imagifybeat API.
	 *
	 * @namespace imagify.beat
	 * @type      {Imagifybeat}
	 */
	w.imagify.beat = new Imagifybeat();

} )( jQuery, document, window );
