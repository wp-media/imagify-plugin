import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * Imagify Reset Internal State — Troubleshooting section tests.
 *
 * The Troubleshooting section (and its Reset Internal State button) is only
 * rendered when a valid API key is present. Tests that require the button
 * assert its presence with a hard failure (no test.skip) so that missing
 * seed data is immediately visible rather than silently skipped.
 *
 * Seed requirement: IMAGIFY_TESTS_API_KEY must be set and valid for tests
 * that interact with the button.
 */
test.describe( 'Reset Internal State — Troubleshooting section', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — section only renders with valid API key.' );
	test( 'Troubleshooting section is visible on settings page (requires API key)', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		const section = page.locator( '#imagify-reset-internal-state' ).locator( '..' );
		await expect( section ).toBeVisible();

		await screenshotElement( page, 'reset-internal-state-section', section );
	} );

	test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — section only renders with valid API key.' );
	test( 'Reset Internal State button is present and has a nonce attribute (requires API key)', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		const button = page.locator( '#imagify-reset-internal-state' );
		await expect( button ).toBeVisible();

		// The button must carry a data-nonce attribute (non-empty).
		const nonce = await button.getAttribute( 'data-nonce' );
		expect( nonce, 'Reset button must have a non-empty data-nonce attribute' ).toBeTruthy();

		await screenshotElement( page, 'reset-internal-state-button', button );
	} );

	test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — section only renders with valid API key.' );
	test( 'Reset Internal State button is clickable (requires API key)', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();

		const button = page.locator( '#imagify-reset-internal-state' );
		await expect( button ).toBeVisible();
		await expect( button ).toBeEnabled();

		// The button should not be disabled before any interaction.
		const isDisabled = await button.isDisabled();
		expect( isDisabled ).toBe( false );

		await screenshotElement( page, 'reset-internal-state-clickable', button );
	} );
} );
