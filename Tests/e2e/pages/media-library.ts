import { Page, Locator, expect } from '@playwright/test';

/**
 * Page Object for the WordPress Media Library page (list view).
 * URL: /wp-admin/upload.php?mode=list
 *
 * Imagify injects an "Optimization" column into the media library table.
 */
export class MediaLibraryPage {
	readonly page: Page;
	readonly imagifyColumn: Locator;

	constructor( page: Page ) {
		this.page = page;
		// The column header injected by Imagify.
		this.imagifyColumn = page.locator( 'th.column-imagify_status, th#imagify_status' ).first();
	}

	async goto(): Promise<void> {
		// Force list mode so the table is rendered (grid mode has no column headers).
		await this.page.goto( '/wp-admin/upload.php?mode=list' );
		await this.page.waitForLoadState( 'networkidle' );
	}

	async isLoaded(): Promise<boolean> {
		const table = this.page.locator( '#the-list, .media-list' );
		return await table.count() > 0;
	}

	async hasImagifyColumn(): Promise<boolean> {
		return await this.imagifyColumn.count() > 0;
	}

	async getFirstAttachmentStatus(): Promise<string> {
		const status = this.page.locator( 'td.column-imagify_status' ).first();
		return ( await status.textContent() ) ?? '';
	}

	async expectNoFatalError(): Promise<void> {
		await expect( this.page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	}
}
