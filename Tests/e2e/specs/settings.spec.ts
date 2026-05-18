import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';

/**
 * Imagify Settings page tests.
 *
 * Focuses on the settings UI rendering and saving behavior.
 * Does not depend on a live API key.
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

	test( 'Optimization level options are present', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		// Imagify exposes Normal, Aggressive, and Ultra levels.
		const optionLabels = page.locator( 'label' ).filter( { hasText: /normal|aggressive|ultra/i } );
		await expect( optionLabels ).not.toHaveCount( 0 );
	} );

	test( 'WebP/AVIF next-gen format toggles are present', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		const webpOption = page.locator( 'input[name*="webp"], label' ).filter( { hasText: /webp/i } ).first();
		await expect( webpOption ).toBeVisible();
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
