import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { BulkOptimizationPage } from '../pages/bulk-optimization';

/**
 * Bulk optimization page tests.
 *
 * The actual optimization trigger requires a valid API key. Tests that call
 * the Imagify API are skipped when IMAGIFY_TESTS_API_KEY is unset.
 */
test.describe( 'Bulk optimization', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Bulk optimization page loads without errors', async ( { page } ) => {
		const bulk = new BulkOptimizationPage( page );
		await bulk.goto();

		await bulk.expectNoFatalError();
		await expect( page ).toHaveURL( /page=imagify-bulk-optimization/ );
	} );

	test( 'Bulk optimization page has an optimize button', async ( { page } ) => {
		const bulk = new BulkOptimizationPage( page );
		await bulk.goto();

		await expect( bulk.optimizeButton ).toBeVisible();
	} );

	test( 'Bulk optimization page shows stats section', async ( { page } ) => {
		const bulk = new BulkOptimizationPage( page );
		await bulk.goto();

		await expect( bulk.statsTable ).toBeVisible();
	} );

	test( 'Starting bulk optimization triggers progress UI', async ( { page } ) => {
		test.skip( ! process.env.IMAGIFY_TESTS_API_KEY, 'IMAGIFY_TESTS_API_KEY not set — skipping live optimization test' );

		const bulk = new BulkOptimizationPage( page );
		await bulk.goto();

		await bulk.optimizeButton.click();

		// After starting, Imagify should show a progress indicator.
		await expect( bulk.progressBar ).toBeVisible( { timeout: 15_000 } );
	} );
} );
