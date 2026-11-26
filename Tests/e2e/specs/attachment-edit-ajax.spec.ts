import { test, expect, type Page } from '@playwright/test';
import { loginAsAdmin } from '../fixtures/auth';
import { wpCli } from '../fixtures/wp-cli';

/**
 * Edit Media screen — the Imagify buttons run through AJAX (#434).
 *
 * On `post.php?post=<id>&action=edit`, Optimize, Re-optimize and Restore (in the sidebar panel and under the image) must
 * send an `admin-ajax.php` request and update the screen in place, instead of going through `admin-post.php` and
 * reloading the page.
 *
 * The Optimize button is only rendered when a valid API key is configured. Like reset-internal-state.spec.ts,
 * a missing key or seed is a hard failure (no test.skip), so it is visible instead of silently skipped.
 *
 * Seed requirement: run bin/dev-seed.sh with IMAGIFY_TESTS_API_KEY set (it also imports a 400px wide test image, wide
 * enough for the "Compare Original VS Optimized" button).
 */

// Imagifybeat polls every 15 s while a media is processed: leave room for a few ticks.
const RESULT_TIMEOUT = 60_000;

/**
 * Record the main frame navigations and the admin-post.php requests, so a test can check nothing reloaded the page.
 *
 * @param page The page.
 * @return The lists filled while the test runs.
 */
function watchReloads( page: Page ): { navigated: string[]; adminPost: string[] } {
	const watched = { navigated: [] as string[], adminPost: [] as string[] };

	page.on( 'request', ( request ) => {
		if ( request.url().includes( 'admin-post.php' ) ) {
			watched.adminPost.push( request.url() );
		}
	} );
	page.on( 'framenavigated', ( frame ) => {
		if ( frame === page.mainFrame() ) {
			watched.navigated.push( frame.url() );
		}
	} );

	return watched;
}

/**
 * Wait for the AJAX request of an Imagify action.
 *
 * @param page   The page.
 * @param action The AJAX action, e.g. `imagify_manual_optimize`.
 * @return The request promise.
 */
function waitForImagifyAjax( page: Page, action: string ) {
	return page.waitForRequest(
		( request ) => request.url().includes( 'admin-ajax.php' ) && request.url().includes( `action=${ action }` )
	);
}

