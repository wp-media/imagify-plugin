/**
 * Pure decision logic for the bulk optimization page.
 *
 * bulk.js itself cannot be unit tested: it is a jQuery IIFE that reads the
 * `imagifyBulk` global at module-evaluation time, calls init() on load, and can
 * open a modal on import. Everything in here is deliberately free of jQuery, the
 * DOM and every global, so a test can import it directly.
 */

/**
 * Blocking conditions, highest priority first.
 *
 * The order matters: it decides which message the user sees when more than one
 * problem applies. Kept as data so the priority is visible and testable.
 *
 * @type {Array<{code: string, blocked: function(object): boolean}>}
 */
const BLOCKING_CONDITIONS = [
	{ code: 'curlMissing', blocked: ( state ) => !! state.curlMissing },
	{ code: 'editorMissing', blocked: ( state ) => !! state.editorMissing },
	{ code: 'extHttpBlocked', blocked: ( state ) => !! state.extHttpBlocked },
	{ code: 'apiDown', blocked: ( state ) => !! state.apiDown },
	// Note the negation: a missing flag must count as invalid, not as valid.
	{ code: 'invalidApiKey', blocked: ( state ) => ! state.keyIsValid },
	{ code: 'isOverQuota', blocked: ( state ) => !! state.isOverQuota }
];

/**
 * Which blocking condition, if any, stops a bulk run from starting.
 *
 * @param  {object} state The `imagifyBulk` config object.
 * @return {string}       The blocking condition's code, or '' when nothing blocks.
 */
export function findBlockingError( state ) {
	const config = state || {};
	const found = BLOCKING_CONDITIONS.find( ( condition ) => condition.blocked( config ) );

	return found ? found.code : '';
}

/**
 * Build the AJAX URL for a bulk action.
 *
 * @param  {object} args             Everything the URL needs, passed in rather than read from globals.
 * @param  {string} args.baseUrl     WordPress `ajaxurl`.
 * @param  {string} args.concat      '?' or '&', depending on whether baseUrl already has a query.
 * @param  {string} args.nonce       The AJAX nonce.
 * @param  {object} args.ajaxActions Map of action name to registered AJAX action.
 * @param  {string} action           The action to look up.
 * @param  {object} [item]           Optional item carrying `context` and `level`.
 * @return {string}
 */
export function buildAjaxUrl( args, action, item ) {
	let url = args.baseUrl + args.concat + '_wpnonce=' + args.nonce + '&action=' + args.ajaxActions[ action ];

	if ( item && item.context ) {
		url += '&context=' + item.context;
	}

	/*
	 * Integers only, on purpose: level 0 is meaningful and must be sent, while
	 * undefined or a numeric string must not be.
	 */
	if ( item && Number.isInteger( item.level ) ) {
		url += '&optimization_level=' + item.level;
	}

	return url;
}

/**
 * Make chart data safe to render.
 *
 * An all-zero dataset draws nothing at all, so the first slice is forced to 1 to
 * keep an empty doughnut visible.
 *
 * @param  {number[]} data Raw chart data.
 * @return {number[]}      A new array; the input is not mutated.
 */
export function normalizeChartData( data ) {
	if ( ! Array.isArray( data ) || ! data.length ) {
		return [];
	}

	const normalized = data.slice();

	if ( 0 === normalized.reduce( ( total, value ) => total + value, 0 ) ) {
		normalized[ 0 ] = 1;
	}

	return normalized;
}
