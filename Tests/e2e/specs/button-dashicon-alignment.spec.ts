import { test, expect } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';

/**
 * CSS regression test for issue #1063 — dashicon alignment inside Imagify buttons.
 *
 * WordPress 7.0 introduced:
 *   .wp-core-ui .button .dashicons { line-height: 1.9; vertical-align: top; }
 *
 * The fix in admin.css adds `line-height: 1` to two Imagify-scoped selectors so
 * dashicons stay vertically centred with their label text. These tests verify
 * that the CSS rules are loaded and contain the correct declarations, using the
 * browser's CSSOM (CSSStyleSheet) so the verification is independent of which
 * dashicon elements happen to appear without an API key.
 */

/**
 * Read all CSS rules from all loaded stylesheets in the page whose selectorText
 * contains BOTH of the provided fragments.
 */
async function findCSSRules(
	page: import( '@playwright/test' ).Page,
	fragment1: string,
	fragment2: string,
): Promise<{ selectorText: string; cssText: string }[]> {
	return page.evaluate( ( [ f1, f2 ] ) => {
		const results: { selectorText: string; cssText: string }[] = [];
		for ( const sheet of Array.from( document.styleSheets ) ) {
			let rules: CSSRuleList;
			try {
				rules = sheet.cssRules;
			} catch {
				// Cross-origin stylesheet — skip.
				continue;
			}
			for ( const rule of Array.from( rules ) ) {
				if (
					rule instanceof CSSStyleRule &&
					rule.selectorText?.includes( f1 ) &&
					rule.selectorText?.includes( f2 )
				) {
					results.push( {
						selectorText: rule.selectorText,
						cssText: rule.cssText,
					} );
				}
			}
		}
		return results;
	}, [ fragment1, fragment2 ] );
}

test.describe( 'Dashicon alignment inside Imagify buttons (issue #1063)', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// -------------------------------------------------------------------------
	// Selector group 1: [class*="imagify-"] .button .dashicons
	// This is the rule added in the PR for admin-bar-style generic buttons.
	// -------------------------------------------------------------------------
	test( 'CSS rule for [class*="imagify-"] .button .dashicons contains line-height: 1', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		// Match rules that contain both "imagify" and ".button .dashicons".
		const rules = await findCSSRules( page, 'imagify', '.button .dashicons' );

		expect(
			rules.length,
			'Expected to find a CSS rule scoped to imagify containing ".button .dashicons"',
		).toBeGreaterThan( 0 );

		// The fix requires both vertical-align: middle AND line-height: 1.
		const rule = rules[ 0 ];
		expect( rule.cssText ).toContain( 'line-height: 1' );
		expect( rule.cssText ).toContain( 'vertical-align: middle' );
	} );

	// -------------------------------------------------------------------------
	// Selector group 2: .imagify-button-primary.imagify-button-primary .dashicons
	// The doubled class ensures higher specificity; the fix adds line-height: 1.
	// -------------------------------------------------------------------------
	test( 'CSS rule for .imagify-button-primary .dashicons contains line-height: 1', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		// Match rules that contain both "imagify-button-primary" and ".dashicons".
		const rules = await findCSSRules( page, 'imagify-button-primary', '.dashicons' );

		expect(
			rules.length,
			'Expected to find a CSS rule for .imagify-button-primary .dashicons',
		).toBeGreaterThan( 0 );

		const rule = rules[ 0 ];
		expect( rule.cssText ).toContain( 'line-height: 1' );
		expect( rule.cssText ).toContain( 'vertical-align: middle' );
	} );

	// -------------------------------------------------------------------------
	// Same check on the Bulk Optimization page: admin.css must be enqueued there.
	// -------------------------------------------------------------------------
	test( 'Bulk Optimization page: CSS rule [class*="imagify-"] .button .dashicons is present and has line-height: 1', async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?page=imagify-bulk-optimization' );
		await page.waitForLoadState( 'networkidle' );

		const rules = await findCSSRules( page, 'imagify', '.button .dashicons' );

		expect(
			rules.length,
			'Expected to find an Imagify CSS rule targeting ".button .dashicons" on the Bulk Optimization page',
		).toBeGreaterThan( 0 );

		const rule = rules[ 0 ];
		expect( rule.cssText ).toContain( 'line-height: 1' );
		expect( rule.cssText ).toContain( 'vertical-align: middle' );
	} );

	// -------------------------------------------------------------------------
	// Visual smoke — screenshots for manual inspection.
	// -------------------------------------------------------------------------
	test( 'Settings page screenshot — button icon alignment visual check', async ( { page } ) => {
		await page.goto( '/wp-admin/options-general.php?page=imagify' );
		await page.waitForLoadState( 'networkidle' );

		// No assertion — visual reference for QA reviewers.
		await page.screenshot( {
			path: '.e2e-screenshots/settings-button-alignment.png',
			fullPage: false,
		} );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	} );

	test( 'Bulk Optimization page screenshot — button icon alignment visual check', async ( { page } ) => {
		await page.goto( '/wp-admin/upload.php?page=imagify-bulk-optimization' );
		await page.waitForLoadState( 'networkidle' );

		await page.screenshot( {
			path: '.e2e-screenshots/bulk-button-alignment.png',
			fullPage: false,
		} );

		await expect( page.locator( '.wp-die-message, #error-page' ) ).toHaveCount( 0 );
	} );
} );
