import { defineConfig, devices } from '@playwright/test';

/**
 * Imagify end-to-end test config.
 *
 * The suite runs against the wp-env stack booted via `bin/dev-up.sh` at the
 * repository root. CI bootstraps the same stack before invoking
 * `npx playwright test` in this directory.
 */
export default defineConfig( {
	testDir: './specs',
	fullyParallel: false, // Tests share a single WP install; isolate via reseed.
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	workers: 1, // Single-worker keeps the wp-env DB state predictable.
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never' } ] ] : 'list',

	use: {
		baseURL: process.env.IMAGIFY_BASE_URL ?? 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		viewport: { width: 1440, height: 900 },
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],

	expect: {
		timeout: 10_000,
	},
	timeout: 60_000,
} );