test.describe( 'Edit Media — AJAX buttons (#434)', () => {
	let attachmentId: string;

	test.beforeEach( async ( { page } ) => {
		expect(
			process.env.IMAGIFY_TESTS_API_KEY,
			'IMAGIFY_TESTS_API_KEY env var must be set — the Optimize button is only rendered when a valid API key is configured.'
		).toBeTruthy();

		attachmentId = wpCli( 'post list --post_type=attachment --post_status=inherit --post_mime_type=image --field=ID --posts_per_page=1' ).trim();
		expect( attachmentId, 'No image in the media library — run bin/dev-seed.sh first.' ).toMatch( /^\d+$/ );

		// The Compare and Restore buttons need a backup of the original file.
		wpCli( `eval '$options = get_option( "imagify_settings" ); $options["backup"] = 1; update_option( "imagify_settings", $options );'` );

		// Start from a media that is not optimized, and not locked by a previous run. If a previous test optimized it,
		// put the original file back first, so the next optimization really compresses it.
		wpCli( `eval '$media = imagify_get_optimization_process( ${ attachmentId }, "wp" )->get_media(); $backup = $media->get_backup_path(); if ( $backup ) { copy( $backup, $media->get_raw_fullsize_path() ); }'` );
		wpCli( `eval 'delete_post_meta( ${ attachmentId }, "_imagify_status" ); delete_post_meta( ${ attachmentId }, "_imagify_data" ); delete_transient( "imagify_wp_${ attachmentId }_process_locked" );'` );

		await loginAsAdmin( page );
		await page.goto( `/wp-admin/post.php?post=${ attachmentId }&action=edit` );
	} );

	test( 'Panel is printed in the container the JS updates, under the Imagify heading', async ( { page } ) => {
		const container = page.locator( '.misc-pub-imagify .imagify-data-actions-container' );

		await expect( container ).toHaveAttribute( 'data-id', attachmentId );
		await expect( container ).toHaveAttribute( 'data-context', 'wp' );
		await expect( container.locator( 'a.button-imagify-optimize' ) ).toBeVisible();
		await expect( page.locator( '.misc-pub-imagify h4', { hasText: 'Imagify' } ) ).toBeVisible();
		await expect( page.locator( '#imagify-optimize-trigger' ) ).toBeVisible();
	} );

	test( 'Optimize updates the panel in place, then the Compare button replaces the Optimize button under the image', async ( { page } ) => {
		test.setTimeout( RESULT_TIMEOUT * 2 );

		const watched   = watchReloads( page );
		const container = page.locator( '.misc-pub-imagify .imagify-data-actions-container' );
		const request   = waitForImagifyAjax( page, 'imagify_manual_optimize' );

		await container.locator( 'a.button-imagify-optimize' ).click();
		await request;

		await expect( container.locator( '.button-imagify-processing' ) ).toBeVisible();
		await expect( page.locator( '#imagify-optimize-trigger' ) ).toHaveCount( 0 );

		// Waiting for the result also makes sure no optimization keeps running into the next test.
		await expect( container ).toContainText( 'Original Saving', { timeout: RESULT_TIMEOUT } );
		await expect( page.locator( '#imagify-start-comparison' ) ).toBeVisible();

		expect( watched.adminPost ).toHaveLength( 0 );
		expect( watched.navigated ).toHaveLength( 0 );
	} );

	test( 'Optimize under the image runs through the panel and does not reload the page', async ( { page } ) => {
		test.setTimeout( RESULT_TIMEOUT * 2 );

		const watched   = watchReloads( page );
		const container = page.locator( '.misc-pub-imagify .imagify-data-actions-container' );
		const request   = waitForImagifyAjax( page, 'imagify_manual_optimize' );

		await page.locator( '#imagify-optimize-trigger' ).click();
		await request;

		await expect( container.locator( '.button-imagify-processing' ) ).toBeVisible();
		await expect( page.locator( '#imagify-optimize-trigger' ) ).toHaveCount( 0 );
		await expect( container ).toContainText( 'Original Saving', { timeout: RESULT_TIMEOUT } );

		expect( watched.adminPost ).toHaveLength( 0 );
		expect( watched.navigated ).toHaveLength( 0 );
	} );

	test( 'Restore and Re-optimize run in place, and the Compare button follows the state', async ( { page } ) => {
		test.setTimeout( RESULT_TIMEOUT * 4 );

		const watched   = watchReloads( page );
		const container = page.locator( '.misc-pub-imagify .imagify-data-actions-container' );
		let request     = waitForImagifyAjax( page, 'imagify_manual_optimize' );

		await container.locator( 'a.button-imagify-optimize' ).click();
		await request;
		await expect( container ).toContainText( 'Original Saving', { timeout: RESULT_TIMEOUT } );
		await expect( page.locator( '#imagify-start-comparison' ) ).toBeVisible();

		// Restore: the Optimize buttons come back, the Compare button goes away.
		request = waitForImagifyAjax( page, 'imagify_restore' );
		await container.locator( 'a.button-imagify-restore' ).click();
		await request;

		await expect( container.locator( 'a.button-imagify-optimize' ) ).toBeVisible( { timeout: RESULT_TIMEOUT } );
		await expect( page.locator( '#imagify-start-comparison' ) ).toHaveCount( 0 );
		await expect( page.locator( '#imagify-optimize-trigger' ) ).toBeVisible();

		// Optimize again, then Re-optimize with the other level.
		request = waitForImagifyAjax( page, 'imagify_manual_optimize' );
		await container.locator( 'a.button-imagify-optimize' ).click();
		await request;
		await expect( container ).toContainText( 'Original Saving', { timeout: RESULT_TIMEOUT } );

		request = waitForImagifyAjax( page, 'imagify_manual_reoptimize' );
		await container.locator( 'a.button-imagify-manual-reoptimize' ).first().click();
		await request;

		await expect( container.locator( '.button-imagify-processing' ) ).toBeVisible();
		await expect( container ).toContainText( 'Original Saving', { timeout: RESULT_TIMEOUT } );
		await expect( page.locator( '#imagify-start-comparison' ) ).toBeVisible();

		expect( watched.adminPost ).toHaveLength( 0 );
		expect( watched.navigated ).toHaveLength( 0 );
	} );
} );
