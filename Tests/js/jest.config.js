/**
 * Jest configuration for the JavaScript unit suite.
 *
 * Scoped deliberately. A default wp-scripts testMatch would also collect the 12
 * Playwright specs in Tests/e2e/specs/*.spec.ts, which import @playwright/test and
 * cannot run under Jest, so the suite is restricted to this directory.
 */
const path = require( 'path' );

module.exports = {
	rootDir: path.join( __dirname, '..', '..' ),
	testEnvironment: 'node',
	testMatch: [ '<rootDir>/Tests/js/**/*.test.js' ],
	testPathIgnorePatterns: [
		'/node_modules/',
		'/vendor/',
		'<rootDir>/Tests/e2e/',
		'<rootDir>/inc/Dependencies/'
	]
};
