import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';
import { screenshotElement } from '../fixtures/screenshot';

/**
 * Welcome banner header spans the full available width.
 *
 * AC #1316: the dark header (`.imagify-title`) of the welcome-steps notice
 * spans the full width of the admin content area and shares its left and
 * right edges with the white section below it, at desktop, tablet and mobile
 * widths, with the Screen Options panel closed or open.
 *
 * The header is a flex container, so without `clear: both` on
 * `.imagify-welcome` it shrinks to sit beside core's floated
 * `#screen-meta-links` (Screen Options / Help) while the section below stays
 * full width.
 *
 * The welcome-steps notice only renders when no API key is saved and the
 * notice is not dismissed. beforeAll backs up the API key server-side (it
 * never reaches this process), blanks it and renews the notice; afterAll
 * restores the key so the other specs keep their seeded state.
 *
 * Related issue: #1316
 */

const VIEWPORTS = [
	{ label: 'desktop', width: 1440, height: 900 },
	{ label: 'tablet', width: 900, height: 900 },
	{ label: 'mobile', width: 600, height: 900 },
];

test.describe( 'Welcome banner header — full width (#1316)', () => {
	test.beforeAll( () => {
		wpCli(
			`eval '$s = (array) get_option( "imagify_settings", [] ); update_option( "imagify_e2e_1316_api_key_backup", $s["api_key"] ?? "", false ); $s["api_key"] = ""; update_option( "imagify_settings", $s ); \\Imagify\\Notices\\Notices::renew_notice( "welcome-steps", get_user_by( "login", "admin" )->ID );'`,
		);
	} );

	test.afterAll( () => {
		wpCli(
			`eval '$s = (array) get_option( "imagify_settings", [] ); $s["api_key"] = (string) get_option( "imagify_e2e_1316_api_key_backup", "" ); update_option( "imagify_settings", $s ); delete_option( "imagify_e2e_1316_api_key_backup" );'`,
		);
	} );

	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	async function expectHeaderMatchesSection( page: Page ) {
		const banner  = page.locator( '.imagify-welcome' );
		const header  = banner.locator( '.imagify-title' );
		const section = banner.locator( '.imagify-settings-section' );

		await expect(
			banner,
			'The welcome-steps notice is not displayed — check the beforeAll state setup (API key blanked, notice renewed).',
		).toBeVisible();

		const headerBox  = await header.boundingBox();
		const sectionBox = await section.boundingBox();

		expect( headerBox ).not.toBeNull();
		expect( sectionBox ).not.toBeNull();

		// Same left edge, same right edge, same width (1px rounding tolerance).
		expect( Math.abs( headerBox!.x - sectionBox!.x ) ).toBeLessThanOrEqual( 1 );
		expect(
			Math.abs( ( headerBox!.x + headerBox!.width ) - ( sectionBox!.x + sectionBox!.width ) ),
		).toBeLessThanOrEqual( 1 );
		expect( Math.abs( headerBox!.width - sectionBox!.width ) ).toBeLessThanOrEqual( 1 );
	}

	for ( const viewport of VIEWPORTS ) {
		test( `header spans the section width at ${ viewport.width }px (${ viewport.label })`, async ( { page } ) => {
			await page.setViewportSize( { width: viewport.width, height: viewport.height } );
			await page.goto( '/wp-admin/plugins.php', { waitUntil: 'networkidle' } );

			await expectHeaderMatchesSection( page );

			await screenshotElement( page, `welcome-banner-${ viewport.label }-${ viewport.width }`, page.locator( '.imagify-welcome' ) );
		} );

		test( `header spans the section width at ${ viewport.width }px with Screen Options open (${ viewport.label })`, async ( { page } ) => {
			await page.setViewportSize( { width: viewport.width, height: viewport.height } );
			await page.goto( '/wp-admin/plugins.php', { waitUntil: 'networkidle' } );

			const screenOptions = page.locator( '#show-settings-link' );

			if ( await screenOptions.isVisible() ) {
				await screenOptions.click();
				await expect( page.locator( '#screen-options-wrap' ) ).toBeVisible();
			}

			await expectHeaderMatchesSection( page );
		} );
	}
} );
