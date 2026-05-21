import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';

/**
 * Imagify Settings page tests.
 *
 * Without a valid API key only the account section (API key input + save
 * button) is visible. The Lossless, WebP/AVIF, and Library sections are
 * hidden and require IMAGIFY_TESTS_API_KEY to be tested.
 */
test.describe( 'Imagify settings', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Settings page renders without fatal errors', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.expectNoFatalError();
		await expect( page ).toHaveURL( /page=imagify/ );
	} );

	test( 'Settings page has a save button', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await expect( settings.saveButton ).toBeVisible();
	} );

	test( 'Lossless compression option is present (requires API key)', async ( { page } ) => {
		test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — settings sections hidden without valid key' );

		const settings = new SettingsPage( page );
		await settings.goto();

		// Imagify uses a "Lossless compression" checkbox, not Normal/Aggressive/Ultra labels.
		const losslessLabel = page.locator( 'label' ).filter( { hasText: /lossless/i } );
		await expect( losslessLabel ).toBeVisible();
	} );

	test( 'WebP/AVIF next-gen format toggles are present (requires API key)', async ( { page } ) => {
		test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — settings sections hidden without valid key' );

		const settings = new SettingsPage( page );
		await settings.goto();

		// The format radio buttons use label[for="imagify_optimization_format_webp"] etc.
		const webpLabel = page.locator( 'label[for="imagify_optimization_format_webp"]' );
		await expect( webpLabel ).toBeVisible();
	} );

	test( 'Saving settings without changing values succeeds', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		await settings.saveButton.click();
		await page.waitForLoadState( 'networkidle' );

		await settings.expectNoFatalError();
		// After save the page should redirect back to the settings page.
		await expect( page ).toHaveURL( /page=imagify/ );
	} );
} );
