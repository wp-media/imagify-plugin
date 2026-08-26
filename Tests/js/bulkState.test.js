/**
 * Tests for _dev/src/lib/bulk-state.js.
 *
 * bulk.js itself is a jQuery IIFE with import-time side effects and cannot be
 * loaded in a test. This covers the decision logic extracted out of it.
 */
import { findBlockingError, buildAjaxUrl, normalizeChartData } from '../../_dev/src/lib/bulk-state.js';

describe( 'findBlockingError', () => {
	// A config where nothing blocks, used as the baseline.
	const healthy = { keyIsValid: true };

	it( 'returns an empty string when nothing blocks', () => {
		expect( findBlockingError( healthy ) ).toBe( '' );
	} );

	it( 'treats a missing API key flag as invalid rather than valid', () => {
		// The original condition is `! imagifyBulk.keyIsValid`, so an absent flag
		// must block. Getting this backwards would let a bulk run start unauthenticated.
		expect( findBlockingError( {} ) ).toBe( 'invalidApiKey' );
		expect( findBlockingError( { keyIsValid: false } ) ).toBe( 'invalidApiKey' );
	} );

	it( 'detects each blocking condition on its own', () => {
		expect( findBlockingError( { ...healthy, curlMissing: true } ) ).toBe( 'curlMissing' );
		expect( findBlockingError( { ...healthy, editorMissing: true } ) ).toBe( 'editorMissing' );
		expect( findBlockingError( { ...healthy, extHttpBlocked: true } ) ).toBe( 'extHttpBlocked' );
		expect( findBlockingError( { ...healthy, apiDown: true } ) ).toBe( 'apiDown' );
		expect( findBlockingError( { ...healthy, isOverQuota: true } ) ).toBe( 'isOverQuota' );
	} );

	it( 'keeps the original priority order when several conditions apply', () => {
		// Order is curl > editor > extHttp > apiDown > invalidKey > overQuota.
		// It decides which message the user actually sees.
		expect( findBlockingError( {
			curlMissing:    true,
			editorMissing:  true,
			extHttpBlocked: true,
			apiDown:        true,
			isOverQuota:    true,
			keyIsValid:     false
		} ) ).toBe( 'curlMissing' );

		expect( findBlockingError( {
			editorMissing: true,
			apiDown:       true,
			keyIsValid:    false
		} ) ).toBe( 'editorMissing' );

		expect( findBlockingError( { apiDown: true, keyIsValid: false } ) ).toBe( 'apiDown' );
		expect( findBlockingError( { keyIsValid: false, isOverQuota: true } ) ).toBe( 'invalidApiKey' );
	} );

	it( 'does not throw on a missing config', () => {
		expect( findBlockingError( undefined ) ).toBe( 'invalidApiKey' );
	} );
} );

describe( 'buildAjaxUrl', () => {
	const args = {
		baseUrl:     '/wp-admin/admin-ajax.php',
		concat:      '?',
		nonce:       'abc123',
		ajaxActions: { optimize: 'imagify_bulk_optimize' }
	};

	it( 'builds the base URL with the nonce and mapped action', () => {
		expect( buildAjaxUrl( args, 'optimize' ) ).toBe(
			'/wp-admin/admin-ajax.php?_wpnonce=abc123&action=imagify_bulk_optimize'
		);
	} );

	it( 'respects a base URL that already has a query string', () => {
		expect( buildAjaxUrl( { ...args, concat: '&' }, 'optimize' ) ).toContain( '.php&_wpnonce=' );
	} );

	it( 'appends the context when the item has one', () => {
		expect( buildAjaxUrl( args, 'optimize', { context: 'wp' } ) ).toContain( '&context=wp' );
	} );

	it( 'sends optimization level 0, which is a real level', () => {
		// `Number.isInteger( 0 )` is true, so level 0 must survive. A truthiness
		// check here would silently drop it and change what gets optimized.
		expect( buildAjaxUrl( args, 'optimize', { level: 0 } ) ).toContain( '&optimization_level=0' );
	} );

	it( 'omits the level when it is absent or not an integer', () => {
		expect( buildAjaxUrl( args, 'optimize', {} ) ).not.toContain( 'optimization_level' );
		expect( buildAjaxUrl( args, 'optimize', { level: '2' } ) ).not.toContain( 'optimization_level' );
		expect( buildAjaxUrl( args, 'optimize', { level: 1.5 } ) ).not.toContain( 'optimization_level' );
	} );
} );

describe( 'normalizeChartData', () => {
	it( 'forces the first slice so an all-zero doughnut still renders', () => {
		expect( normalizeChartData( [ 0, 0, 0 ] ) ).toEqual( [ 1, 0, 0 ] );
	} );

	it( 'leaves a dataset with any value alone', () => {
		expect( normalizeChartData( [ 0, 5, 0 ] ) ).toEqual( [ 0, 5, 0 ] );
	} );

	it( 'does not mutate its input', () => {
		const original = [ 0, 0, 0 ];

		normalizeChartData( original );

		expect( original ).toEqual( [ 0, 0, 0 ] );
	} );

	it( 'returns an empty array for empty or invalid input', () => {
		expect( normalizeChartData( [] ) ).toEqual( [] );
		expect( normalizeChartData( undefined ) ).toEqual( [] );
		expect( normalizeChartData( 'nope' ) ).toEqual( [] );
	} );
} );
