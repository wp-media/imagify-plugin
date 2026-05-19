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
		// On first run, Imagify shows a SweetAlert2 info modal before starting.
		// Dismiss it so launchAllProcesses() is called.
		const modal = this.page.locator( '.swal2-popup.imagify-before-bulk-infos' );
		try {
			await modal.waitFor( { state: 'visible', timeout: 3_000 } );
			await modal.locator( '.swal2-confirm' ).click();
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
