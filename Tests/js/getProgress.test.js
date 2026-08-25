/* eslint-env node */
/**
 * Tests for `window.imagify.optionsBulk.getProgress()` in assets/js/options.js.
 *
 * Run with: npm run test:unit
 *
 * `options.js` is a browser bundle of jQuery IIFEs, not a module, so it is loaded here inside a
 * VM context with the minimum stubs it touches at load time. That keeps the test honest: it
 * exercises the real shipped file rather than a copy of the logic.
 */

const assert = require( 'node:assert' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );

/**
 * Builds a chainable no-op stand-in for a jQuery object.
 *
 * Every property access returns the same callable proxy, so any chain `options.js` builds at load
 * time resolves without us having to enumerate the jQuery surface.
 *
 * @return {Proxy} A callable, infinitely chainable proxy.
 */
function chainable() {
	const fn = function () {
		return fn;
	};

	return new Proxy( fn, {
		get( target, prop ) {
			if ( 'length' === prop ) {
				return 0;
			}

			if ( Symbol.toPrimitive === prop || 'toString' === prop ) {
				return () => '';
			}

			return chainable();
		},
		apply() {
			return chainable();
		},
	} );
}

/**
 * Loads assets/js/options.js in a sandbox and returns the getProgress helper.
 *
 * @return {Function} The `getProgress` method bound to its object.
 */
function loadGetProgress() {
	const file = path.join( __dirname, '..', '..', 'assets', 'js', 'options.js' );
	const code = fs.readFileSync( file, 'utf8' );

	const jQuery = chainable();

	const sandbox = {
		window: {},
		document: {},
		jQuery,
		// The bulk IIFE is guarded by `imagifyOptions.bulk`; without it optionsBulk is never
		// defined. `progress_next_gen.total === false` keeps init() out of the render branch.
		imagifyOptions: {
			bulk: {
				imagifybeatIDs: { progress: 'progress', requirements: 'requirements' },
				progress_next_gen: { total: false, remaining: false },
				labels: {},
				contexts: [ 'wp' ],
			},
		},
		ajaxurl: '',
		swal: Object.assign( () => ( { then: () => {}, catch: () => {} } ), { noop: () => {} } ),
	};

	sandbox.window.imagify = { concat: '?', beat: chainable(), template: () => () => '' };
	sandbox.window.jQuery = jQuery;
	sandbox.window.document = sandbox.document;
	sandbox.window.window = sandbox.window;
	sandbox.self = sandbox.window;
	sandbox.globalThis = sandbox;

	vm.createContext( sandbox );
	vm.runInContext( code, sandbox, { filename: 'options.js' } );

	const bulk = sandbox.window.imagify.optionsBulk;

	assert.ok( bulk, 'window.imagify.optionsBulk should be defined after loading options.js' );
	assert.strictEqual( typeof bulk.getProgress, 'function', 'getProgress should be a function' );

	return bulk.getProgress.bind( bulk );
}

const loaded = loadGetProgress();

/**
 * Calls getProgress and copies the result into this realm.
 *
 * The helper runs inside a VM context, so its return value carries that context's `Object`
 * prototype and `deepStrictEqual` would reject structurally identical values.
 *
 * @param {*} total     Number of media to process when the run started.
 * @param {*} remaining Number of media still waiting to be processed.
 *
 * @return {object} A plain object with `processed`, `total` and `percent` keys.
 */
function getProgress( total, remaining ) {
	const result = loaded( total, remaining );

	return {
		processed: result.processed,
		total: result.total,
		percent: result.percent,
	};
}

it( 'the count never goes negative when uploads grow the workload mid-run (#760)', () => {
	// Snapshot said 23; two uploads during the run pushed the live count to 25.
	assert.deepStrictEqual( getProgress( 23, 25 ), { processed: 0, total: 25, percent: 0 } );
} );

it( 'a zero snapshot never divides by zero when the format is switched mid-run (#865)', () => {
	// Nothing was missing when the run started, then AVIF was enabled: everything is missing.
	assert.deepStrictEqual( getProgress( 0, 17 ), { processed: 0, total: 17, percent: 0 } );
} );

it( 'a healthy run in progress is unchanged', () => {
	assert.deepStrictEqual( getProgress( 20, 8 ), { processed: 12, total: 20, percent: 60 } );
} );

it( 'a completed run still reports 100%', () => {
	assert.deepStrictEqual( getProgress( 20, 0 ), { processed: 20, total: 20, percent: 100 } );
} );

it( 'an empty run reports 0% rather than NaN', () => {
	assert.deepStrictEqual( getProgress( 0, 0 ), { processed: 0, total: 0, percent: 0 } );
} );

it( 'non-numeric input degrades to zero instead of NaN', () => {
	assert.deepStrictEqual( getProgress( null, undefined ), { processed: 0, total: 0, percent: 0 } );
	assert.deepStrictEqual( getProgress( 'abc', 'def' ), { processed: 0, total: 0, percent: 0 } );
} );

it( 'negative input is clamped to zero', () => {
	assert.deepStrictEqual( getProgress( -5, -3 ), { processed: 0, total: 0, percent: 0 } );
} );

it( 'the percentage stays within 0-100 for every combination up to 50', () => {
	for ( let total = 0; total <= 50; total++ ) {
		for ( let remaining = 0; remaining <= 50; remaining++ ) {
			const { processed, percent, total: effective } = getProgress( total, remaining );

			assert.ok( processed >= 0, `processed negative for ${ total }/${ remaining }` );
			assert.ok( percent >= 0 && percent <= 100, `percent out of range for ${ total }/${ remaining }` );
			assert.ok( Number.isFinite( percent ), `percent not finite for ${ total }/${ remaining }` );
			assert.ok( processed <= effective, `processed exceeds total for ${ total }/${ remaining }` );
		}
	}
} );
