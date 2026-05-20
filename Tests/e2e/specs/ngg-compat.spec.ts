import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';

/**
 * NextGEN Gallery v4.x compatibility spec.
 *
 * Validates that Imagify's NGG integration loads without fatal errors when
 * NextGEN Gallery v4.x is active. The primary fix in PR #1059 removes the
 * stale `class_exists('Mixin')` guard from the bootstrap file and adds a
 * defensive check inside `add_mixin()` to silently no-op on NGG v4.x.
 *
 * Related PR:   #1059  (fix/1020-compatibility-imagify-controls-nextgen)
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
		test.skip(
			! process.env.IMAGIFY_NGG_INSTALLED,
			'IMAGIFY_NGG_INSTALLED is not set — skipping NGG compatibility tests. ' +
			'Run: npx @wordpress/env run cli wp plugin install nextgen-gallery --activate ' +
			'then re-run with IMAGIFY_NGG_INSTALLED=1',
		);
		await loginAsAdmin( page );
	} );

	// -----------------------------------------------------------------------
	// Test 3 — No fatal errors with NGG v4.x active (primary acceptance criterion)
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
	// Test 1 — NGG Bulk Optimization page is accessible via direct URL
	// -----------------------------------------------------------------------

	test( 'NGG Imagify bulk optimization page is accessible via direct URL', async ( { page } ) => {
		// The page slug `imagify-ngg-bulk-optimization` is registered via
		// add_submenu_page('nextgen-gallery', ...). In NGG v4.x it may not appear
		// in the sidebar (NGG changed its menu slug to `imagely`), but the page
		// itself is accessible and must not throw a fatal error.
		await page.goto( '/wp-admin/admin.php?page=imagify-ngg-bulk-optimization', { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		const bodyText = await page.locator( 'body' ).innerText();
		expect( bodyText ).not.toMatch( /Fatal error/i );
	} );

	// -----------------------------------------------------------------------
	// NGG v4.x — Mixin class check (the core fix)
	// -----------------------------------------------------------------------

	test( 'NGG gallery admin page loads without PHP fatal error', async ( { page } ) => {
		// In NGG v4.x the main gallery admin is page=imagely; page=nextgen-gallery
		// is also available for backward compatibility.
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
