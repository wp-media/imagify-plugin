/**
 * Tests for `getProgress()`.
 *
 * Previously this file loaded the whole of assets/js/options.js inside a node:vm
 * context behind a Proxy-based fake jQuery, because the function lived inside a
 * jQuery IIFE with no export. `getProgress` now lives in assets/js/helpers.js, so
 * this is a plain require and the sandbox is gone.
 */
const { getProgress } = require( '../../assets/js/helpers.js' );

describe( 'getProgress', () => {
	it( 'never goes negative when uploads grow the workload mid-run (#760)', () => {
		// 10 media at the start, but 12 are now waiting: the workload grew.
		expect( getProgress( 10, 12 ) ).toEqual( {
			processed: 0,
			total:     12,
			percent:   0
		} );
	} );

	it( 'never divides by zero when the format is switched mid-run (#865)', () => {
		expect( getProgress( 0, 0 ) ).toEqual( {
			processed: 0,
			total:     0,
			percent:   0
		} );
	} );

	it( 'reports a healthy run in progress unchanged', () => {
		expect( getProgress( 10, 4 ) ).toEqual( {
			processed: 6,
			total:     10,
			percent:   60
		} );
	} );

	it( 'reports 100% for a completed run', () => {
		expect( getProgress( 10, 0 ) ).toEqual( {
			processed: 10,
			total:     10,
			percent:   100
		} );
	} );

	it( 'degrades non-numeric input to zero instead of NaN', () => {
		expect( getProgress( 'nope', undefined ) ).toEqual( {
			processed: 0,
			total:     0,
			percent:   0
		} );
	} );

	it( 'clamps negative input to zero', () => {
		expect( getProgress( -5, -2 ) ).toEqual( {
			processed: 0,
			total:     0,
			percent:   0
		} );
	} );

	it( 'floors the percentage rather than rounding it up', () => {
		// 1 of 3 processed is 33.33%, must not display as 34%.
		expect( getProgress( 3, 2 ).percent ).toBe( 33 );
	} );

	it( 'stays within bounds across the whole small-input space', () => {
		for ( let total = 0; total <= 50; total++ ) {
			for ( let remaining = 0; remaining <= 50; remaining++ ) {
				const progress = getProgress( total, remaining );

				expect( Number.isFinite( progress.percent ) ).toBe( true );
				expect( progress.percent ).toBeGreaterThanOrEqual( 0 );
				expect( progress.percent ).toBeLessThanOrEqual( 100 );
				expect( progress.processed ).toBeGreaterThanOrEqual( 0 );
				expect( progress.processed ).toBeLessThanOrEqual( progress.total );
			}
		}
	} );
} );
