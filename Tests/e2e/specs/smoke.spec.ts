import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';

/**
 * Smoke tests — verify core Imagify admin surfaces load without fatal errors.
 * These tests do NOT require an API key and run in all environments.
 */
test.describe( 'Imagify smoke', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Settings page loads', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify/ );
	} );

	test( 'Bulk optimization page loads', async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?page=imagify-bulk-optimization' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify-bulk-optimization/ );
	} );

	test( 'Media library loads with Imagify column', async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?mode=list' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		// Imagify injects its optimization column into the media list table.
		const imagifyCol = page.locator( 'th[id*="imagify"], th.column-imagify' ).first();
		await expect( imagifyCol ).toBeVisible();
	} );

	test( 'Plugin can be deactivated and reactivated without errors', async ( { page } ) => {
		// Deactivate.
		await page.goto( '/wp-admin/plugins.php' );
		const deactivateLink = page.locator( 'tr[data-slug="imagify"] a[href*="action=deactivate"]' );
		await expect( deactivateLink ).toBeVisible();
		await deactivateLink.click();
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );

		// Reactivate.
		const activateLink = page.locator( 'tr[data-slug="imagify"] a[href*="action=activate"]' );
		await expect( activateLink ).toBeVisible();
		await activateLink.click();
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	} );
} );
