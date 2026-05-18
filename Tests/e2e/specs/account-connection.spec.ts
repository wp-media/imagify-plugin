import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';

/**
 * Account connection tests.
 *
 * Tests that require a real API key are skipped when IMAGIFY_TESTS_API_KEY is unset.
 */
test.describe( 'Imagify account connection', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Settings page contains the API key field', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await expect( settings.apiKeyInput ).toBeVisible();
	} );

	test( 'Entering an invalid API key shows an error', async ( { page } ) => {
		// Imagify validates the key by calling its API, so an actual network call is
		// required for the error notice to appear. Skip if no key is configured — we
		// cannot reliably produce a "bad key" response without a real account.
		test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — cannot produce a bad-key API response' );

		const settings = new SettingsPage( page );
		await settings.goto();

		// Submit a deliberately wrong key so the API rejects it.
		await settings.apiKeyInput.fill( 'invalid-key-000000000000000000000000000000' );
		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		// Imagify renders the "wrong API key" notice with a standard WP .notice.error class.
		const error = page.locator( '.notice.error, .notice-error' ).first();
		await expect( error ).toBeVisible( { timeout: 10_000 } );
	} );

	test( 'Valid API key connects the account', async ( { page } ) => {
		test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — skipping live connection test' );

		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.apiKeyInput.fill( process.env.IMAGIFY_TESTS_API_KEY! );
		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		// After saving a valid key, Imagify shows account info or a success notice.
		const success = page.locator( '.notice-success, .updated, .imagify-connected' ).first();
		await expect( success ).toBeVisible( { timeout: 15_000 } );
	} );
} );
