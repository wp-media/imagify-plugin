import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { MediaLibraryPage } from '../pages/media-library';

/**
 * Media library integration tests.
 *
 * Verifies that Imagify injects its optimization column and that the
 * per-image optimization UI renders correctly.
 *
 * Optimization tests that call the API are skipped when IMAGIFY_TESTS_API_KEY is unset.
 */
test.describe( 'Media library — Imagify column', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Media library (list mode) loads without errors', async ( { page } ) => {
		const library = new MediaLibraryPage( page );
		await library.goto();

		await library.expectNoFatalError();
	} );

	test( 'Imagify column is present in media library table', async ( { page } ) => {
		const library = new MediaLibraryPage( page );
		await library.goto();

		await expect( library.imagifyColumn ).toBeVisible();
	} );

	test( 'Media attachment detail page loads without errors', async ( { page } ) => {
		// Navigate to media library and open the first attachment.
		await page.goto( '/wp-admin/upload.php?mode=list' );
		await page.waitForLoadState( 'networkidle' );

		const firstLink = page.locator( 'td.title a.row-title' ).first();
		const count = await firstLink.count();
		if ( count === 0 ) {
			test.skip( true, 'No media attachments in library — run bin/dev-seed.sh first' );
			return;
		}

		await firstLink.click();
		await page.waitForLoadState( 'networkidle' );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	} );
} );
