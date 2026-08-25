import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * Responsive "Generate missing Next-Gen images versions" button.
 *
 * AC #1045: The button does not overflow on narrow viewports.
 *
 * The `.generate-missing-webp` section only renders when the
 * `imagify_stat_without_next_gen` transient has stat > 0.
 * beforeAll seeds the transient automatically via WP-CLI.
 *
 * Related PR:    #1080
 * Related issue: #1045
 */

test.describe( 'Generate missing Next-Gen button — responsive layout (#1045)', () => {
	test.beforeAll( () => {
		wpCli(
			`eval 'set_transient( "imagify_stat_without_next_gen", array( "contexts" => implode( "|", imagify_get_context_names() ), "stat" => 5 ), 172800 );'`,
		);
	} );

	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	async function gotoSettingsAndGetButton( page: Page ) {
		await page.goto( '/wp-admin/options-general.php?page=imagify', {
			waitUntil: 'networkidle',
		} );

		const container = page.locator( '.generate-missing-webp' );

		expect(
			await container.isVisible(),
			'The .generate-missing-webp section is not visible after seeding the transient — check beforeAll.',
		).toBe( true );

		const button = container.locator( '#imagify-generate-webp-versions' );
		await expect( button ).toBeVisible( { timeout: 5_000 } );

		return { container, button };
	}

	test( 'button does not overflow container at desktop width (1440 px)', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshotElement( page, 'generate-webp-button-desktop-1440', container );
	} );

	test( 'button does not overflow container at 782 px (WP mobile breakpoint)', async ( { page } ) => {
		await page.setViewportSize( { width: 782, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshotElement( page, 'generate-webp-button-tablet-782', container );
	} );

	test( 'button does not overflow container at 375 px (mobile)', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerBox = await container.boundingBox();
		const buttonBox    = await button.boundingBox();

		expect( containerBox ).not.toBeNull();
		expect( buttonBox ).not.toBeNull();

		expect( buttonBox!.x + buttonBox!.width ).toBeLessThanOrEqual(
			containerBox!.x + containerBox!.width + 1,
		);

		await screenshotElement( page, 'generate-webp-button-mobile-375', container );
	} );

	test( 'button has white-space:normal and word-break:break-word (CSS wrapping properties applied)', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const { button } = await gotoSettingsAndGetButton( page );

		const whiteSpace = await button.evaluate(
			( el ) => window.getComputedStyle( el ).whiteSpace,
		);
		const wordBreak = await button.evaluate(
			( el ) => window.getComputedStyle( el ).wordBreak,
		);

		expect( whiteSpace ).toBe( 'normal' );
		expect( wordBreak ).toBe( 'break-word' );
	} );

	test( 'button fills container width at 782 px mobile breakpoint (width:100%)', async ( { page } ) => {
		await page.setViewportSize( { width: 782, height: 900 } );
		const { container, button } = await gotoSettingsAndGetButton( page );

		const containerWidth = await container.evaluate(
			( el ) => el.getBoundingClientRect().width,
		);
		const buttonWidth = await button.evaluate(
			( el ) => el.getBoundingClientRect().width,
		);

		expect( buttonWidth ).toBeGreaterThanOrEqual( containerWidth - 2 );
	} );
} );
