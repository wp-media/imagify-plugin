import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';

/**
 * "Generate missing Next-Gen versions" progress bar.
 *
 * AC #1217: while a generation run is in progress, the progress bar is visible and the
 * counter sits on the bar, and the disabled button still looks like a button.
 *
 * The `.generate-missing-webp` section renders when the `imagify_stat_without_next_gen`
 * transient has stat > 0. The progress bar inside it is only revealed when the
 * `imagify_missing_next_gen_total` transient exists, which is what marks a run as running.
 * beforeAll seeds both via WP-CLI.
 *
 * Related issue: #1217
 */

test.describe( 'Generate missing Next-Gen progress bar (#1217)', () => {
	test.beforeAll( () => {
		wpCli(
			`eval 'set_transient( "imagify_stat_without_next_gen", array( "contexts" => implode( "|", imagify_get_context_names() ), "stat" => 9 ), 172800 );'`,
		);
		wpCli( `eval 'set_transient( "imagify_missing_next_gen_total", 9, 3600 );'` );
	} );

	test.afterAll( () => {
		wpCli( `eval 'delete_transient( "imagify_missing_next_gen_total" );'` );
	} );

	test.beforeEach( async ( { page } ) => {
		await loginAsAdmin( page );
	} );

	async function gotoSettings( page: Page ) {
		await page.goto( '/wp-admin/options-general.php?page=imagify', {
			waitUntil: 'networkidle',
		} );

		const container = page.locator( '.generate-missing-webp' );

		expect(
			await container.isVisible(),
			'The .generate-missing-webp section is not visible after seeding the transients — check beforeAll.',
		).toBe( true );

		return container;
	}

	test( 'the progress bar has a visible track', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const container = await gotoSettings( page );

		const track = container.locator( '.imagify-progress .progress' );
		await expect( track ).toBeVisible();

		const box = await track.boundingBox();

		expect( box ).not.toBeNull();

		// Regression guard: the wrapper is a flex item under `align-items: flex-start` and has
		// no intrinsic width, so without an explicit stretch it collapsed to zero and no bar
		// was ever drawn.
		expect(
			box!.width,
			'The progress track collapsed to zero width, so no progress bar is drawn.',
		).toBeGreaterThan( 0 );
	} );

	test( 'the counter sits on the bar rather than outside the container', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const container = await gotoSettings( page );

		const track   = container.locator( '.imagify-progress .progress' );
		const counter = container.locator( '.imagify-progress .percent' );

		await expect( counter ).toBeVisible();

		const trackBox   = await track.boundingBox();
		const counterBox = await counter.boundingBox();

		expect( trackBox ).not.toBeNull();
		expect( counterBox ).not.toBeNull();

		// The counter is absolutely positioned against the filled bar. When the track had no
		// width it was rendered to the left of the whole settings column.
		expect(
			counterBox!.x,
			'The counter is rendered to the left of the progress track.',
		).toBeGreaterThanOrEqual( trackBox!.x - 1 );

		expect(
			counterBox!.x + counterBox!.width,
			'The counter overflows past the end of the progress track.',
		).toBeLessThanOrEqual( trackBox!.x + trackBox!.width + 1 );
	} );

	test( 'the button still looks like a button while disabled', async ( { page } ) => {
		await page.setViewportSize( { width: 1440, height: 900 } );
		const container = await gotoSettings( page );

		const button = container.locator( '#imagify-generate-webp-versions' );
		await expect( button ).toBeVisible();

		// The run is in progress, so the JS disables the button.
		await expect( button ).toBeDisabled();

		const background = await button.evaluate(
			( el ) => window.getComputedStyle( el ).backgroundColor,
		);

		// WordPress core styles disabled buttons with `background: transparent !important`.
		// Combined with Imagify's `border: 0` that left the button as bare text.
		expect(
			background,
			'The disabled button has no background, so it reads as plain text rather than a button.',
		).not.toBe( 'rgba(0, 0, 0, 0)' );
		expect( background ).not.toBe( 'transparent' );
	} );
} );
