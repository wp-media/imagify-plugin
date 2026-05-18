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
		test.skip( !! process.env.CI && ! process.env.IMAGIFY_TESTS_API_KEY, 'Requires API key to validate response' );

		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.apiKeyInput.fill( 'invalid-key-000' );
		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		// Imagify should render an error notice or inline validation message.
		const error = page.locator( '.notice-error, .imagify-notice-error, .imagify-error' ).first();
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
