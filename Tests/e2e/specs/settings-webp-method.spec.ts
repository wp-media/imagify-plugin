import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';
import { SettingsPage } from '../pages/settings';

/**
 * Settings — WebP/AVIF next-gen delivery method copy tests.
 *
 * Validates that the <picture> tags radio label reads "(CDN-compatible)"
 * (not the old "(preferred)") and that the warning info box explicitly names
 * the categories of content that can be affected.
 *
 * Related PR:   #1056  (fix/1015-improve-clarity-picture-tag)
 * Related issue: #1015
 */
test.describe( 'Settings — WebP delivery method copy', () => {
	test.beforeEach( async ( { page } ) => {
		// Ensure the display_nextgen option is enabled so the method selector
		// and its info box are rendered in the DOM.
		wpCli(
			`eval "update_option('imagify_settings', array_merge(get_option('imagify_settings', []), ['display_nextgen' => true, 'display_nextgen_method' => 'picture']));"`,
		);
		await loginAsAdmin( page );
	} );

	test( 'picture-tag radio label reads CDN-compatible, not preferred', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		// The label associated with the "picture" radio must contain "CDN-compatible".
		const pictureLabel = page.locator( 'label' ).filter( { hasText: /CDN-compatible/i } );
		await expect( pictureLabel ).toBeVisible( { timeout: 10_000 } );

		// The old "(preferred)" wording must not appear anywhere on the page.
		const oldWording = page.locator( 'body' ).filter( { hasText: /\(preferred\)/i } );
		await expect( oldWording ).toHaveCount( 0 );
	} );

	test( 'warning info box names sliders, WooCommerce, galleries, background images', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		// The info box below the delivery-method radios must contain explicit
		// references to the content categories that can be broken by the
		// <picture> tag method.
		const infoBox = page.locator( '#describe-display_nextgen_method' );
		await expect( infoBox ).toBeVisible( { timeout: 10_000 } );

		const infoText = await infoBox.innerText();

		expect( infoText ).toMatch( /sliders/i );
		expect( infoText ).toMatch( /WooCommerce/i );
		expect( infoText ).toMatch( /galleries/i );
		expect( infoText ).toMatch( /[Bb]ackground images/i );
	} );

	test( 'warning info box explicitly warns about layout breakage', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		const infoBox = page.locator( '#describe-display_nextgen_method' );
		await expect( infoBox ).toBeVisible( { timeout: 10_000 } );

		// The warning should use the word "Warning" and mention reviewing the site.
		const infoText = await infoBox.innerText();
		expect( infoText ).toMatch( /[Ww]arning/i );
		expect( infoText ).toMatch( /review/i );
	} );

	test( 'rewrite-rules radio option is still present', async ( { page } ) => {
		const settings = new SettingsPage( page );
		await settings.goto();
		await settings.expectNoFatalError();

		// The alternative "rewrite rules" option must not have been removed.
		const rewriteLabel = page.locator( 'label' ).filter( { hasText: /rewrite rules/i } );
		await expect( rewriteLabel ).toBeVisible( { timeout: 10_000 } );
	} );
} );
