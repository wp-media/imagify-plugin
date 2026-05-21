import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { runFromRepoRoot } from '../fixtures/wp-cli';

/**
 * NextGEN Gallery v3.x backward-compatibility spec.
 *
 * Validates that the NGG v4 fixes in PR #1059 do not regress against NGG v3.x.
 * Specifically:
 *  - No PHP fatal errors with both plugins active
 *  - Bulk Optimization submenu is visible under the NGG v3 menu (slug: NGGFOLDER
 *    = 'nextgen-gallery'), NOT under 'imagely' which is v4-only
 *  - Navigating to the bulk page works correctly
 *
 * Related PR:    #1059  (fix/1020-compatibility-imagify-controls-nextgen)
 * Related issue: #1020
 */

// wpCli() doesn't support --version because @wordpress/env swallows flags before '--'.
// runFromRepoRoot() lets us pass the '--' separator to protect the version arg.
const ENV_CLI = 'npx --yes @wordpress/env run cli --';

test.beforeAll( () => {
	runFromRepoRoot( `${ ENV_CLI } wp plugin install nextgen-gallery "--version=3.59.7" --force --activate` );
} );

test.afterAll( () => {
	// Restore NGG v4 so other suites are not affected.
	runFromRepoRoot( `${ ENV_CLI } wp plugin install nextgen-gallery --force --activate` );
} );

test.beforeEach( async ( { page } ) => {
	await loginAsAdmin( page );
} );

// ---------------------------------------------------------------------------
// Smoke — no fatal errors
// ---------------------------------------------------------------------------

test( 'Dashboard loads without PHP fatal errors when NGG v3 is active', async ( { page } ) => {
	await page.goto( '/wp-admin/', { waitUntil: 'domcontentloaded' } );

	const bodyText = await page.locator( 'body' ).innerText();
	expect( bodyText ).not.toMatch( /Fatal error/i );
	expect( bodyText ).not.toMatch( /C_Gallery_Storage/i );
	expect( bodyText ).not.toMatch( /class Mixin/i );
} );

test( 'Imagify settings page loads without fatal errors when NGG v3 is active', async ( { page } ) => {
	await page.goto( '/wp-admin/options-general.php?page=imagify', { waitUntil: 'domcontentloaded' } );

	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	await expect( page ).toHaveURL( /page=imagify/ );
} );

// ---------------------------------------------------------------------------
// Core acceptance — Bulk Optimization submenu under the NGG v3 menu
// ---------------------------------------------------------------------------

test( 'Bulk Optimization submenu is visible under the NGG v3 sidebar menu', async ( { page } ) => {
	// NGG v3 registers its top-level menu under NGGFOLDER ('nextgen-gallery').
	await page.goto( '/wp-admin/admin.php?page=nextgen-gallery', { waitUntil: 'domcontentloaded' } );

	const submenuLink = page.locator( '#adminmenu a[href*="page=imagify-ngg-bulk-optimization"]' );
	await expect( submenuLink ).toBeVisible( { timeout: 10_000 } );
	await expect( submenuLink ).toContainText( 'Bulk Optimization' );

	await page.screenshot( { path: 'screenshots/ngg-v3-bulk-submenu-visible.png', fullPage: false } );
} );

test( 'Bulk Optimization page loads correctly with NGG v3 active', async ( { page } ) => {
	await page.goto( '/wp-admin/admin.php?page=imagify-ngg-bulk-optimization', { waitUntil: 'domcontentloaded' } );

	await expect( page ).toHaveURL( /page=imagify-ngg-bulk-optimization/ );
	await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

	const bodyText = await page.locator( 'body' ).innerText();
	expect( bodyText ).not.toMatch( /Fatal error/i );
	await expect( page.locator( '.imagify-bulk' ).first() ).toBeVisible( { timeout: 10_000 } );

	await page.screenshot( { path: 'screenshots/ngg-v3-bulk-page.png', fullPage: true } );
} );
