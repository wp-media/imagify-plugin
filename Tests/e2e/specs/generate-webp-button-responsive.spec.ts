import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import * as path from 'path';
import * as fs from 'fs';

/**
 * Responsive "Generate missing Next-Gen images versions" button.
 *
 * AC #1045: The button "Generate missing Next-Gen images versions" is
 * responsive (does not overflow on narrow viewports).
 *
 * The `.generate-missing-webp` section is only rendered when the
 * `imagify_stat_without_next_gen` transient returns a non-zero count.
 * Seed it before running this suite:
 *
 *   npx @wordpress/env run cli wp eval \
 *     'set_transient("imagify_stat_without_next_gen", ["contexts"=>"wp-library","stat"=>3], 172800);'
 *
 * Related PR:    #1080
 * Related issue: #1045
 */

const SCREENSHOTS_DIR = path.join(
	__dirname,
	'..',
	'.e2e-screenshots',
);

/** Ensure the screenshots directory exists. */
function ensureScreenshotsDir() {
	if ( ! fs.existsSync( SCREENSHOTS_DIR ) ) {
		fs.mkdirSync( SCREENSHOTS_DIR, { recursive: true } );
	}
}

/**
 * Capture a screenshot and save it under `.e2e-screenshots/`.
 * Returns the absolute path.
 */
async function screenshot( page: Page, name: string ): Promise<string> {
	ensureScreenshotsDir();
	const filePath = path.join( SCREENSHOTS_DIR, `${ name }.png` );
	await page.screenshot( { path: filePath, fullPage: false } );
	return filePath;
}

test.describe( 'Generate missing Next-Gen button — responsive layout (#1045)', () => {
	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	// -----------------------------------------------------------------------
	// Helper: navigate to settings and locate the button
	// -----------------------------------------------------------------------

	async function gotoSettingsAndGetButton( page: Page ) {
		await page.goto( '/wp-admin/options-general.php?page=imagify', {
			waitUntil: 'networkidle',
		} );

		// The section only renders when there are images without next-gen versions.
		const container = page.locator( '.generate-missing-webp' );
		const isVisible  = await container.isVisible();

		if ( ! isVisible ) {
			// If the section is hidden the test environment has no qualifying images.
			// Skip gracefully so the suite does not produce a false FAIL.
			test.skip(
				true,
				'The .generate-missing-webp section is not visible — ' +
				'seed the imagify_stat_without_next_gen transient before running this suite.',
			);
		}

		const button = container.locator( '#imagify-generate-webp-versions' );
		await expect( button ).toBeVisible( { timeout: 5_000 } );

		return { container, button };
	}

	// -----------------------------------------------------------------------
	// Desktop (1440 px default viewport)
	// -----------------------------------------------------------------------

	test( 'button does not overflow container at desktop width (1440 px)', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		// Button right edge must not exceed container right edge (1 px sub-pixel tolerance).
		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshot( page, 'generate-webp-button-desktop-1440' );
	} );

	// -----------------------------------------------------------------------
	// Tablet / WordPress mobile breakpoint (782 px)
	// -----------------------------------------------------------------------

	test( 'button does not overflow container at 782 px (WP mobile breakpoint)', async ( { page } ) => {
		await page.setViewportSize( { width: 782, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		// Button must not overflow the container.
		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshot( page, 'generate-webp-button-tablet-782' );
	} );

	// -----------------------------------------------------------------------
	// Mobile (375 px)
	// -----------------------------------------------------------------------

	test( 'button does not overflow container at 375 px (mobile)', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		// Button must not overflow the container.
		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshot( page, 'generate-webp-button-mobile-375' );
	} );

	// -----------------------------------------------------------------------
	// CSS property validation — white-space and word-break allow wrapping
	// -----------------------------------------------------------------------

	test( 'button has white-space:normal and word-break:break-word (CSS wrapping properties applied)', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const { button } = await gotoSettingsAndGetButton( page );

		const whiteSpace = await button.evaluate(
			( el ) => window.getComputedStyle( el ).whiteSpace,
		);
		const wordBreak = await button.evaluate(
			( el ) => window.getComputedStyle( el ).wordBreak,
		);

		// white-space: normal allows text to wrap (not nowrap).
		expect( whiteSpace ).toBe( 'normal' );
		// word-break: break-word prevents overflow on very long words.
		expect( wordBreak ).toBe( 'break-word' );
	} );

	// -----------------------------------------------------------------------
	// Full-width check at ≤ 782 px
	// -----------------------------------------------------------------------

	test( 'button fills container width at 782 px mobile breakpoint (width:100%)', async ( { page } ) => {
		await page.setViewportSize( { width: 782, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerWidth = await container.evaluate(
			( el ) => el.getBoundingClientRect().width,
		);
		const buttonWidth = await button.evaluate(
			( el ) => el.getBoundingClientRect().width,
		);

		// At ≤782 px the button should fill its container (width:100%).
		// Allow a 2 px tolerance for borders/padding.
		expect( buttonWidth ).toBeGreaterThanOrEqual( containerWidth - 2 );
	} );
} );
