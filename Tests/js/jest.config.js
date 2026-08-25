/**
 * Jest configuration for the JavaScript unit suite.
 *
 * Extends wp-scripts' preset - that is what supplies the Babel transform, without
 * which the ES module sources under _dev/src cannot be imported.
 *
 * testMatch is narrowed deliberately: the preset would also collect the 12
 * Playwright specs in Tests/e2e/specs/*.spec.ts, which import @playwright/test
 * and cannot run under Jest.
 */
const path = require( 'path' );

module.exports = {
	preset:    '@wordpress/jest-preset-default',
	/*
	 * The preset ships no transform, and wp-scripts expects a Babel config in the
	 * project. Declaring it here rather than in a root babel.config.js keeps the
	 * bud/webpack build - which has its own Babel setup - completely unaffected.
	 */
	transform: {
		'\\.[jt]sx?$': [ 'babel-jest', { presets: [ '@wordpress/babel-preset-default' ] } ]
	},
	rootDir:                path.join( __dirname, '..', '..' ),
	testEnvironment:        'node',
	testMatch:              [ '<rootDir>/Tests/js/**/*.test.js' ],
	testPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'<rootDir>/Tests/e2e/',
		'<rootDir>/inc/Dependencies/'
	]
};
