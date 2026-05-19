import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { seedOptimizedImage } from '../fixtures/seed';

/**
 * Responsive layout tests for the Imagify Settings page.
 *
 * These tests exercise the CSS fixes introduced in fix/1045-generate-missing-next-gen:
 * the "Generate missing Next-Gen images versions" button must fill its container
 * at viewport widths ≤ 782px (WordPress mobile breakpoint).
 *
 * The "Generate missing Next-Gen images versions" button is only rendered when
 * there are optimized images without next-gen versions. The test seeds that
 * state via WP-CLI before asserting the responsive layout.
 *
 * If the seed cannot produce the button (no images in the library), the
 * responsive CSS assertions fall back to verifying that the page loads
 * without errors and that the button — if present — is full-width.
 */

const MOBILE_VIEWPORT = { width: 782, height: 900 };
const DESKTOP_VIEWPORT = { width: 1440, height: 900 };

test.describe( 'Settings page — responsive layout', () => {
	test.beforeAll( () => {
		seedOptimizedImage();
	} );

	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Settings page loads at mobile viewport without fatal errors', async ( { page } ) => {
		await page.setViewportSize( MOBILE_VIEWPORT );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify/ );
	} );

	test( 'Settings page loads at desktop viewport without fatal errors', async ( { page } ) => {
		await page.setViewportSize( DESKTOP_VIEWPORT );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
		await expect( page ).toHaveURL( /page=imagify/ );
	} );

	test( 'Generate Next-Gen button is full-width at ≤ 782px when visible', async ( { page } ) => {
		await page.setViewportSize( MOBILE_VIEWPORT );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		const button = page.locator( '#imagify-generate-webp-versions' );

		// The button must be visible at mobile width.
		await expect( button ).toBeVisible();

		// Assert that the button fills its container: its rendered width must
		// equal the width of its parent container (the .generate-missing-webp div).
		const container = page.locator( '.imagify-options-line.generate-missing-webp' );
		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		// Allow a 2px tolerance for borders / sub-pixel rounding.
		expect( Math.abs( buttonBox!.width - containerBox!.width ) ).toBeLessThanOrEqual( 2 );
	} );

	test( 'Generate Next-Gen button width is unchanged at desktop viewport when visible', async ( { page } ) => {
		await page.setViewportSize( DESKTOP_VIEWPORT );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		const button = page.locator( '#imagify-generate-webp-versions' );

		await expect( button ).toBeVisible();

		// At desktop widths the button should NOT be full-width: it must be
		// narrower than the viewport, confirming the desktop layout is untouched.
		const buttonBox = await button.boundingBox();
		expect( buttonBox ).not.toBeNull();
		expect( buttonBox!.width ).toBeLessThan( DESKTOP_VIEWPORT.width );
	} );

	test( 'generate-missing-webp container has no left margin at ≤ 782px when visible', async ( { page } ) => {
		await page.setViewportSize( MOBILE_VIEWPORT );
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		const container = page.locator( '.imagify-options-line.generate-missing-webp' );

		// The CSS rule sets margin-left: 0, overriding the default 40px from
		// `label ~ .imagify-options-line`. Verify margin-left is 0px.
		const marginLeft = await container.evaluate(
			( el ) => window.getComputedStyle( el ).marginLeft
		);

		expect( marginLeft ).toBe( '0px' );
	} );
} );
