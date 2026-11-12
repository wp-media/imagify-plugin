import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * Button icon alignment tests — verifies that dashicons inside Imagify buttons
 * are vertically centred and not shifted upward by the WP 7.0 CSS rule:
 *   .wp-core-ui .button .dashicons { line-height: 1.9; vertical-align: top; }
 *
 * Issue: #1063 / PR: #1083
 */
test.describe( 'Button icon alignment (WP 7.0 compat)', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	test( 'Settings page submit button dashicon has line-height: inherit', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		// The submit button on the settings page is a standard WP #submit button
		// wrapped inside .imagify-settings, so the multi-selector block applies.
		const submitButton = page.locator( '#submit' );
		await expect( submitButton ).toBeVisible( { timeout: 10000 } );

		// Find any dashicon inside a button on the settings page.
		// The [class*="imagify-"] .button .dashicons rule covers this.
		const dashiconInButton = page.locator( '[class*="imagify-"] .button .dashicons' ).first();
		const count = await dashiconInButton.count();

		if ( count === 0 ) {
			// Hard-fail: there must be at least one dashicon in a button on the settings page.
			throw new Error(
				'No .dashicons element found inside a .button within an [class*="imagify-"] container on the Settings page. ' +
				'The button icon alignment test requires at least one such button to be visible.'
			);
		}

		// Verify computed line-height on the dashicon is NOT "1.9" (which would
		// indicate the WP 7.0 override is winning).
		const computedLineHeight = await dashiconInButton.evaluate( ( el ) => {
			return window.getComputedStyle( el ).lineHeight;
		} );

		// "1.9" as a numeric multiplier resolves to roughly 28-29px for a 15px font.
		// `line-height: inherit` will resolve to the button's own natural line-height,
		// typically around 1 or expressed as a pixel value significantly below 28px.
		// We assert the value is NOT matching the 1.9 override.
		const lineHeightNum = parseFloat( computedLineHeight );
		expect(
			lineHeightNum,
			`Expected dashicon line-height to not be the WP 7.0 override (1.9 * font-size ≈ 28.5px), got: ${ computedLineHeight }`
		).not.toBeCloseTo( 28.5, 0 );

		// Screenshot the actual dashicon button for visual confirmation.
		await screenshotElement( page, 'button-icon-alignment-settings', dashiconInButton );
	} );

	test( 'Imagify button dashicons have vertical-align: middle', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		const dashiconInButton = page.locator( '[class*="imagify-"] .button .dashicons' ).first();
		const count = await dashiconInButton.count();

		if ( count === 0 ) {
			throw new Error(
				'No .dashicons element found inside a .button within an [class*="imagify-"] container on the Settings page. ' +
				'Cannot verify vertical-align without a visible dashicon button.'
			);
		}

		const verticalAlign = await dashiconInButton.evaluate( ( el ) => {
			return window.getComputedStyle( el ).verticalAlign;
		} );

		expect(
			verticalAlign,
			`Expected dashicon vertical-align to be "middle", got: ${ verticalAlign }`
		).toBe( 'middle' );
	} );
} );
