import { Page, Locator, expect } from '@playwright/test';

/**
 * Page Object for the Imagify Bulk Optimization page.
 * URL: /wp-admin/upload.php?page=imagify-bulk-optimization
 */
export class BulkOptimizationPage {
	readonly page: Page;
	readonly optimizeButton: Locator;
	readonly progressBar: Locator;
	readonly statsTable: Locator;

	constructor( page: Page ) {
		this.page = page;
		// The main "Imagif'em all" button.
		this.optimizeButton = page.locator( '#imagify-bulk-action' );
		this.progressBar    = page.locator( '.imagify-row-progress' ).first();
		this.statsTable     = page.locator( '.imagify-bulk-table' ).first();
	}

	async startOptimization(): Promise<void> {
		await this.optimizeButton.click();
		// On first run, Imagify shows a "before bulk" info modal. Dismiss it by
		// clicking the confirm button. Use text matching so we're not tied to
		// SweetAlert2's internal class names (they changed between v6 and v7).
		const confirmButton = this.page.getByRole( 'button', { name: /start the optimization/i } );
		try {
			await confirmButton.waitFor( { state: 'visible', timeout: 3_000 } );
			await confirmButton.click();
		} catch {
			// No modal — optimization started directly.
		}
	}

	async goto(): Promise<void> {
		await this.page.goto( '/wp-admin/upload.php?page=imagify-bulk-optimization' );
		await this.page.waitForLoadState( 'networkidle' );
	}

	async isLoaded(): Promise<boolean> {
		const heading = this.page.locator( 'h1, h2' ).filter( { hasText: /bulk/i } );
		return await heading.count() > 0;
	}

	async expectNoFatalError(): Promise<void> {
		await expect( this.page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	}

	async getOptimizedCount(): Promise<number> {
		const text = await this.statsTable.textContent();
		const match = text?.match( /(\d+)\s*(image|file)/i );
		return match ? parseInt( match[ 1 ], 10 ) : 0;
	}
}
