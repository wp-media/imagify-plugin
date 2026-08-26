/**
 * Unit tests for assets/js/helpers.js.
 *
 * Note there is no loader, sandbox, or stubbing here - a plain require. That is
 * the point of helpers.js: pure functions with a real seam to test against.
 */
const helpers = require( '../../assets/js/helpers.js' );

describe( 'humanSize', () => {
	// A non-breaking space separates the number from the unit.
	const NBSP = '\xA0';

	it( 'returns 0 kB for zero rather than dividing by a log of zero', () => {
		expect( helpers.humanSize( 0 ) ).toBe( '0' + NBSP + 'kB' );
	} );

	it( 'formats bytes, kilobytes and megabytes', () => {
		expect( helpers.humanSize( 512 ) ).toBe( '512.00' + NBSP + 'B' );
		expect( helpers.humanSize( 1024 ) ).toBe( '1.00' + NBSP + 'kB' );
		expect( helpers.humanSize( 1048576 ) ).toBe( '1.00' + NBSP + 'MB' );
	} );

	it( 'always keeps two decimals', () => {
		expect( helpers.humanSize( 1536 ) ).toBe( '1.50' + NBSP + 'kB' );
	} );
} );

describe( 'formatQuota', () => {
	it( 'treats -1 as unlimited', () => {
		expect( helpers.formatQuota( -1 ) ).toBe( 'Unlimited' );
	} );

	it( 'switches to GB at 1000 MB', () => {
		expect( helpers.formatQuota( 999 ) ).toBe( '999 MB' );
		expect( helpers.formatQuota( 1000 ) ).toBe( '1 GB' );
		expect( helpers.formatQuota( 5000 ) ).toBe( '5 GB' );
	} );
} );

describe( 'monthlyFromAnnual', () => {
	it( 'rounds the monthly equivalent to two decimals', () => {
		// 49.9 / 12 = 4.1583..., must not leak extra decimals into the UI.
		expect( helpers.monthlyFromAnnual( 49.9 ) ).toBe( 4.16 );
		expect( helpers.monthlyFromAnnual( 120 ) ).toBe( 10 );
	} );
} );

describe( 'applyDiscount', () => {
	it( 'applies a percentage to both the monthly and yearly price', () => {
		// 30% off 4.99 monthly / 49.9 annual.
		const priced = helpers.applyDiscount( 4.99, 49.9, 30 );

		expect( priced.monthly ).toBeCloseTo( 3.493, 5 );
		expect( priced.yearly ).toBe( 2.91 );
	} );

	it( 'is a no-op at 0 percent', () => {
		const priced = helpers.applyDiscount( 4.99, 49.9, 0 );

		expect( priced.monthly ).toBeCloseTo( 4.99, 5 );
		expect( priced.yearly ).toBe( helpers.monthlyFromAnnual( 49.9 ) );
	} );

	it( 'makes everything free at 100 percent', () => {
		expect( helpers.applyDiscount( 4.99, 49.9, 100 ) ).toEqual( {
			monthly: 0,
			yearly:  0
		} );
	} );

	it( 'never returns NaN for a zero-priced plan', () => {
		const priced = helpers.applyDiscount( 0, 0, 30 );

		expect( Number.isNaN( priced.monthly ) ).toBe( false );
		expect( Number.isNaN( priced.yearly ) ).toBe( false );
	} );
} );

describe( 'splitPrice', () => {
	it( 'keeps two decimals as-is', () => {
		expect( helpers.splitPrice( '4.99' ) ).toEqual( [ '4', '99' ] );
	} );

	it( 'pads a single decimal', () => {
		expect( helpers.splitPrice( '4.9' ) ).toEqual( [ '4', '90' ] );
	} );

	it( 'truncates beyond two decimals rather than rounding', () => {
		expect( helpers.splitPrice( '4.999' ) ).toEqual( [ '4', '99' ] );
	} );

	it( 'handles zero', () => {
		expect( helpers.splitPrice( '0' ) ).toEqual( [ '0', '00' ] );
	} );

	it( 'handles a whole number instead of throwing', () => {
		// Regression guard: the inline version did `'5'.split('.')[1].length` and
		// threw a TypeError, which broke the pricing modal render entirely.
		expect( helpers.splitPrice( 5 ) ).toEqual( [ '5', '00' ] );
		expect( helpers.splitPrice( '10' ) ).toEqual( [ '10', '00' ] );
	} );

	it( 'accepts a number as well as a string', () => {
		expect( helpers.splitPrice( 4.99 ) ).toEqual( [ '4', '99' ] );
	} );
} );

describe( 'getPromoAppliesTo', () => {
	it( 'deduplicates plan names', () => {
		expect( helpers.getPromoAppliesTo( {
			applies_to: [
				{ plan_name: 'lite' },
				{ plan_name: 'lite' },
				{ plan_name: 'pro' }
			]
		} ) ).toEqual( [ 'lite', 'pro' ] );
	} );

	it( 'wraps a non-array value', () => {
		expect( helpers.getPromoAppliesTo( { applies_to: 'all' } ) ).toEqual( [ 'all' ] );
	} );

	it( 'returns an empty list for an empty array', () => {
		expect( helpers.getPromoAppliesTo( { applies_to: [] } ) ).toEqual( [] );
	} );
} );

describe( 'sanitizeId', () => {
	it( 'coerces a numeric string to an integer', () => {
		expect( helpers.sanitizeId( '42' ) ).toBe( 42 );
	} );

	it( 'stops at the first non-digit', () => {
		expect( helpers.sanitizeId( '42abc' ) ).toBe( 42 );
	} );

	it( 'returns NaN for something with no leading digits', () => {
		expect( Number.isNaN( helpers.sanitizeId( 'abc' ) ) ).toBe( true );
	} );
} );
