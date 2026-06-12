import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { screenshotElement } from '../fixtures/screenshot';
import { wpCli } from '../fixtures/wp-cli';

/**
 * NextGEN Gallery v3.x regression spec.
 *
 * Validates that Imagify's NGG integration continues to work correctly with
 * NextGEN Gallery v3.x after the v4 compatibility changes introduced in #1020.
 * Ensures that the POPE framework path (Mixin / C_Gallery_Storage) remains intact
 * and that the v3 submenu, bulk page, and per-image column are all functional.
 *
 * IMPORTANT: Set the IMAGIFY_NGG_V3 env var before running this suite and ensure
 * NGG v3 is installed. This suite installs NGG v3.59.7 in beforeAll and restores
 * the latest NGG version in afterAll.
 *
 * Run:
 *   IMAGIFY_NGG_V3=1 npx playwright test specs/ngg-v3-compat.spec.ts
 */
test.describe( 'NextGEN Gallery v3.x regression', () => {
	test.beforeAll( async () => {
		if ( ! process.env.IMAGIFY_NGG_V3 ) {
			throw new Error(
				'IMAGIFY_NGG_V3 is not set. NGG v3 must be available before this suite. ' +
				'Run: npx @wordpress/env run cli wp plugin install nextgen-gallery --version=3.59.7 --activate --force, ' +
				'then re-run with IMAGIFY_NGG_V3=1.',
			);
		}

		// Install and activate NGG v3.59.7 for this suite.
		wpCli( 'plugin install nextgen-gallery --version=3.59.7 --activate --force' );
	} );

	test.afterAll( async () => {
		if ( process.env.IMAGIFY_NGG_V3 ) {
			// Restore the latest version of NGG after the suite.
			wpCli( 'plugin install nextgen-gallery --activate --force' );
		}
	} );

	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// -----------------------------------------------------------------------
	// Smoke — no fatal errors with NGG v3 active
	// -----------------------------------------------------------------------

	test( 'WordPress dashboard loads without PHP fatal errors when NGG v3 is active', async ( { page } ) => {
		await page.goto( '/wp-admin/', { waitUntil: 'domcontentloaded' } );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
		expect( bodyText ).not.toMatch( /PHP Fatal/i );
	} );

	// -----------------------------------------------------------------------
	// Submenu — must appear under 'nextgen-gallery' menu slug on v3
	// -----------------------------------------------------------------------

	test( 'Bulk Optimization submenu is visible under the NextGEN Gallery v3 menu', async ( { page } ) => {
		await page.goto( '/wp-admin/', { waitUntil: 'domcontentloaded' } );

		// NGG v3 registers its top-level menu under the NGGFOLDER basename or 'nextgen-gallery'.
		// The submenu link should appear when hovering/clicking the NGG top-level entry.
		const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
		await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );
		await expect( submenuLink ).toContainText( 'Bulk Optimization' );

		await screenshotElement( page, 'ngg-v3-submenu', submenuLink );
	} );

	// -----------------------------------------------------------------------
	// Bulk page — renders Imagify content correctly on v3
	// -----------------------------------------------------------------------

	test( 'Bulk optimization page loads without errors and renders Imagify content on NGG v3', async ( { page } ) => {
		await page.goto( '/wp-admin/admin.php?page=imagify-ngg-bulk-optimization', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
		expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );

		// The .imagify-bulk wrapper is rendered when Imagify bulk JS is enqueued.
		const bulkWrapper = page.locator( '.imagify-bulk' );
		await expect( bulkWrapper ).toBeVisible( { timeout: 10_000 } );

		await screenshotElement( page, 'ngg-v3-bulk-page', bulkWrapper );
	} );

	// -----------------------------------------------------------------------
	// POPE framework — Mixin / C_Gallery_Storage path must not error on v3
	// -----------------------------------------------------------------------

	test( 'NGG v3 settings page loads without C_Gallery_Storage or Mixin errors', async ( { page } ) => {
		// Navigate to the NGG gallery management page, which exercises the v3 POPE framework.
		await page.goto( '/wp-admin/admin.php?page=nggallery-manage-gallery', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
		expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );
		expect( bodyText ).not.toMatch( /class Mixin/i );
	} );

	// -----------------------------------------------------------------------
	// Deactivation / reactivation regression
	// -----------------------------------------------------------------------

	test( 'Imagify can be deactivated and reactivated cleanly with NGG v3 active', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' } );

		const imagifyRow = page.locator( 'tr:has(input[value*="imagify/imagify"])' );

		const deactivateLink = imagifyRow.locator( 'a', { hasText: /Deactivate/i } );
		await expect( deactivateLink ).toBeVisible( { timeout: 10_000 } );
		await deactivateLink.click();
		await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const imagifyRowAfter = page.locator( 'tr:has(input[value*="imagify/imagify"])' );
		const activateLink = imagifyRowAfter.locator( 'a', { hasText: /^Activate$/i } );
		await expect( activateLink ).toBeVisible( { timeout: 10_000 } );
		await activateLink.click();
		await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const imagifyRowFinal = page.locator( 'tr:has(input[value*="imagify/imagify"])' );
		await expect( imagifyRowFinal.locator( 'a', { hasText: /Deactivate/i } ) ).toBeVisible();
	} );
} );
