import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * NextGEN Gallery v4.x compatibility spec.
 *
 * Validates that Imagify's NGG integration loads without fatal errors when
 * NextGEN Gallery v4.x is active AND that the Bulk Optimization submenu is
 * visible in the NGG sidebar.
 *
 * The primary fix in PR #1059 removes the stale `class_exists('Mixin')` guard
 * from the bootstrap file and adds a defensive check inside `add_mixin()` to
 * silently no-op on NGG v4.x.
 *
 * A follow-up fix adds imagify_get_ngg_parent_menu_slug(), which returns
 * 'imagely' on NGG v4.x so the Bulk Optimization submenu is registered under
 * the correct top-level menu (NGG v4 changed the slug from 'nextgen-gallery'
 * to 'imagely').
 *
 * Related PR:    #1059  (fix/1020-compatibility-imagify-controls-nextgen)
 * Related issue: #1020
 *
 * IMPORTANT: Tests in this file are skipped when the env var
 * `IMAGIFY_NGG_INSTALLED` is not set. In standard CI, NGG is not installed by
 * default; set that env var and activate the plugin before running this suite.
 *
 * When `IMAGIFY_NGG_INSTALLED` IS set (local testing with NGG active):
 *   npx @wordpress/env run cli wp plugin install nextgen-gallery --activate
 *   IMAGIFY_NGG_INSTALLED=1 npx playwright test specs/ngg-compat.spec.ts
 */
test.describe( 'NextGEN Gallery v4.x compatibility', () => {
	test.beforeEach( async ( { page } ) => {
		if ( ! process.env.IMAGIFY_NGG_INSTALLED ) {
			// Skip gracefully in CI where NGG is not installed.
			// To run locally: install NGG, then re-run with IMAGIFY_NGG_INSTALLED=1.
			test.skip();
			return;
		}
		await loginAsAdmin( page );
	} );

	// -----------------------------------------------------------------------
	// Smoke — no fatal errors when both plugins are active
	// -----------------------------------------------------------------------

	test( 'Imagify settings page loads without fatal errors when NGG is active', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify/ );
	} );

	test( 'Imagify bulk optimization page loads without fatal errors when NGG is active', async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?page=imagify-bulk-optimization', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify-bulk-optimization/ );
	} );

	test( 'WordPress admin dashboard loads without PHP fatal errors when NGG v4 is active', async ( { page } ) => {
		await page.goto( '/wp-admin/', { waitUntil: 'domcontentloaded' } );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
		expect( bodyText ).not.toMatch( /PHP Fatal/i );
		expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );
		expect( bodyText ).not.toMatch( /class Mixin/i );
	} );

	// -----------------------------------------------------------------------
	// Core acceptance criterion — Bulk Optimization submenu in NGG v4 sidebar
	// -----------------------------------------------------------------------

	test( 'Bulk Optimization submenu is visible in the NGG v4 Imagely sidebar menu', async ( { page } ) => {
		// NGG v4 registers its top-level menu under the slug 'imagely'.
		// Imagify must add its submenu under that same slug so it appears in
		// the visible sidebar (not under the legacy 'nextgen-gallery' slug).
		await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

		// WordPress renders submenus in #adminmenu when the parent is active.
		const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
		await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );
		await expect( submenuLink ).toContainText( 'Bulk Optimization' );
	} );

	test( 'Clicking the Bulk Optimization submenu navigates to the bulk page without errors', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

		const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
		await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );
		await submenuLink.click();

		await expect( page ).toHaveURL( /page=imagify-ngg-bulk-optimization/, { timeout: 10_000 } );
		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
	} );

	test( 'Imagify JS and CSS are enqueued on the NGG bulk optimization page (Finding C)', async ( { page } ) => {
		// Navigate directly to the bulk page to verify enqueue fires with the correct screen ID.
		await page.goto( '/wp-admin/admin.php?page=imagify-ngg-bulk-optimization', { waitUntil: 'domcontentloaded' } );

		// The .imagify-bulk wrapper is rendered only when Imagify bulk JS is enqueued and initialised.
		const bulkWrapper = page.locator( '.imagify-bulk' );
		await expect( bulkWrapper ).toBeVisible( { timeout: 10_000 } );

		await screenshotElement( page, 'ngg-v4-bulk-page-imagify-assets', bulkWrapper );
	} );

	// -----------------------------------------------------------------------
	// Regression guard — NGG v4 gallery admin page
	// -----------------------------------------------------------------------

	test( 'NGG v4 gallery admin page loads without PHP fatal error', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
		expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );
	} );

	// -----------------------------------------------------------------------
	// Plugin deactivation / reactivation smoke test
	// -----------------------------------------------------------------------

	test( 'Imagify can be deactivated and reactivated cleanly with NGG v4.x active', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' } );

		// Locate the Imagify row via the checkbox value (most stable selector in WP plugins table).
		const imagifyRow = page.locator( 'tr:has(input[value*="imagify-plugin"])' );

		const deactivateLink = imagifyRow.locator( 'a', { hasText: /Deactivate/i } );
		await expect( deactivateLink ).toBeVisible( { timeout: 10_000 } );
		await deactivateLink.click();
		await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		// Re-activate Imagify.
		const imagifyRowAfter = page.locator( 'tr:has(input[value*="imagify-plugin"])' );
		const activateLink = imagifyRowAfter.locator( 'a', { hasText: /^Activate$/i } );
		await expect( activateLink ).toBeVisible( { timeout: 10_000 } );
		await activateLink.click();
		await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		// Confirm Imagify is active again.
		const imagifyRowFinal = page.locator( 'tr:has(input[value*="imagify-plugin"])' );
		await expect( imagifyRowFinal.locator( 'a', { hasText: /Deactivate/i } ) ).toBeVisible();
	} );
} );
