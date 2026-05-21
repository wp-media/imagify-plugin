import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';

/**
 * NextGEN Gallery v4.x compatibility spec.
 *
 * Validates that Imagify's NGG integration loads without fatal errors when
 * NextGEN Gallery v4.x is active AND that the Bulk Optimization submenu is
 * visible in the NGG v4 sidebar and navigates to the correct page.
 *
 * Fixes covered:
 *  - PR #1059: removes stale `class_exists('Mixin')` bootstrap guard.
 *  - imagify_get_ngg_parent_menu_slug(): returns 'imagely' on NGG v4 so the
 *    Bulk Optimization submenu registers under the correct top-level menu.
 *
 * Related PR:    #1059  (fix/1020-compatibility-imagify-controls-nextgen)
 * Related issue: #1020
 *
 * Per-image optimization note:
 *   The per-image Imagify column is NOT available in the new NGG v4 React
 *   gallery UI. NGG v4 replaced the PHP-rendered table (which exposed
 *   `ngg_manage_images_number_of_columns` filters) with a React SPA whose
 *   image context menu is fully hardcoded with no PHP extension hooks.
 *   See PR #1059 comment for the full technical explanation.
 */

/**
 * Install and activate NextGEN Gallery before the suite runs.
 * This is idempotent: wp-cli exits 0 whether NGG was already installed or not.
 */
test.beforeAll( () => {
	wpCli( 'plugin install nextgen-gallery --activate --force' );
} );

test.beforeEach( async ( { page } ) => {
	await loginAsAdmin( page );
} );

// ---------------------------------------------------------------------------
// Smoke — no fatal errors when both plugins are active
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Core acceptance criterion — Bulk Optimization submenu in NGG v4 sidebar
// ---------------------------------------------------------------------------

test( 'Bulk Optimization submenu is visible in the NGG v4 Imagely sidebar menu', async ( { page } ) => {
	// NGG v4 registers its top-level menu under the slug 'imagely'. Navigate
	// there so the sidebar expands and exposes its submenus.
	await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

	const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
	await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );
	await expect( submenuLink ).toContainText( 'Bulk Optimization' );

	await page.screenshot( { path: 'screenshots/ngg-bulk-submenu-visible.png', fullPage: false } );
} );

test( 'Clicking Bulk Optimization submenu navigates to the Imagify NGG bulk page', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

	const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
	await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );

	// Validate the href contains the correct slug, then navigate directly to avoid
	// being intercepted by the NGG v4 React SPA client-side router.
	const href = await submenuLink.getAttribute( 'href' );
	expect( href ).toMatch( /page=imagify-ngg-bulk-optimization/ );

	await page.goto( '/wp-admin/admin.php?page=imagify-ngg-bulk-optimization', { waitUntil: 'domcontentloaded' } );

	await expect( page ).toHaveURL( /page=imagify-ngg-bulk-optimization/ );
	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

	const bodyText = await page.locator( 'body' ).innerText();
	expect( bodyText ).not.toMatch( /Fatal error/i );
	// The Imagify bulk page renders a .imagify-bulk wrapper (views/page-bulk.php).
	await expect( page.locator( '.imagify-bulk' ).first() ).toBeVisible( { timeout: 10_000 } );

	await page.screenshot( { path: 'screenshots/ngg-bulk-page.png', fullPage: true } );
} );

// ---------------------------------------------------------------------------
// Regression guard — NGG v4 gallery admin page
// ---------------------------------------------------------------------------

test( 'NGG v4 gallery admin page loads without PHP fatal error', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=imagely', { waitUntil: 'domcontentloaded' } );

	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

	const bodyText = await page.locator( 'body' ).innerText();
	expect( bodyText ).not.toMatch( /Fatal error/i );
	expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );
} );

// ---------------------------------------------------------------------------
// Plugin deactivation / reactivation smoke test
// ---------------------------------------------------------------------------

test( 'Imagify can be deactivated and reactivated cleanly with NGG v4.x active', async ( { page } ) => {
	await page.goto( '/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' } );

	// In wp-env the plugin folder is mapped as 'imagify' (see .wp-env.json mappings),
	// so the plugin row input value is 'imagify/imagify.php', not 'imagify-plugin/...'.
	const imagifyRow = page.locator( 'tr' ).filter( { has: page.locator( 'td strong:has-text("Imagify")' ) } );

	const deactivateLink = imagifyRow.locator( 'a', { hasText: /Deactivate/i } );
	await expect( deactivateLink ).toBeVisible( { timeout: 10_000 } );
	await deactivateLink.click();
	await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

	const imagifyRowAfter = page.locator( 'tr' ).filter( { has: page.locator( 'td strong:has-text("Imagify")' ) } );
	const activateLink = imagifyRowAfter.locator( 'a', { hasText: /^Activate$/i } );
	await expect( activateLink ).toBeVisible( { timeout: 10_000 } );
	await activateLink.click();
	await page.waitForURL( /plugins\.php/, { timeout: 10_000 } );

	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

	const imagifyRowFinal = page.locator( 'tr' ).filter( { has: page.locator( 'td strong:has-text("Imagify")' ) } );
	await expect( imagifyRowFinal.locator( 'a', { hasText: /Deactivate/i } ) ).toBeVisible();
} );
