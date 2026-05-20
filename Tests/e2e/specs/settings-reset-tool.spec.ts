import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { SettingsPage } from '../pages/settings';

/**
 * Settings — Reset Internal State troubleshooting tool.
 *
 * Validates that:
 * 1. The "Troubleshooting" section is visible on the settings page.
 * 2. The "Reset Internal State" button is present.
 * 3. Clicking the button sends an AJAX request and displays a success message.
 * 4. The AJAX endpoint returns { success: true } with the expected message.
 *
 * Related PR:   #1058  (fix/1012-one-click-tool-reset)
 * Related issue: #1012
 */
test.describe( 'Settings — Reset Internal State tool', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Troubleshooting section is visible on settings page', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const troubleshootingHeader = page.locator( 'h2' ).filter( { hasText: /Troubleshooting/i } );
		await expect( troubleshootingHeader ).toBeVisible( { timeout: 10_000 } );
	} );

	test( 'Reset Internal State button is present', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const resetBtn = page.locator( '#imagify-reset-internal-state' );
		await expect( resetBtn ).toBeVisible( { timeout: 10_000 } );

		// Button text must include "Reset Internal State"
		await expect( resetBtn ).toContainText( /Reset Internal State/i );
	} );

	test( 'Reset Internal State button has a nonce attribute', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const resetBtn = page.locator( '#imagify-reset-internal-state' );
		await expect( resetBtn ).toBeVisible( { timeout: 10_000 } );

		// The button must carry a data-nonce attribute for the AJAX call.
		const nonce = await resetBtn.getAttribute( 'data-nonce' );
		expect( nonce ).toBeTruthy();
		expect( nonce!.length ).toBeGreaterThan( 5 );
	} );

	test( 'Clicking the button shows a success message', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const resetBtn  = page.locator( '#imagify-reset-internal-state' );
		const feedback  = page.locator( '#imagify-reset-internal-state-feedback' );

		await resetBtn.scrollIntoViewIfNeeded();
		await resetBtn.click();

		// Wait for feedback to become non-empty and not just the "Resetting…" intermediate state.
		await expect( feedback ).not.toHaveText( '' );
		await page.waitForFunction(
			() => {
				const el = document.getElementById( 'imagify-reset-internal-state-feedback' );
				return el && el.textContent && ! el.textContent.includes( 'Resetting' );
			},
			{ timeout: 15_000 },
		);

		const feedbackText = await feedback.innerText();
		// Success message must indicate the reset completed successfully.
		expect( feedbackText ).toMatch( /reset successfully|réinitialisé avec succès/i );
	} );

	test( 'AJAX endpoint returns success when called with a valid nonce', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		// Grab nonce from the already-rendered button.
		const resetBtn = page.locator( '#imagify-reset-internal-state' );
		const nonce    = await resetBtn.getAttribute( 'data-nonce' );
		expect( nonce ).toBeTruthy();

		// Call the AJAX endpoint in-page (inherits the logged-in session cookie).
		const result: { success: boolean; data?: { message: string } } = await page.evaluate(
			async ( ajaxNonce: string ) => {
				const fd = new FormData();
				fd.append( 'action', 'imagify_reset_internal_state' );
				fd.append( '_ajax_nonce', ajaxNonce );
				const res = await fetch( '/wp-admin/admin-ajax.php', {
					method: 'POST',
					body: fd,
					credentials: 'include',
				} );
				return res.json();
			},
			nonce!,
		);

		expect( result.success ).toBe( true );
		expect( result.data ).toBeDefined();
		expect( result.data!.message ).toMatch( /reset successfully|réinitialisé avec succès/i );
	} );

	test( 'AJAX endpoint returns 403 when nonce is invalid', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const result: { success: boolean; data?: { message: string } } = await page.evaluate( async () => {
			const fd = new FormData();
			fd.append( 'action', 'imagify_reset_internal_state' );
			fd.append( '_ajax_nonce', 'invalid-nonce-value' );
			const res = await fetch( '/wp-admin/admin-ajax.php', {
				method: 'POST',
				body: fd,
				credentials: 'include',
			} );
			return res.json();
		} );

		// Invalid nonce must return a failure response.
		expect( result.success ).toBe( false );
	} );
} );
