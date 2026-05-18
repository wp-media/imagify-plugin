import { Page, Locator, expect } from '@playwright/test';

/**
 * Page Object for the Imagify Settings page.
 * URL: /wp-admin/options-general.php?page=imagify
 */
export class SettingsPage {
	readonly page: Page;
	readonly apiKeyInput: Locator;
	readonly saveButton: Locator;
	readonly successNotice: Locator;

	constructor( page: Page ) {
		this.page = page;
		this.apiKeyInput  = page.locator( '#imagify-api-key, [name="imagify_settings[api_key]"]' ).first();
		this.saveButton   = page.getByRole( 'button', { name: /save/i } );
		this.successNotice = page.locator( '.notice-success, .updated' ).first();
	}

	async goto(): Promise<void> {
		await this.page.goto( '/wp-admin/options-general.php?page=imagify' );
		await this.page.waitForLoadState( 'networkidle' );
	}

	async isLoaded(): Promise<boolean> {
		const title = this.page.locator( 'h1, h2' ).filter( { hasText: /imagify/i } );
		return await title.count() > 0;
	}

	async setApiKey( key: string ): Promise<void> {
		await this.apiKeyInput.fill( key );
		await this.saveButton.click();
		await this.successNotice.waitFor( { timeout: 10_000 } );
	}

	async getApiKey(): Promise<string> {
		return ( await this.apiKeyInput.inputValue() ) ?? '';
	}

	async expectNoFatalError(): Promise<void> {
		await expect( this.page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	}
}
