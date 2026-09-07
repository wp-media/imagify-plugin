/* eslint-env browser, node */
/**
 * Imagify pure helpers.
 *
 * Deliberately free of jQuery, the DOM, and every `imagify*` config global, so it
 * can be imported directly by a unit test with no loader, sandbox, or stubbing.
 * Everything here must stay a plain input-to-output function; anything needing the
 * page belongs in the file that uses it.
 *
 * Attached to `window.imagify` for the admin scripts, and exported through
 * `module.exports` for Jest. Both guards are defensive so the file is safe to load
 * in either place.
 */
( function ( w ) {
	/**
	 * Format a number of bytes as a human readable size.
	 *
	 * @param  {number} bytes Number of bytes.
	 * @return {string}       e.g. "1.00 MB". Non-breaking space before the unit.
	 */
	function humanSize( bytes ) {
		var sizes = [ 'B', 'kB', 'MB' ],
			i;

		if ( 0 === bytes ) {
			return '0\xA0kB';
		}

		i = parseInt( Math.floor( Math.log( bytes ) / Math.log( 1024 ) ), 10 );

		return ( bytes / Math.pow( 1024, i ) ).toFixed( 2 ) + '\xA0' + sizes[ i ];
	}

	/**
	 * Format a plan quota for display.
	 *
	 * @param  {number} quota Quota in MB. -1 means unlimited.
	 * @return {string}       e.g. "Unlimited", "1 GB", "500 MB".
	 */
	function formatQuota( quota ) {
		if ( -1 === quota ) {
			return 'Unlimited';
		}

		return quota >= 1000 ? quota / 1000 + ' GB' : quota + ' MB';
	}

	/**
	 * Monthly equivalent of an annual cost, rounded to 2 decimals.
	 *
	 * @param  {number} annual Annual cost.
	 * @return {number}
	 */
	function monthlyFromAnnual( annual ) {
		return Math.round( annual / 12 * 100 ) / 100;
	}

	/**
	 * Apply a percentage discount to a plan's prices.
	 *
	 * @param  {number} monthly     Monthly cost.
	 * @param  {number} annual      Annual cost.
	 * @param  {number} couponValue Discount in percent, e.g. 30 for -30%.
	 * @return {{monthly: number, yearly: number}}
	 */
	function applyDiscount( monthly, annual, couponValue ) {
		var percent = ( 100 - couponValue ) / 100;

		return {
			monthly: monthly * percent,
			yearly:  monthlyFromAnnual( annual * percent )
		};
	}

	/**
	 * Progress of a bulk run.
	 *
	 * The workload can grow mid-run (new uploads), so the largest total seen is used
	 * as the denominator - otherwise `processed` goes negative and the percentage
	 * exceeds 100. See #760 and #865.
	 *
	 * @param  {number} total     Number of media to process when the run started.
	 * @param  {number} remaining Number of media still waiting to be processed.
	 * @return {{processed: number, total: number, percent: number}}
	 */
	function getProgress( total, remaining ) {
		var effectiveTotal, processed;

		total     = Math.max( parseInt( total, 10 ) || 0, 0 );
		remaining = Math.max( parseInt( remaining, 10 ) || 0, 0 );

		effectiveTotal = Math.max( total, remaining );
		processed      = effectiveTotal - remaining;

		return {
			processed: processed,
			total:     effectiveTotal,
			percent:   effectiveTotal > 0 ? Math.floor( processed / effectiveTotal * 100 ) : 0
		};
	}

	/**
	 * Split a price into its integer part and a two-digit decimal part.
	 *
	 * The pricing modal built this inline in three places. It crashed on a whole
	 * number: `'5'.split('.')[1]` is undefined, so reading `.length` threw a
	 * TypeError and the modal failed to render. A price with no decimal separator
	 * now yields '00'.
	 *
	 * @param  {string|number} value A price, e.g. 4.99, '4.9', 5.
	 * @return {string[]}            [ integerPart, twoDigitDecimalPart ]
	 */
	function splitPrice( value ) {
		var parts    = ( '' + value ).split( '.' ),
			decimals = parts.length > 1 ? '' + parts[ 1 ] : '';

		if ( '' === decimals ) {
			decimals = '00';
		} else if ( 1 === decimals.length ) {
			decimals = decimals + '0';
		} else {
			decimals = decimals.substring( 0, 2 );
		}

		return [ parts[ 0 ], decimals ];
	}

	/**
	 * Plan names a promotion applies to, deduplicated.
	 *
	 * @param  {object} promo A promotion, whose `applies_to` is either an array of
	 *                        objects carrying `plan_name`, or a bare value.
	 * @return {string[]}
	 */
	function getPromoAppliesTo( promo ) {
		var appliesTo = [];

		if ( ! ( promo.applies_to instanceof Array ) ) {
			return [ promo.applies_to ];
		}

		promo.applies_to.forEach( function ( plan ) {
			if ( ! appliesTo.includes( plan.plan_name ) ) {
				appliesTo.push( plan.plan_name );
			}
		} );

		return appliesTo;
	}

	/**
	 * Coerce a media ID to an integer.
	 *
	 * @param  {number|string} id A media ID.
	 * @return {number}
	 */
	function sanitizeId( id ) {
		return parseInt( id, 10 );
	}

	var helpers = {
		getProgress:       getProgress,
		getPromoAppliesTo: getPromoAppliesTo,
		sanitizeId:        sanitizeId,
		splitPrice:        splitPrice,
		humanSize:         humanSize,
		formatQuota:       formatQuota,
		monthlyFromAnnual: monthlyFromAnnual,
		applyDiscount:     applyDiscount
	};

	if ( typeof w !== 'undefined' && w ) {
		w.imagify = w.imagify || {};

		for ( var key in helpers ) {
			if ( Object.prototype.hasOwnProperty.call( helpers, key ) ) {
				w.imagify[ key ] = helpers[ key ];
			}
		}
	}

	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = helpers;
	}
} )( typeof window !== 'undefined' ? window : undefined );
