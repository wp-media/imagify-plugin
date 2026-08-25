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

	var helpers = {
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
